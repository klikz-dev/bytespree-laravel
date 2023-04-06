<?php

namespace App\Classes\Database;

use App\Models\{
    PartnerIntegration,
    PartnerIntegrationForeignDatabase,
    Server
};
use Closure;
use DB;
use Exception;
use InvalidArgumentException;

/**
 * Class description
 */
class Connection
{
    public static function connect(PartnerIntegration $database, bool $use_default_database = FALSE, ?string $app_name = NULL)
    {
        if ($use_default_database) {
            $database_name = $database->server->default_database;
        } else {
            $database_name = $database->database;
        }

        if (empty($app_name)) {
            $app_name = 'bytespree';
        }

        $app_name_sql = <<<SQL
            SET application_name TO {$app_name}
            SQL;

        $config_key = self::getConfigKey($database_name);

        if (array_key_exists($config_key, config('database.connections'))) { // Try to reuse the connection
            try {
                DB::connection($config_key)->getPdo();
                $connection = DB::connection($config_key);
                $connection->statement($app_name_sql);

                return $connection;
            } catch (Exception $e) {
                // do nothing. try to create a new connection.
            }
        }

        $config = array_merge(
            config('database.connections.pgsql'),
            [
              'host'             => $database->server->hostname,
              'username'         => $database->server->username,
              'password'         => $database->server->password,
              'port'             => $database->server->port,
              'database'         => $database_name,
              'application_name' => self::getApplicationName(),
            ]
        );

        config(['database.connections.' . $config_key => $config]);

        try {
            DB::connection($config_key)->getPdo();
        } catch ( Exception $e) {
            logger()->error("Could not connect to database {$database->database} in server {$database->server_id}.");
            throw $e;
        }

        $connection = DB::connection($config_key);
        $connection->statement($app_name_sql);

        return $connection;
    }

    public static function connectServer(Server $server)
    {
        $database_name = $server->default_database;

        $config_key = self::getConfigKey($database_name);

        if (array_key_exists($config_key, config('database.connections'))) { // Try to reuse the connection
            try {
                DB::connection($config_key)->getPdo();

                return DB::connection($config_key);
            } catch (Exception $e) {
                // do nothing. try to create a new connection.
            }
        }

        $config = array_merge(
            config('database.connections.pgsql'),
            [
                'host'             => $server->hostname,
                'username'         => $server->username,
                'password'         => $server->password,
                'port'             => $server->port,
                'database'         => $database_name,
                'application_name' => self::getApplicationName(),
            ]
        );

        config(['database.connections.' . $config_key => $config]);

        try {
            DB::connection($config_key)->getPdo();
        } catch (Exception $e) {
            logger()->error("Could not connect to server {$server->id}.");
            throw $e;
        }

        return DB::connection($config_key);
    }

    public static function getSchemaBuilder(PartnerIntegration $database)
    {
        return self::connect($database)->getSchemaBuilder();
    }

    public static function external(string $hostname, string $username, string $password, $port, string $database, string $driver, string $schema = NULL)
    {
        $base = match ($driver) {
            'mysql'    => config('database.connections.mysql'),
            'pgsql'    => config('database.connections.pgsql'),
            'postgres' => config('database.connections.pgsql'),
            'postgre'  => config('database.connections.pgsql'),
            'sqlsrv'   => config('database.connections.sqlsrv'),
            default    => throw new Exception('Invalid driver'),
        };

        $config = array_merge(
            $base,
            [
                'host'     => $hostname,
                'username' => $username,
                'password' => $password,
                'port'     => $port,
                'database' => $database,
                'schema'   => $schema,
            ]
        );

        $database_key = 'external:' . microtime(TRUE);

        config(['database.connections.' . $database_key => $config]);

        try {
            DB::connection($database_key)->getPdo();
        } catch ( Exception $e) {
            throw $e;
        }
        
        return DB::connection($database_key);
    }

    public static function getSchemas(PartnerIntegration $database, $all = FALSE)
    {
        $connection = self::connect($database);

        $where = $all ? '' : "WHERE schema_owner != 'postgres'";

        $sql = <<<SQL
            SELECT schema_owner, schema_name
            FROM information_schema.schemata
            $where
            SQL;

        return array_column($connection->select($sql), 'schema_name');
    }

    public static function createDatabase(PartnerIntegration $database, string $database_name): bool
    {
        $connection = self::connect($database, TRUE);

        $result = FALSE;

        try {
            $connection->statement("CREATE DATABASE $database_name");
            $result = TRUE;
        } catch (Exception $e) {
            logger()->error("Could not create database $database_name in server {$database->server_id}.");
        }

        $connection->disconnect();

        return $result;
    }

