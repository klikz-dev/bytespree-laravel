<?php

namespace App\Console\Commands\Connector;

use App\Models\PartnerIntegration;
use Illuminate\Console\Command;
use Exception;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use SingerPhp\SingerParser;
use SingerPhp\Singer;
use SingerPhp\Messages\{DeleteRecordMessage, MetaMessage, RecordMessage, SchemaMessage, StateMessage, TableActionMessage};
use App\Classes\Database\{Connection, Insert, Table, View};
use App\Models\Event;
use App\Events\Connector\{RebuildTableStarted, RebuildTable};
use App\Models\Manager\JenkinsBuild;
use Illuminate\Database\Schema\Blueprint;

class Run extends Command
{
    protected $signature = 'connector:run {action} {database_id} {--table=} {--build_id=} {--log}';

    protected $description = 'Run a connector';

    protected $build_id;
    protected $table;
    protected $normalized_table;
    protected $database;
    protected $database_id;
    protected $database_table;
    protected $connection;
    protected $supported_tables = [];
    protected $supported_columns = [];
    protected $table_recreated = FALSE;
    protected $table_full_replace = FALSE;
    protected $enforce_unique_keys = FALSE; // Should we enforce unique key constraints?
    protected $unique_keys = []; // Our unique keys
    protected $delete_keys = []; // Keys used to delete from
    protected $sort_column = NULL;
    private $output_log;
    private $output_log_file = NULL;
    private bool $output_log_enabled = FALSE;
    private $config_file = '';
    private $catalog_file = '';
    private $batch_count = 25;
    private $state_file;

    public function handle()
    {
        $this->output_log_enabled = $this->option('log');
        $this->database_id = (int) $this->argument('database_id');
        $this->database = PartnerIntegration::find($this->database_id);
        $this->build_id = $this->getJenkinsBuildId();

        if (! empty($this->option('table'))) {
            $this->table = $this->option('table');
            $this->normalized_table = $this->normalizeTableName($this->table);
        }

        if (! $this->database) {
            throw new Exception("Database not found");
        }

        $this->database_table = $this->database->tables->where('name', $this->table)->first();

        if ($this->database->server->inMaintenanceWindow()) {
            echo "Ending due to maintenance window\n";
            // todo: send notifications
            exit(1);
        }

        if (env('JENKINS_HOME')) {
            $this->runMonitor($this->argument('action'));
        }

        logger()->debug("> START RUN " . $this->argument('action') . ": " . $this->database->name . " - " . $this->table);

        match ($this->argument('action')) {
            'build' => $this->build(),
            'test'  => $this->test(),
            'sync'  => $this->sync(),
            default => throw new Exception('Invalid action provided.'),
        };

        $this->afterRun();
        
        logger()->debug("> END RUN " . $this->argument('action') . ": " . $this->database->name . " - " . $this->table);

        return 0;
    }

