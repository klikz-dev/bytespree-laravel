<?php

namespace App\Classes\Publishers;

use App\Models\Explorer\Publisher as PublisherTable;
use App\Classes\Database\Connection;
use App\Classes\Mssql as MssqlConnection;
use DateTime;
use Exception;

class Mssql extends Publisher
{
    public $columns = [];
    public $options = NULL;
    public $publisher_name = 'Microsoft SQL Server'; // What's shown in alerts/notifications
    public $use_hooks = TRUE; // Make sure when publishing, the caller knows we're utilizing callbacks.
    public $rows_published = 0;

    protected $schema = 'dbo';
    protected $table;

    private $mssql_connection; // For our remote connection
    private $column_mappings = [];
    private $database_name;
    private $destination_columns = [];
    private $max_per_insert = 100;
    private $unsupported_columns = [];

    /**
     * Callback that is called when the columns from our publish job are available to us
     *
     * @param  array $columns An array of columns that are returned by the query
     * @return void
     */
    public function retrievedColumns($columns)
    {
        $this->prepareColumns($columns);

        if ($this->destination_options->using_new_table) {
            $column_string = $this->generateColumnString();

            try {
                $this->mssql_connection->beginTransaction();
                $this->mssql_connection->createTable($this->schema, $this->table, $column_string);
                $this->mssql_connection->commit();
            } catch (Exception $e) {
                $this->mssql_connection->rollback();
                logger()->error(
                    'When publishing, could not create destination SQL Server table.',
                    [
                        'error'   => $e->getMessage(),
                        'columns' => $column_string
                    ]
                );
                throw new Exception('Failed to create new table.');
            }
        }

        $this->getDestinationDataTypes();

        $this->verifyNecessaryColumnsExistInDestination();

        if ($this->destination_options->truncate_on_publish) {
            $this->mssql_connection->truncateTable($this->schema, $this->table);
        }
    }

    /**
     * Get the column types from the destination's table
     *
     * @return void
     */
    public function getDestinationDataTypes()
    {
        $columns = $this->mssql_connection->getColumns($this->schema, $this->table);

        $this->destination_columns = [];

        foreach ($columns as $column) {
            if (method_exists($this, 'apply_' . $column->DATA_TYPE)) {
                $column->DATA_APPLY_METHOD = 'apply_' . $column->DATA_TYPE;
            }

            $this->destination_columns[$column->COLUMN_NAME] = $column;
        }
    }

    /**
     * Generate a string from our columns used for table creation
     *
     * @return string
     */
    public function generateColumnString()
    {
        $without_unsupported_types = array_filter($this->columns, function ($column) {
            return $column['type'] != 'unsupported';
        });

        $columns_with_types = array_map(function ($column) {
            return "[{$column['name']}] {$column['type']}";
        }, $without_unsupported_types);

        return implode(",\n    ", $columns_with_types);
    }

    /**
     * Prepare our columns by guessing the SQL Server equivelant type
     *
     * @param  array $columns Columns to be prepared
     * @return void
     */
    public function prepareColumns($columns)
    {
        // Filter out any columns that aren't checked
        $columns = array_filter($columns, function ($column) {
            return filter_var($column['checked'], FILTER_VALIDATE_BOOLEAN) == TRUE;
        });

        $this->columns = array_map(function ($column) {
            $column_name = $column['alias'] == '' ? $column['target_column_name'] : $column['alias'];
            $use_length = FALSE;
            $length = NULL;

            // Custom columns
            if ($column['added'] && $column['prefix'] == 'custom') {
                $column['data_type'] = 'custom';
                $use_length = TRUE;
                $length = 'MAX';
            }

            switch (strtolower($column['data_type'])) {
                case 'varchar':
                case 'character varying':
                    $use_length = TRUE;
                    $length = $column['character_maximum_length'] == 0 ? 'MAX' : $column['character_maximum_length'];
                    break;
                case 'timestamp':
                case 'timestamp without time zone':
                case 'timestamp with time zone':
                    $use_length = TRUE;
                    break;
                case 'json':
                case 'jsonb':
                    $use_length = TRUE;
            }

            $type = $this->mssql_connection->getConvertedType($column['data_type'], $use_length, $length);

            if ($type == 'unsupported') {
                $this->unsupported_columns[$column_name] = TRUE;
            }

            return [
                'name' => $column_name,
                'type' => $type
            ];
        }, $columns);
    }

    /**
     * Callback for when we retrieve a chunk of data from our outer SQL cursor
     *
     * @param  array $data A multidimensional array of SQL returned data
     * @return void
     */
    public function chunk($data)
    {
        if ($this->rows_published == 0) {
            $this->mssql_connection->beginTransaction();
        }

        $chunks = array_chunk($data, $this->max_per_insert);

        foreach ($chunks as $inserting) {
            // Apply source -> destination mapping if inserting into an existing database
            if (! empty($this->column_mappings)) {
                $inserting = array_map(function ($row) {
                    $mapped = [];
                    $row = (array) $row;
                    foreach ($this->column_mappings as $mapping) {
                        $mapped[$mapping['destination_column']] = $row[$mapping['name']];
                    }

                    return $mapped;
                }, $inserting);
            }

            // Apply any necessary transformations to our data types for this chunk
            $inserting = array_map(function ($row) {
                return $this->applySqlServerDataTypes($row);
            }, $inserting);

            $this->mssql_connection->insert($this->schema, $this->table, $inserting);

            $this->rows_published += count($inserting);

            if ($this->rows_published % 100000 == 0) {
                echo "{$this->rows_published} rows published\n";
            }
        }
    }