    public static function databaseExists(PartnerIntegration $database, string $database_name): bool
    {
        $connection = self::connect($database, TRUE);

        $result = TRUE;
        
        try {
            $results = $connection->select("SELECT datname FROM pg_database WHERE lower(datname) = lower(?)", [$database_name]);
            $connection->disconnect();

            return count($results) > 0;
        } catch (Exception $e) {
            throw new Exception("Failed to determine if database {$database_name} exists");
        }
    }

    public static function getAllDepndencies(PartnerIntegration $database)
    {
        $connection = self::connect($database);

        $sql = <<<SQL
            SELECT
                source_table.relname as source_name, 
                source_ns.nspname as source_schema,
                dependent_view.relname as "name",
                dependent_ns.nspname as "schema",
                case 
                    when dependent_view.relkind = 'm' then 'materialized' 
                    when dependent_view.relkind = 'v' then 'normal' 
                end as type
            FROM pg_depend
            JOIN pg_rewrite ON pg_depend.objid = pg_rewrite.oid
            JOIN pg_class as dependent_view ON pg_rewrite.ev_class = dependent_view.oid
            JOIN pg_class as source_table ON pg_depend.refobjid = source_table.oid
            JOIN pg_attribute ON pg_depend.refobjid = pg_attribute.attrelid AND pg_depend.refobjsubid = pg_attribute.attnum
            JOIN pg_namespace as dependent_ns ON dependent_ns.oid = dependent_view.relnamespace
            JOIN pg_namespace as source_ns ON source_ns.oid = source_table.relnamespace
            WHERE pg_attribute.attnum > 0 AND dependent_ns.nspname NOT IN ('information_schema')
            GROUP BY "name", "schema", "type", source_ns.nspname, source_table.relname
            ORDER BY "name"
            SQL;

        return $connection->select($sql);
    }

    public static function getObjectDependencies(PartnerIntegration $database, string $schema_name, string $table_name)
    {
        $dependencies = array_filter(self::getAllDepndencies($database), function ($dep) use ($schema_name, $table_name) {
            return $dep->source_schema == $schema_name && $dep->source_name == $table_name;
        });

        return array_values($dependencies);
    }

    public static function getForeignObjectDependencies(PartnerIntegration $database, string $table_name)
    {
        $foreign_dependencies = [];

        $foreign_databases = PartnerIntegrationForeignDatabase::where('foreign_control_id', $database->id)->get();
        foreach ($foreign_databases as $foreign_database) {
            $database_f = PartnerIntegration::find($foreign_database->control_id);

            $dependencies = collect(self::getAllDepndencies($database_f))->filter(function ($dep) use ($foreign_database, $table_name) {
                return $dep->source_schema == $foreign_database->schema_name && $dep->source_name == $table_name;
            })->map(function ($dep) use ($database_f) {
                $dep->foreign_database = $database_f->database;

                return $dep;
            });

            $foreign_dependencies = array_merge($foreign_dependencies, $dependencies->toArray());
        }

        return array_values($foreign_dependencies);
    }

    public static function getAllObjectParents(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $dependency_map = self::getAllDepndencies($database);

        return self::getAllObjectParentsRecursively($dependency_map, $table_name, $table_schema);
    }

    public static function getAllObjectParentsRecursively(array $dependencies, string $table_schema, string $table_name)
    {
        $parents = array_filter($dependencies, function ($dep) use ($table_name, $table_schema) {
            return $dep->name == $table_name && $dep->schema == $table_schema;
        });

        $parents_final = [];

        foreach ($parents as $parent) {
            $parents_final[] = $parent;

            $parents_final = array_merge($parents_final, self::getAllObjectParentsRecursively($dependencies, $parent->source_name, $parent->source_schema));
        }

        return $parents_final;
    }

    public static function getApplicationName() : string
    {
        $pieces = ['byt'];

        if (auth()->check()) {
            $pieces[] = auth()->user()->user_handle;
        } else {
            $pieces[] = 'unknown';
        }

        $pieces[] = app('environment')->getTeam();

        return mb_substr(implode('-', $pieces), 0, 63);
    }