    public function build()
    {
        $tap_uses_tables = TRUE;
        if (empty($this->table)) {
            $tap_uses_tables = FALSE;
        }

        Event::buildStart($this->database, [$this->database_id, $this->table]);

        $this->writeConfigurationFile();

        $this->writeCatalogFile();

        $this->initOutputLog('build');

        $args = [
            'php',
            $this->database->connectorTap(),
            "--discover",
            "--config={$this->config_file}",
            "--catalog={$this->catalog_file}"
        ];

        $message = "> RUN: " . $this->database->name;
        if (! empty($this->table)) {
            $message .= " - " . $this->table;
        }
        $message .= ' CALLING: ' . implode(' ', $args);
        logger()->debug($message);
        
        $process = new Process($args);

        $process->setWorkingDirectory(dirname($this->database->connectorTap()));
        
        $schema_message_found = FALSE;

        $process->run(function ($type, $buffer) use (&$schema_message_found, $tap_uses_tables) {
            if (Process::ERR === $type) {
                $this->afterError();
                throw new Exception('Build failed: ' . $buffer);
            }
            if (Process::OUT === $type) {
                $lines = explode(PHP_EOL, $buffer);
                foreach ($lines as $line) {
                    $this->writeOutputLog($line);
                    if (! is_null($line) && ! empty($line)) {
                        $message = SingerParser::parseMessage($line);

                        if ($message instanceof SchemaMessage) {
                            if ($schema_message_found && $this->table == $message->stream) {
                                // throw new Exception('Multiple schema messages found.');
                                $this->compareTableColumns($message);
                            } else {
                                if ($this->table != $message->stream) {
                                    $this->table = $message->stream;
                                    $this->normalized_table = $this->normalizeTableName($message->stream);
                                }

                                $this->buildTableFromSchemaMessage($message);

                                if ($tap_uses_tables) {
                                    $schema_message_found = TRUE;
                                }
                            }
                        }

                        if ($message instanceof MetaMessage) {
                            if ($message->metadata && property_exists($message->metadata, 'unique_keys')) {
                                if (is_array($message->metadata->unique_keys)) {
                                    $this->unique_keys = $message->metadata->unique_keys;
                                } else {
                                    $this->unique_keys = [$message->metadata->unique_keys];
                                }

                                if (empty($this->unique_keys)) {
                                    $this->enforce_unique_keys = FALSE;
                                } else {
                                    $this->enforce_unique_keys = TRUE;
                                }
                            }
                        }

                        if ($message instanceof StateMessage) {
                            $this->updateState($message);
                            continue;
                        }

                        if ($message === NULL && ! empty($line)) {
                            if (substr($line, 0, 4) === 'INFO') {
                                echo $line . PHP_EOL;
                            }
                        }
                    }
                }
            }
        });

        Event::buildEnd($this->database, [$this->database_id, $this->table]);
    }

