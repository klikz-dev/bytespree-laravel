<?php

namespace App\Classes\Database;

use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationForeignDatabase;
use App\Models\Explorer\ProjectForeignDatabase;
use Exception;

/**
 * Class description
 */
class ForeignDatabase extends Connection
{
    public static function create(PartnerIntegration $database, PartnerIntegration $foreign_database, string $server_name, string $schema_name = "")
    {
        $connection = self::connect($database);
        $fetch_size = config('database.connections.pgsql.fdw_fetch_size');

        if (empty($fetch_size)) {
            throw new Exception("FDW_FETCH_SIZE is not set in the enviroment variable file.");
        }

        if (empty($schema_name)) {
            $schema_name = $server_name;
        }

        try {
            $sql = <<<SQL
                CREATE EXTENSION IF NOT EXISTS postgres_fdw;
                SQL;

            $connection->statement($sql);

            $sql = <<<SQL
                CREATE SERVER "$server_name"
                    FOREIGN DATA WRAPPER postgres_fdw
                    OPTIONS (host '{$foreign_database->server->hostname}', port '{$foreign_database->server->port}', dbname '{$foreign_database->database}', fetch_size '$fetch_size');
                SQL;

            $connection->statement($sql);

            $sql = <<<SQL
                CREATE USER MAPPING FOR {$database->server->username}
                    SERVER "$server_name"
                    OPTIONS (user '{$foreign_database->server->username}', password '{$foreign_database->server->password}');
                SQL;

            $connection->statement($sql);

            $sql = <<<SQL
                CREATE SCHEMA IF NOT EXISTS "$schema_name"
                SQL;

            $connection->statement($sql);

            $sql = <<<SQL
                IMPORT FOREIGN SCHEMA public
                    FROM SERVER "$server_name"
                    INTO "$schema_name"
                SQL;

            $connection->statement($sql);

            // todo circle back to this when data lake is fully finished
            // $user = $this->BP_ProjectsModel->checkReadOnlyUser($control_id, $partner_integration["database"]);
            // if (empty($user) == FALSE) {
            //     $sql = <<<SQL
            //         CREATE USER MAPPING FOR {$user["username"]}
            //             SERVER "{$server_name}"
            //             OPTIONS (user '{$foreign_partner_integration["username"]}', password '{$foreign_partner_integration["password"]}');
            //         SQL;
    
            //     $connection->statement($sql);
            //     $this->BP_ProjectsModel->grantUserAccessToSchema($control_id, $user["username"], $schema_name);
            // }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
            logger()->error($e->getMessage(), compact("database", "foreign_database", "server_name", "schema_name"));

            return FALSE;
        }

        return TRUE;
    }

    public static function addTable(PartnerIntegration $database, string $name, string $orig_name = "")
    {
        foreach ($database->foreign_databases() as $foreign_database) {
            $database_f = PartnerIntegration::find($foreign_database->control_id);
            $foreign_connection = self::connect($database_f);
            
            $table_check = Table::get($database_f, $foreign_database->schema_name, $name);

            if (empty($table_check)) {
                $sql = <<<SQL
                    IMPORT FOREIGN SCHEMA public
                        LIMIT TO ("$name")
                        FROM SERVER "$foreign_database->foreign_server_name"
                        INTO "$foreign_database->schema_name"
                    SQL;

                try {
                    $foreign_connection->statement($sql);
                } catch (Exception $e) {
                    $message = $e->getMessage();

                    // Is postgres telling us the table already exists in the foreign schema? If not, throw the exception
                    if (strpos($message, "already exists") === FALSE) {
                        throw $e;
                    }
                }

                if (! empty($orig_name) && $name != $orig_name) {
                    $projects = ProjectForeignDatabase::select('bp_projects.name')
                        ->where('bp_project_foreign_databases.foreign_database_id', $foreign_database->id)
                        ->join('bp_projects', 'bp_projects.id', '=', 'bp_project_foreign_databases.project_id')
                        ->get();

                    View::dropForTable($database_f, $foreign_database->schema_name, $orig_name);
                    foreach ($projects as $project) {
                        View::dropForTable($database_f, $project->name, $orig_name);
                    }

                    self::removeTable($database, $orig_name);
                }
            } else {
                $columns = Table::columns($database, 'public', $name);
                $columns = md5(serialize($columns));

                $foreign_columns = Table::columns($database_f, $foreign_database->schema_name, $name);
                $foreign_columns = md5(serialize($foreign_columns));

                if ($foreign_columns != $columns) {
                    $projects = ProjectForeignDatabase::select('bp_projects.name')
                        ->where('bp_project_foreign_databases.foreign_database_id', $foreign_database->id)
                        ->join('bp_projects', 'bp_projects.id', '=', 'bp_project_foreign_databases.project_id')
                        ->get();

                    $definitions_arr[] = View::dropForTable($database_f, $foreign_database->schema_name, $name);
                    foreach ($projects as $project) {
                        $definitions_arr[] = View::dropForTable($database_f, $project->name, $name);
                    }

                    self::refreshTable($database_f, $foreign_database, $foreign_database->schema_name, $name);

                    foreach ($definitions_arr as $definitions) {
                        View::createFromDefinitions($database_f, $definitions);
                    }
                }
            }
        }
    }

    public static function refreshTable(PartnerIntegration $database, PartnerIntegrationForeignDatabase $foreign_database, string $schema, string $name)
    {
        $connection = self::connect($database);
        try {
            $sql = <<<SQL
                DROP FOREIGN TABLE $schema.$name CASCADE
                SQL;

            $connection->statement($sql);

            // Let's add it back ...
            $sql = <<<SQL
                IMPORT FOREIGN SCHEMA public
                    LIMIT TO ("$name")
                    FROM SERVER "$foreign_database->foreign_server_name"
                    INTO "$foreign_database->schema_name"
                SQL;

            $connection->statement($sql);
        } catch (Exception $e) {
            return FALSE;
        }

        return TRUE;
    }

    public static function removeTable(PartnerIntegration $database, string $name)
    {
        $foreign_databases = PartnerIntegrationForeignDatabase::where('foreign_control_id', $database->id)->get();

        foreach ($foreign_databases as $foreign_database) {
            $database_f = PartnerIntegration::find($foreign_database->control_id);
            $foreign_connection = self::connect($database_f);
            
            $sql = <<<SQL
                DROP FOREIGN TABLE IF EXISTS $foreign_database->schema_name."$name" CASCADE
                SQL;

            $foreign_connection->statement($sql);
        }
    }

    public static function types(PartnerIntegration $database, array $schemas, array $tables)
    {
        $foreign_databases = PartnerIntegrationForeignDatabase::where('control_id', $database->id)->get();

        $foreign_tables = array_filter($tables, function ($table) {
            return $table->table_type == 'FOREIGN';
        });

        $tables = array_filter($tables, function ($table) {
            return $table->table_type != 'FOREIGN';
        });

        // Iterate over each foreign database
        foreach ($foreign_databases as $foreign_database) {
            $catalog = array_search($foreign_database->schema_name, $schemas);
            $database_f = PartnerIntegration::find($foreign_database->foreign_control_id);

            // Iterate over our tables, and check their type only if foreign and matches our database
            foreach ($foreign_tables as $key => $foreign_table) {
                if ($foreign_table->table_catalog != $catalog) {
                    continue;
                }

                $table = Table::get($database_f, 'public', $foreign_table->table_name);
                $foreign_table->table_type = $table?->table_type;
                $tables[] = $foreign_table;
                unset($foreign_tables[$key]);
            }

            if (empty($foreign_tables)) {
                break;
            }
        }

        return $tables;
    }
}
