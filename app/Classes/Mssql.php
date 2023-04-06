<?php

namespace App\Classes;

use App\Classes\Database\Connection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Support\Collection;
use Exception;

class Mssql
{
    private SqlServerConnection $db;

    public static function connect(array $config) : Mssql
    {
        return new self($config);
    }

    public static function test($hostname, $username, $password, $port) : bool
    {
        $config = [
            'host'     => $hostname,
            'port'     => $port,
            'username' => $username,
            'password' => $password,
        ];

        try {
            $self = new self($config);
        } catch (Exception $e) {
            return FALSE;
        }

        return TRUE;
    }

    public function __construct(array $config)
    {
        $this->db = $this->getConnection($config);
    }
    
    /**
     * Begins a transaction for the database
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }
    
    /**
     * Rolls the database back to before anything happened
     *
     * @return void
     */
    public function rollback()
    {
        $this->db->rollBack();
    }
    
    /**
     * Completes a transaction
     *
     * @return void
     */
    public function commit()
    {
        $this->db->commit();
    }

    /**
     * Get all databases from an SQL server
     *
     * @return array
     */
    public function getDatabases()
    {
        $databases = $this->db
            ->table('sys.databases')
            ->select('name')
            ->orderBy('name', 'asc')
            ->get();

        return $databases->pluck('name');
    }

    /**
     * Attempt to create a database on the SQL Server
     *
     * @param  string $database Name of the database to create
     * @return bool   TRUE if it appears to have been created; FALSE if not
     */
    public function createDatabase(string $database)
    {
        $sql = <<<SQL
            CREATE DATABASE [{$database}]
            SQL;

        try {
            $this->db->statement($sql);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists')) {
                return TRUE;
            }

            return FALSE;
        }

        return TRUE;
    }

    /**
     * Set our current database by using the USE statement.
     *
     * @param  string $database Name of the database
     * @return Mssql
     */
    public function setDatabase(string $database)
    {
        $sql = <<<SQL
            USE [{$database}]
            SQL;

        $query = $this->db->statement($sql);

        if (! $query) {
            throw new Exception('Connection to the remote database failed.');
        }

        return $this;
    }

    /**
     * Get the tables from our current SQL server schema
     *
     * @return Collection
     */
    public function getTables()
    {
        $tables = $this->db
            ->table('INFORMATION_SCHEMA.TABLES')
            ->selectRaw("TABLE_SCHEMA + '.' + TABLE_NAME as TABLE_NAME")
            ->orderByRaw("TABLE_SCHEMA, TABLE_NAME")
            ->get();

        return $tables->pluck('TABLE_NAME');
    }
    
    /**
     * Creates a new table in the Mssql database
     *
     * @param  string $schema        The schema to put table in
     * @param  string $table         The table we are creating
     * @param  string $column_string The columns for this table in a string format
     * @return bool
     */
    public function createTable(string $schema, string $table, string $column_string)
    {
        $sql = <<<SQL
            CREATE TABLE [{$schema}].[{$table}] (
                {$column_string}
            );
            SQL;

        return $this->db->statement($sql);
    }

    /**
     * Truncates table in the Mssql database
     *
     * @param  string $schema The schema containing the table
     * @param  string $table  The table we are truncating
     * @return bool
     */
    public function truncateTable(string $schema, string $table)
    {
        $sql = <<<SQL
            TRUNCATE TABLE [{$schema}].[{$table}]
            SQL;

        return $this->db->statement($sql);
    }

    /**
     * Does a table exist within a SQL Server database?
     *
     * @param  string $schema   The schema to look in
     * @param  string $table    The table to look for
     * @param  string $database The database to test
     * @return bool   TRUE if it exists; FALSE if not
     */
    public function tableExists(string $schema, string $table, string $database): bool
    {
        $tables = $this->db
            ->table('INFORMATION_SCHEMA.TABLES')
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('TABLE_CATALOG', $database)
            ->get();

        if ($tables->count() == 0) {
            return FALSE;
        }
  
        return TRUE;
    }

    public function insert(string $schema, string $table, object|array $inserts)
    {
        $this->db->table("{$schema}.{$table}")->insert($inserts);
    }

    /**
     * Get the columns for a given table_name
     *
     * @param  string     $schema The schema containing the table
     * @param  string     $table  The table to inquire about
     * @return Collection Collection (with info) from our table's information_schema
     */
    public function getColumns(string $schema, string $table)
    {
        return $this->db
            ->table('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->get();
    }

    /**
     * Get our converted type (pgsql => sql server)
     *
     * @param  string     $type       Pgsql Type of the column
     * @param  bool       $use_length Should we use length(width) options? e.g. in create syntax
     * @param  int|string $length     Prepopulate the length(width) value
     * @return string
     */
    public function getConvertedType(string $type, bool $use_length = FALSE, int|string $length = NULL)
    {
        $type = strtolower(trim($type));

        $types = [
            'bigint'                      => 'bigint',
            'bool'                        => 'bit',
            'boolean'                     => 'bit',
            'int4'                        => 'int',
            'int8'                        => 'int',
            'integer'                     => 'int',
            'char'                        => 'nchar',
            'character'                   => 'nchar',
            'character varying'           => ['varchar', 'varchar (%s)'],
            'date'                        => 'date',
            'decimal'                     => 'decimal',
            'double precision'            => 'float',
            'json'                        => ['nvarchar', 'nvarchar(max)'],
            'jsonb'                       => ['nvarchar', 'nvarchar(max)'],
            'money'                       => 'smallmoney',
            'numeric'                     => 'numeric',
            'smallint'                    => 'tinyint',
            'time'                        => 'time',
            'time without time zone'      => 'time',
            'timestamp'                   => ['datetime2', 'datetime2(6)'],
            'timestamp without time zone' => ['datetime2', 'datetime2(6)'],
            'timestamp with time zone'    => ['datetime2', 'datetimeoffset(0)'],
            'text'                        => 'text',
            'uuid'                        => 'uniqueidentifier',
            'varchar'                     => ['varchar', 'varchar (%s)'],
            'custom'                      => ['nvarchar', 'nvarchar(max)']
        ];

        if (! array_key_exists($type, $types)) {
            return 'unsupported';
        }

        if (! is_array($types[$type])) {
            return $types[$type];
        }

        if (! $use_length) {
            return $types[$type][0];
        }

        return sprintf($types[$type][1], $length);
    }

    protected function getConnection($config) : SqlServerConnection
    {
        if (empty($config['database']) || $config['database'] === 'forge') {
            $config['database'] = 'master';
        }

        return Connection::external(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['port'],
            $config['database'],
            'sqlsrv'
        );
    }
}