    public function sync()
    {
        Event::integrationStart($this->database, [$this->database_id, $this->table]);

        if (! empty($this->table)) {
            $this->supported_columns = $this->getTableColumns();
            $this->checkTableSettingValues();
        }

        $this->writeConfigurationFile();

        $this->writeCatalogFile();

        $this->writeStateFile();

        $this->initOutputLog('sync');

        $args = [
            'php',
            $this->database->connectorTap(),
            "--config={$this->config_file}",
            "--catalog={$this->catalog_file}",
            "--state={$this->state_file}"
        ];

        logger()->debug("> RUN: " . $this->database->name . " - " . $this->table . ' CALLING: ' . implode(' ', $args));

        $process = new Process($args);

        $process->setWorkingDirectory(dirname($this->database->connectorTap()));

        $process->setTimeout(0);

        $this->connection = Connection::connect(
            database: $this->database,
            app_name: 'byt_sync_' . $this->table
        );

        $record_count = 0;

        $records_to_insert = [];

        $process->run(function ($type, $buffer) use (&$record_count, &$records_to_insert) {
            if (Process::ERR === $type) {
                $this->afterError();
                throw new Exception('Sync failed: ' . $buffer);
            }
            if (Process::OUT === $type) {
                $lines = explode(PHP_EOL, $buffer);

                foreach ($lines as $line) {
                    $this->writeOutputLog($line);

                    if (is_null($line) || empty($line)) {
                        continue;
                    }
                    
                    $message = SingerParser::parseMessage($line);

                    if ($message instanceof MetaMessage) {
                        if ($message->metadata && property_exists($message->metadata, 'stream')) {
                            if ($this->table != $message->metadata->stream) {
                                if (count($records_to_insert) > 0) {
                                    $this->insertRecords($records_to_insert);
                                    $records_to_insert = [];
                                }

                                $this->table = $message->metadata->stream;
                                $this->normalized_table = $this->normalizeTableName($message->metadata->stream);
                                $this->unique_keys = [];
                                $this->delete_keys = [];
                                $this->table_recreated = FALSE;

                                // Get our current database structure
                                $this->supported_columns = $this->getTableColumns();
                            }
                        }

                        if ($message->metadata && property_exists($message->metadata, 'unique_keys')) {
                            if (is_array($message->metadata->unique_keys)) {
                                $this->unique_keys = $message->metadata->unique_keys;
                            } else {
                                $this->unique_keys = [$message->metadata->unique_keys];
                            }
                            
                            if (! empty($this->unique_keys)) {
                                $this->checkForUnqiueKeys();
                                $this->enforce_unique_keys = TRUE;
                            }
                        }

                        if ($message->metadata && property_exists($message->metadata, 'delete_keys')) {
                            if (is_array($message->metadata->delete_keys)) {
                                $this->delete_keys = $message->metadata->delete_keys;
                            } else {
                                $this->delete_keys = [$message->metadata->delete_keys];
                            }
                        }
                    }

                    if ($message instanceof TableActionMessage) {
                        $this->table = $message->stream;
                        $this->normalized_table = $this->normalizeTableName($message->stream);
                        $this->handleTableAction($message);
                        continue;
                    }

                    if ($message instanceof RecordMessage) {
                        if ($this->table != $message->stream) {
                            $this->table = $message->stream;
                            $this->normalized_table = $this->normalizeTableName($message->stream);

                            // Get our current database structure
                            $this->supported_columns = $this->getTableColumns();
                        }

                        $records_to_insert[] = $message;

                        ++$record_count;

                        if (count($records_to_insert) >= $this->batch_count) {
                            $this->insertRecords($records_to_insert);
                            $records_to_insert = [];
                        }

                        continue;
                    }

                    if ($message instanceof DeleteRecordMessage) {
                        $this->deleteRecord($message);
                        continue;
                    }

                    if ($message instanceof SchemaMessage) {
                        if (($this->database->integration->fully_replace_tables === TRUE || $this->table_full_replace === TRUE) && ! $this->table_recreated) {
                            $this->rebuildTableFromSchemaMessage($message);
                        } else {
                            $this->compareTableColumns($message);
                        }
                        continue;
                    }

                    if ($message instanceof StateMessage) {
                        $this->updateState($message);
                        continue;
                    }

                    if ($message === NULL && ! empty($line)) {
                        if (substr($line, 0, 4) === 'INFO') {
                            echo $line . PHP_EOL;
                        }
                    }
                }
            }
        });

        if (count($records_to_insert) > 0) {
            $this->insertRecords($records_to_insert);
            $records_to_insert = [];
        }

        Event::download($this->database, $record_count, [$this->database_id, $this->table]); // put it in the batch

        $this->closeOutputLog();

        Event::integrationEnd($this->database, [$this->database_id, $this->table]);
    }

    public function test()
    {
        $settings = $this->database->getKeyValueSettings();

        $args = [
            'php',
            $this->database->connectorTap(),
            '--metadata',
            "--method=test",
            "--input=" . json_encode((object) $settings),
        ];

        logger()->debug("> RUN: " . $this->database->name . " - " . $this->table . ' CALLING: ' . implode(' ', $args));

        $process = new Process($args);

        $process->setWorkingDirectory(dirname($this->database->connectorTap()));

        try {
            $process->mustRun();

            $output = $process->getOutput();
        } catch (ProcessFailedException $exception) {
            throw new Exception($exception->getMessage());
        }

        $message = app(SingerParser::class)->parseMessage($output);

        if ($message instanceof MetaMessage) {
            try {
                if (property_exists($message, 'metadata') && property_exists($message->metadata, 'test_result')) {
                    if ($message->metadata->test_result === TRUE) {
                        return TRUE;
                    }

                    throw new Exception('Connector test has failed.');
                }
            } catch (Exception $e) {
                throw new Exception('Invalid message returned from connector. Message did not return test_result.');
            }
        } else {
            throw new Exception('Invalid message returned from connector. Message was not of type Meta.');
        }
    }