    public static function needsQuotes($type)
    {
        return match ($type) {
            "bigint"                      => FALSE,
            "smallint"                    => FALSE,
            "integer"                     => FALSE,
            "int"                         => FALSE,
            "int2"                        => FALSE,
            "int4"                        => FALSE,
            "int8"                        => FALSE,
            "smallserial"                 => FALSE,
            "serial"                      => FALSE,
            "bigserial"                   => FALSE,
            "serial2"                     => FALSE,
            "serial4"                     => FALSE,
            "serial8"                     => FALSE,
            "real"                        => FALSE,
            "float4"                      => FALSE,
            "float8"                      => FALSE,
            "double precision"            => FALSE,
            "numeric"                     => FALSE,
            "decimal"                     => FALSE,
            "bool"                        => FALSE,
            "character"                   => TRUE,
            "char"                        => TRUE,
            "character varying"           => TRUE,
            "varchar"                     => TRUE,
            "double precision"            => FALSE,
            "json"                        => TRUE,
            "jsonb"                       => TRUE,
            "text"                        => TRUE,
            "date"                        => TRUE,
            "time"                        => TRUE,
            "time without time zone"      => TRUE,
            "time with time zone"         => TRUE,
            "timetz"                      => TRUE,
            "timestamp"                   => TRUE,
            "timestamp without time zone" => TRUE,
            "timestamp with time zone"    => TRUE,
            "timestamptz"                 => TRUE,  
            default                       => FALSE
        };
    }
    
    /**
     * Generate a configuration key for a database conenction
     * 
     * @param  string $database_name The name of the database (should be mostly unique)
     * @return string The configuration key that should be used so connections can be reused
     */
    public static function getConfigKey(string $database_name) : string
    {
        return 'database:' . $database_name;
    }

    /**
     * Drop a database
     * 
     * @param  PartnerIntegration $database      The database whose conenction we should use to issue the drop database statement
     * @param  string             $database_name The name of the database to drop
     * @return void
     */
    public static function dropDatabase(PartnerIntegration $database, string $database_name)
    {
        $connection = self::connect($database, TRUE);

        $sql = <<<SQL
            SELECT * FROM (
            SELECT
                pg_terminate_backend (pid) as terminated
            FROM pg_stat_activity
            WHERE pg_stat_activity.datname = ?
            ) as p
            WHERE terminated != true
            SQL;

        $connections = $connection->select($sql, [$database->database]);
        
        if ($connections != []) {
            return;
        }
        
        $sql = "DROP DATABASE IF EXISTS " . $database_name;

        $connection->statement($sql);
    }

    /**
     * Execute a closure within a transaction and rollback the transaction regardless of the outcome
     */
    public static function transactionWithRollback(PartnerIntegration $database, Closure $closure): void
    {
        $connection = self::connect($database);

        $connection->beginTransaction();

        try {
            $closure($connection);

            $connection->rollBack();
        } catch (Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    /**
     * Terminate processes that have an object and dependencies locked
     * @param  PartnerIntegration $database     Database to connect to
     * @param  string             $object_name  Name of object with locks
     * @param  array              $object_kinds (Optional) Kinds of objects to look for
     *                                          r = table,
     *                                          v = view,
     *                                          m = materialized view,
     *                                          f = foreign table
     * @return bool
     *                                         TRUE if all processes locking object were terminated or if no locks were found, otherwise FALSE
     */
    public static function terminateProcessesLockingObject(PartnerIntegration $database, $object_schema, $object_name, $object_kinds = ['r', 'v', 'm', 'f'])
    {
        $connection = self::connect($database);

        if (empty($object_kinds) && ! is_array($object_kinds)) {
            throw new InvalidArgumentException("Must provide at least one kind of object in object_kinds array");
        }

        $available = TRUE;

        array_walk($object_kinds, function (&$value, $key) {
            $value = "'$value'";
        });

        $kinds = implode(',', $object_kinds);

        $sql = <<<SQL
            select
                pg_terminate_backend(pid) terminated,
                pid,
                state,
                usename,
                query,
                query_start
            from pg_stat_activity 
            where pid in (
                select pid
                    from pg_locks l 
                    join pg_class t on l.relation = t.oid 
                    join pg_namespace n 
                    on n.oid = t.relnamespace
                        and t.relkind in ($kinds)
                    where 
                        t.relname = ?
                        and n.nspname = ?
            )
            SQL;

        $results = $connection->select($sql, [$object_name, $object_schema]);
        if ($results) {
            foreach ($results as $result) {
                if ($result->terminated === FALSE) {
                    $available = FALSE;
                    logger()->error(
                        "Could not terminate a process locking object $object_name",
                        [
                            'object_schema' => $object_schema,
                            'object_name'   => $object_name,
                            'pid'           => $result->pid,
                            'state'         => $result->state,
                            'usename'       => $result->usename,
                            'query'         => $result->query,
                            'query_start'   => $result->query_start,
                        ]
                    );
                }
            }
        }

        return $available;
    }
}