    /**
     * Apply our PHP typing to help the conversion of particular columns e.g. dates w/offsets & booleans (t/f)
     *
     * @param  array $row Array representation of the row being published
     * @return void
     */
    private function applySqlServerDataTypes($row)
    {
        $row = array_filter((array) $row, function ($key) {
            return isset($this->destination_columns[$key]);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($row as $column => $value) {
            if (isset($this->destination_columns[$column]->DATA_APPLY_METHOD)) {
                $row[$column] = $this->{$this->destination_columns[$column]['DATA_APPLY_METHOD']}($value);
            }
        }

        return $row;
    }

    /**
     * Callback to be executed before our publish job runs
     *
     * @return void
     */
    public function beforePublish()
    {
        $this->rows_published = 0;

        $server = PublisherTable::find($this->destination_options->server_id);

        if ($server->count() == 0) {
            throw new Exception('Server was not found');
        }

        $server->data = (object) $server->data;
        $config = [
            'host'     => $server->data->hostname,
            'port'     => $server->data->port,
            'username' => $server->data->username,
            'password' => $server->data->password
        ];

        // Test the server
        $this->mssql_connection = new MssqlConnection($config);

        // Are we using a new database, or an existing one?
        if ($this->destination_options->using_new_database) {
            $this->destination_options->target_create_database = mb_substr($this->destination_options->target_create_database, 0, 124);
            if (! $this->mssql_connection->createDatabase($this->destination_options->target_create_database)) {
                throw new Exception("The database {$this->destination_options->target_create_database} could not be created.");
            }
            $this->database_name = $this->destination_options->target_create_database;
        } else {
            $this->database_name = $this->destination_options->target_database;
        }

        $this->mssql_connection->setDatabase($this->database_name);

        if ($this->destination_options->using_new_table) {
            $this->table = $this->destination_options->target_create_table;

            if (isset($this->destination_options->append_timestamp) && $this->destination_options->append_timestamp) {
                $this->table = mb_substr($this->table, 0, 113); // Just in case we're pushing the 128 character limit of SQL server (suffix is 15 characters)
                $this->table .= '_' . date('Ymdhis');
            }
        } else {
            $this->table = $this->destination_options->target_table;
        }

        $parts = explode('.', $this->table);
        if (count($parts) > 1) {
            $this->schema = $parts[0];
            $this->table = $parts[1];
        } else {
            $this->table = $parts[0];
        }        

        if (! $this->destination_options->using_new_table) {
            if (! $this->mssql_connection->tableExists($this->schema, $this->table, $this->destination_options->target_database)) {
                throw new Exception('Table could not be accessed.');
            }
        }

        $this->column_mappings = $this->destination_options->column_mappings ?? [];
    }

    /**
     * After publish callback
     *
     * @return void
     */
    public function onSuccess()
    {
        $this->mssql_connection->commit();

        $this->notifyUsers(TRUE);
    }

    /**
     * Apply a filter to convert a datetime PostGres value to use the RFC3339 format SQL Server uses.
     *
     * @param  mixed  $value Incoming value
     * @return string A re-formatted string; the original value if date creation fails
     */
    public function apply_datetimeoffset($value)
    {
        try {
            return (new DateTime($value))->format(DateTime::RFC3339);
        } catch (Exception $e) {
            return $value;
        }
    }

    /**
     * Our error callback
     *
     * @return void
     */
    public function onError()
    {
        if ($this->mssql_connection) {
            $this->mssql_connection->rollback();
        }

        $this->notifyUsers(FALSE, NULL, $this->error_message);
    }

    /**
     * Verify the destination table has our required columns
     *
     * @return void
     */
    public function verifyNecessaryColumnsExistInDestination()
    {
        $columns_that_exist = array_column($this->mssql_connection->getColumns($this->schema, $this->table)->toArray(), 'COLUMN_NAME');

        if (empty($this->column_mappings)) {
            $required_columns = array_column($this->columns, 'name');
        } else {
            $mapped_columns = array_column((array) $this->column_mappings, 'destination_column');
            $required_columns = array_filter($mapped_columns, function ($col) {
                return ! in_array(trim(strtolower($col)), ['null', '']);
            });
        }

        $columns_that_dont_exist = array_filter($required_columns, function ($column) use ($columns_that_exist) {
            return ! in_array($column, $columns_that_exist) && ! in_array($column, $this->unsupported_columns);
        });

        if (count($columns_that_dont_exist) > 0) {
            $this->error_message = 'These required columns were not found in the destination table: ' . implode(', ', $columns_that_dont_exist) . '.';
            $this->abortPublishing();
        }
    }

    /**
     * Get our notification data for any notifications
     *
     * @param  object $data The data object from our publisher
     * @return array  An array of data to be appended to our notification data
     */
    public function getNotificationData($data): array
    {
        if (empty($this->destination_options)) {
            return [];
        }

        $server = PublisherTable::find($this->destination_options->server_id);

        if (empty($server)) {
            return [];
        }

        // Are we using a new database, or an existing one?
        if ($this->destination_options->using_new_database) {
            $database = mb_substr($this->destination_options->target_create_database, 0, 124);
        } else {
            $database = $this->destination_options->target_database;
        }

        $table = $this->table;

        if (empty($table)) {
            if ($this->destination_options->using_new_table) {
                $table = $this->destination_options->target_create_table;

                if (isset($this->destination_options->append_timestamp) && $this->destination_options->append_timestamp) {
                    $table = mb_substr($table, 0, 113); // Just in case we're pushing the 128 character limit of SQL server (suffix is 15 characters)
                    $table .= '_' . date('Ymdhis');
                }
            } else {
                $table = $this->destination_options->target_table;
            }
        }

        return [
            'hostname' => $server->data->hostname,
            'port'     => $server->data->port,
            'username' => $server->data->username,
            'table'    => $table
        ];
    }
}