    /**
     * Launch our run monitor to watch over our Jenkins job
     */
    public function runMonitor(string $method)
    {
        $build = JenkinsBuild::create([
            'jenkins_build_id' => $this->build_id,
            'job_path'         => config('services.jenkins.job_name'),
            'jenkins_home'     => config('services.jenkins.jenkins_home'),
            'started_at'       => now(),
            'parameters'       => [
                'artisan',
                'Services',
                'Run',
                strtolower($method),
                $this->database->id
            ]]);

        logger()->info("Launching monitor for build {$build->id}");

        $application_path = rtrim(base_path(), "/");

        exec("JENKINS_BUILD_ID=dontKillMe BUILD_ID=dontKillMe nohup php $application_path/artisan jenkins:monitor {$build->id} --type=connector >/dev/null 2>&1 &");
    }

    /**
     * Build a database table from a schema message
     */
    public function buildTableFromSchemaMessage(SchemaMessage $schema_message)
    {
        if (! property_exists($schema_message->schema, 'properties')) {
            throw new Exception('Schema message does not have any columns.');
        }

        // todo dump this elsewhere
        $columns = collect($schema_message->schema->properties)->mapWithKeys(function ($column, $column_name) {
            return [$column_name => $this->getColumnType($column->type)];
        });

        if (property_exists($schema_message, 'keyProperties') && is_array($schema_message->keyProperties)) {
            $indexes = $schema_message->keyProperties;
        } else {
            $indexes = [];
        }

        $connection = Connection::connect(
            database: $this->database,
            app_name: 'byt_sync_' . $this->table
        );

        $connection->transaction(function () use ($columns, $connection) {
            $connection->getSchemaBuilder()
                ->create($this->normalized_table, function (Blueprint $table) use ($columns) {
                    if (! $columns->has('dmi_id')) {
                        $table->bigIncrements('dmi_id');
                    }
                    
                    if (! $columns->has('dmi_created')) {
                        $table->dateTime('dmi_created')->nullable();
                    }

                    $table->boolean('dmi_deleted')->default(FALSE);

                    if (! $columns->has('dmi_status')) {
                        $table->string('dmi_status', 20)->nullable();
                    }

                    foreach ($columns as $column_name => $column_type) {
                        $table->addColumn($column_type, $column_name)->nullable();
                    }
                });
        });

        if ($this->enforce_unique_keys) {
            $connection->transaction(function () use ($connection) {
                $connection->getSchemaBuilder()
                    ->table($this->normalized_table, function (Blueprint $table) {
                        $table->unique($this->unique_keys);

                        $indexed_columns = implode(', ', $this->unique_keys);
                        echo "Creating unique index for columns: {$indexed_columns}\n";
                    });
            });
        }

        if (! empty($indexes)) {
            $connection->transaction(function () use ($connection, $indexes) {
                $connection->getSchemaBuilder()
                    ->table($this->normalized_table, function (Blueprint $table) use ($indexes) {
                        $counter = 0;
                        foreach ($indexes as $index) {
                            ++$counter;

                            $index_name = substr($this->normalized_table . '_idx' . str_pad($counter, 2, '0', STR_PAD_LEFT), 0, 63);
                            $table->index($index, $index_name);

                            echo "Creating index for column: {$index}\n";
                        }
                    });
            });
        }

        $this->supported_columns = $this->getTableColumns();
    }

    /**
     * Map the column type from the schema message to a database (postgres) column type.
     * 
     * @param $column_type The column type from the schema message
     * @return string    the database column type
     * @throws Exception if column type is unsupported or invalid
     */
    public function getColumnType(mixed $column_type) : string
    {
        return match ($column_type) {
            Singer::TYPE_STRING      => 'text',
            Singer::TYPE_VARCHAR     => 'text',
            Singer::TYPE_BOOLEAN     => 'boolean',
            Singer::TYPE_INTEGER     => 'bigInteger',
            Singer::TYPE_NUMBER      => 'numeric',
            Singer::TYPE_ARRAY       => 'jsonb',
            Singer::TYPE_JSON        => 'jsonb',
            Singer::TYPE_OBJECT      => 'jsonb',
            Singer::TYPE_DATE        => 'date',
            Singer::TYPE_DATETIME    => 'datetime',
            Singer::TYPE_TIME        => 'time',
            Singer::TYPE_TIMESTAMP   => 'timestamp',
            Singer::TYPE_TIMESTAMPTZ => 'timestamp',
            Singer::TYPE_FLOAT       => 'numeric',
            Singer::TYPE_CURRENCY    => 'numeric',
            Singer::TYPE_REAL        => 'real',
            Singer::TYPE_REALFLOAT   => 'doublePrecision',
            Singer::TYPE_MONEY       => 'money',
            Singer::TYPE_TINYINT     => 'smallInteger',
            default                  => throw new Exception("Unsupported column type: {$column_type}")
        };
    }

    /**
     * Insert a batch of records
     */
    public function insertRecords(array $messages = [])
    {
        $insertable = [];
        $table = '';

        foreach ($messages as $message) {
            /** @var RecordMessage $message */
            $data = $message->record;
            $table = $this->normalizeTableName($message->stream);

            foreach ($data as $key => $value) {
                if (! in_array($key, $this->supported_columns)) {
                    unset($data[$key]);
                }

                if (is_array($value) || is_object($value)) {
                    $data[$key] = json_encode($value);
                }
            }

            if (! array_key_exists('dmi_id', $data)) {
                $time_extracted = (string) $message->timeExtracted;
                if (in_array('dmi_created', $this->supported_columns)) {
                    $data['dmi_created'] = $time_extracted;
                }
                if (in_array('dmi_created_at', $this->supported_columns)) {
                    $data['dmi_created_at'] = $time_extracted;
                }
                if (in_array('dmi_last_updated_at', $this->supported_columns)) {
                    $data['dmi_last_updated_at'] = $time_extracted;
                }
                if (in_array('dmi_status', $this->supported_columns)) {
                    $data['dmi_status'] = NULL;
                }
                if (in_array('dmi_deleted', $this->supported_columns)) {
                    $data['dmi_deleted'] = FALSE;
                }
            }

            $insertable[] = $data;
        }

        if (! empty($insertable) && ! empty($table)) {
            $unique_keys = $this->unique_keys;
            if (in_array('dmi_created_at', $this->supported_columns) && in_array('dmi_last_updated_at', $this->supported_columns)) {
                $unique_keys[] = 'dmi_created_at';
            }
            if (! empty($this->unique_keys)) {
                $columns = array_diff(
                    array_keys($insertable[0]),
                    $this->unique_keys
                );

                // see https://laravel.com/docs/9.x/eloquent#upserts. Makes use of the "on conflict" clause
                // todo: rechunk the insertable array because the query size will grow a bit with the on conflict clause
                foreach (Insert::makeSafeForUpsert($insertable, $this->unique_keys) as $chunked_insert) {
                    if (! empty($chunked_insert)) {
                        $this->connection->table($table)->upsert(
                            $chunked_insert,
                            $this->unique_keys,
                            $columns
                        );
                    }
                }
            } else if (! empty($this->delete_keys)) {
                if (count($this->delete_keys) == 1) {
                    $delete_query = $this->connection->table($table);

                    foreach ($this->delete_keys as $key) {
                        $values = array_column($insertable, $key);
                        $delete_query->whereIn($key, $values);
                    }

                    $delete_query->delete();

                    $this->connection->table($table)->insert($insertable);
                } else {
                    foreach ($insertable as $insert) {
                        $delete_query = $this->connection->table($table);

                        foreach ($this->delete_keys as $key) {
                            $delete_query->where($key, $insert[$key]);
                        }

                        $delete_query->delete();

                        $this->connection->table($table)->insert($insert);
                    }
                }
            } else {
                $this->connection->table($table)->insert($insertable);
            }
        }
    }

    public function normalizeTableName($table)
    {
        $normalized_table = $table;
        if (empty($this->supported_tables)) {
            $this->supported_tables = $this->getTables();
        }

        if (! in_array($table, $this->supported_tables)) {
            $normalized_table = strtolower($table);
            if (! in_array($normalized_table, $this->supported_tables)) {
                return $table;
            }
        }

        return $normalized_table;
    }

    public function getTables()
    {
        $tables = collect(Table::list($this->database, ['public']));
        $tables = $tables->toArray();

        if (! is_array($tables)) {
            throw new Exception("Returned tables was not an array.");
        }

        return array_column($tables, 'table_name');
    }

    public function getTableColumns()
    {
        $columns = Table::columns($this->database, 'public', $this->normalized_table);

        if (! is_array($columns)) {
            throw new Exception("Returned columns was not an array.");
        }

        return array_column($columns, 'column_name');
    }

    /**
     * Compare table columns to the schema message and add any missing columns.
     */
    public function compareTableColumns(SchemaMessage $schema_message)
    {
        $columns_to_add = collect($schema_message->schema->properties)->filter(function ($column_type, $column_name) {
            return ! in_array($column_name, $this->supported_columns);
        })->mapWithKeys(function ($column, $column_name) {
            return [$column_name => $this->getColumnType($column->type)];
        })->toArray();

        if (count($columns_to_add) === 0) {
            return;
        }

        $connection = Connection::connect(
            database: $this->database,
            app_name: 'byt_sync_' . $this->table
        );
        
        $connection->getSchemaBuilder()
            ->table($schema_message->stream, function (Blueprint $table) use ($columns_to_add) {
                foreach ($columns_to_add as $column_name => $column_type) {
                    echo "Found new column: {$column_name} ({$column_type})" . PHP_EOL;

                    $table->addColumn($column_type, $column_name)->nullable();
                }
            });

        $this->supported_columns = $this->getTableColumns();
    }

    /**
     * Check if the unqiue keys exist for a table and create them if they don't
     */
    public function checkForUnqiueKeys()
    {
        $connection = Connection::connect(
            database: $this->database,
            app_name: 'byt_sync_' . $this->table
        );
        // Laravel always lowercases constraints
        $table = strtolower($this->table);
        $unique_key_string = "{$table}_";

        foreach ($this->unique_keys as $unique_key) {
            $unique_key_string .= "{$unique_key}_";
        }

        $unique_key_string .= "unique";

        $unique_key_string = strtolower($unique_key_string);

        $sql = <<<SQL
            SELECT con.conname as unique_key
            FROM pg_catalog.pg_constraint con
                    INNER JOIN pg_catalog.pg_class rel
                            ON rel.oid = con.conrelid
                    INNER JOIN pg_catalog.pg_namespace nsp
                            ON nsp.oid = connamespace
            WHERE nsp.nspname = 'public' AND 
                rel.relname = '$this->normalized_table' AND
                con.contype = 'u'
            SQL;

        $constraints = $connection->select($sql);

        foreach ($constraints as $constraint) {
            if ($constraint->unique_key == $unique_key_string) {
                return;
            }
        }

        $connection->transaction(function () use ($connection) {
            $connection->getSchemaBuilder()
                ->table($this->normalized_table, function (Blueprint $table) {
                    $table->unique($this->unique_keys);
                });
        });
    }

    /**
     * Initialize our output log so that we can write to it.
     */
    public function initOutputLog(string $output_filename)
    {
        if (! $this->output_log_enabled) {
            return;
        }

        $team = app('environment')->getTeam();
        $build_id = $this->getJenkinsBuildId();

        $path = config('app.connector_path') . "/output/{$team}/{$this->database->database}/{$output_filename}/{$this->table}/{$build_id}";

        $this->ensurePathExists($path);

        $this->output_log_file = $path . '/output.tmp';
        $this->output_log = fopen($this->output_log_file, 'w');
    }

    /**
     * Write output to our connector log file
     */
    public function writeOutputLog($line)
    {
        if (! $this->output_log_enabled) {
            return;
        }

        if (! is_string($line)) {
            return;
        }

        if (empty($line)) {
            return;
        }

        fwrite($this->output_log, $line . PHP_EOL);
    }

    /**
     * Close our output.log file for the connector.
     */
    public function closeOutputLog() : void
    {
        if (! $this->output_log_enabled) {
            return;
        }

        if (is_resource($this->output_log)) {
            fclose($this->output_log);
            $new_name = str_replace('.tmp', '.log', $this->output_log_file);
            if (rename($this->output_log_file, $new_name)) {
                $this->output_log_file = $new_name;
            }
        }
    }

    /**
     * Ensure a path exists, if it doesn't, create it with appropriate 755 permissions
     */
    private function ensurePathExists(string $path) : void
    {
        $built_path = '/';

        $folders = explode('/', $path);

        foreach ($folders as $folder) {
            $built_path .= $folder . '/';

            if (! is_dir($built_path)) {
                mkdir($built_path, 0775, TRUE);
            }
        }
    }

    /**
     * Write a configuration file for our current connector's process
     * 
     * @return void
     */
    private function writeConfigurationFile()
    {
        $path = $this->getWorkspacePath();
        $path .= '/config.json';

        $config = [
            'app_env'  => app()->environment(),
            'database' => $this->database->database,
            'table'    => $this->table,
            'columns'  => $this->supported_columns
        ];

        if (! empty($this->table)) {
            $config['table_settings'] = [
                $this->table => $this->database->getKeyValueTableSettings($this->table)
            ];
        }

        $this->database->load(['settings', 'settings.setting']);

        $config = array_merge(
            $config,
            $this->database->getKeyValueSettings()
        );

        $fp = fopen($path, 'w');
        fwrite($fp, json_encode($config, JSON_PRETTY_PRINT));
        fclose($fp);

        $this->config_file = $path;
    }

    /**
     * Write the necessary catalog file for our current process
     *
     * @return void
     */
    private function writeCatalogFile()
    {
        $path = $this->getWorkspacePath();
        $path .= '/catalog.json';

        $catalog = (object) [
            'streams' => [
                (object) [
                    'stream'        => $this->table,
                    'tap_stream_id' => $this->table,
                    'schema'        => (object) [
                        "type"                 => ["null", "object"],
                        "additionalProperties" => FALSE,
                        "properties"           => '*'
                    ]
                ]
            ]
        ];

        $fp = fopen($path, 'w');
        fwrite($fp, json_encode($catalog, JSON_PRETTY_PRINT));
        fclose($fp);

        $this->catalog_file = $path;
    }

    private function writeStateFile()
    {
        $path = $this->getWorkspacePath();
        $path .= '/state.json';

        if (empty($this->database_table)) {
            $last_started = '0001-01-01 00:00:00.000';
        } else {
            $last_started = $this->database_table->last_started;
        }

        if (! $this->database->integration->fully_replace_tables && ! $this->table_full_replace && $this->database->integration->use_tables) {
            echo "Last started date for {$this->table}: {$last_started}\n";
        }

        $state = (object) [
            'bookmarks' => [
                $this->table => (object) [
                    'last_started' => $last_started
                ]
            ]
        ];

        $fp = fopen($path, 'w');
        fwrite($fp, json_encode($state, JSON_PRETTY_PRINT));
        fclose($fp);

        $this->state_file = $path;
    }

    public function updateState(StateMessage $message)
    {
        if (! $this->database_table) {
            return;
        }

        $this->database_table->update([
            'last_started' => $message->bookmarks[$this->table]['last_started']
        ]);
    }

    /**
     * Attempt to rebuild the table from a SchemaMessage (drop it and its dependencies, recreating them)
     */
    public function rebuildTableFromSchemaMessage(SchemaMessage $message)
    {
        logger()->info("Dropping {$this->normalized_table}, if it exists");

        $schemaObject = new SchemaObject($this->database, 'public', $this->table);

        // Dependency definitions will be added to schemaObject metadata
        RebuildTableStarted::dispatch($schemaObject);

        Table::drop($this->database, 'public', $this->normalized_table);

        logger()->info("Rebuild the table, {$this->normalized_table}");

        $this->buildTableFromSchemaMessage($message);

        RebuildTable::dispatch($schemaObject);

        $this->table_recreated = TRUE;
    }

    /**
     * Checks for certain table level settings
     */
    public function checkTableSettingValues()
    {
        $table_settings = $this->database->getKeyValueTableSettings($this->table);

        if (empty($table_settings)) {
            return;
        }

        if (array_key_exists('full_replace', $table_settings)) {
            $this->table_full_replace = $table_settings['full_replace'] == "1";
        }
    }

    /**
     * Delete a record from our database table using a DeleteRecordMessage object
     */
    public function deleteRecord(DeleteRecordMessage $message)
    {
        if (empty($message->record)) {
            return;
        }

        $query = $this->connection->table($message->stream);
        
        foreach ($message->record as $key => $value) {
            $query->where($key, $value);
        }

        if (property_exists($message, 'softDelete') && $message->softDelete === TRUE) {
            $query->update(['dmi_deleted' => TRUE]);
        } else {
            $query->delete();
        }
    }

    /**
     * Get a unique build id for this process
     *
     * @return string
     */
    public function getJenkinsBuildId()
    {
        if (empty($this->option('build_id'))) {
            $this->build_id = config('services.jenkins.build_id');
        } else {
            $this->build_id = $this->option('build_id');
        }

        if (empty($this->build_id)) {
            $workspace_path = $this->getWorkspacePath();
            $build_id_file = "{$workspace_path}/build_id.txt";
            if (file_exists($build_id_file)) {
                $this->build_id = intval(file_get_contents($build_id_file)) + 1;
            } else {
                $this->build_id = 1;
            }
            file_put_contents($build_id_file, $this->build_id);
        }

        return $this->build_id;
    }

    /**
     * Get a path to the workspace (Jenkins workspace or /tmp/taps)
     *
     * @return string
     */
    public function getWorkspacePath()
    {
        $path = config('services.jenkins.workspace');

        if (empty($path)) {
            $team = app('environment')->getTeam();
            $temp_dir = "/tmp/taps/{$team}/{$this->database->database}";
            if (! is_dir($temp_dir)) {
                mkdir($temp_dir, 0775, TRUE);
            }
            $path = $temp_dir;
        }

        return rtrim($path, '/');
    }

    /**
     * Execute a table action
     * 
     * @throws Exception if the action is not supported
     */
    public function handleTableAction(TableActionMessage $message): void
    {
        if ($message->action === 'drop') {
            throw new Exception("The drop action is not yet supported.");
            // todo: drop the table
            return;
        }

        if ($message->action === 'truncate') {
            echo "Truncating table {$this->normalized_table}" . PHP_EOL;

            Connection::connect(
                database: $this->database,
                app_name: 'byt_sync_' . $this->table
            )->statement("TRUNCATE {$this->normalized_table} RESTART IDENTITY");

            return;
        }

        throw new Exception("The table action, \"{$message->action}\", supplied is not supported.");
    }

    /**
     * Handle any cleanup necessary after running the tap
     */
    private function afterRun(): void
    {
        $this->compressOutputFile();
    }

    /**
     * Handle any cleanup necessary after encountering an error while running the tap
     */
    private function afterError(): void
    {
        $this->compressOutputFile();
    }

    /**
     * Compress the output file via system's gzip command; also deletes the original file once done.
     * Use gzip -dk output.log.gz to decompress the file and keep the compressed version.
     */
    private function compressOutputFile(): void
    {
        if (! $this->output_log_enabled) {
            return;
        }

        if (! empty($this->output_log_file) && file_exists($this->output_log_file)) {
            $target_file = $this->output_log_file . '.gz';
            $i = 0;
            $suffix = '.gz';
            while (file_exists($target_file)) {
                $suffix = ".{$i}.gz";
                $target_file = "{$this->output_log_file}{$suffix}";
                ++$i;
            }
            (new Process(['gzip', '-9', '-S', $suffix, $this->output_log_file]))
                ->setTimeout(3600)
                ->mustRun();
        }
    }
}
