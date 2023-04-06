<?php

namespace App\Classes\Database;

use App\Models\PartnerIntegration;
use App\Models\Manager\ViewDefinition;
use App\Models\Manager\ViewDefinitionHistory;
use DB;
use Exception;

/**
 * Class description
 */
class View extends Connection
{
    public static function list(PartnerIntegration $database)
    {
        $sql = <<<SQL
            SELECT
                table_schema as view_schema, 
                table_name as view_name,
                view_definition,
                'normal' as view_type
            FROM
                information_schema.views
            WHERE
                table_schema NOT IN ('pg_catalog', 'information_schema', 'zdb')
            UNION
            SELECT 
                schemaname as view_schema,
                matviewname as view_name,
                definition as view_definition,
                'materialized' as view_type
            FROM pg_matviews
            WHERE
                schemaname NOT IN ('pg_catalog', 'information_schema', 'zdb')
            ORDER BY view_schema, view_name asc
            SQL;

        return self::connect($database)->select($sql);
    }

    public static function get(PartnerIntegration $database, string $schema, string $name)
    {
        $sql = <<<SQL
            SELECT
                table_schema as view_schema, 
                table_name as view_name,
                view_definition,
                'normal' as view_type
            FROM
                information_schema.views
            WHERE
                table_schema = '$schema' AND
                table_name = '$name'
            UNION
            SELECT 
                schemaname as view_schema,
                matviewname as view_name,
                definition as view_definition,
                'materialized' as view_type
            FROM pg_matviews
            WHERE
                schemaname = '$schema' AND
                matviewname = '$name'
            ORDER BY view_schema, view_name asc
            SQL;

        $view = self::connect($database)->select($sql);

        return array_shift($view);
    }

    public static function create(PartnerIntegration $database, string $schema, string $name, string $type, string $definition)
    {
        $type = empty($type) || $type == 'normal' ? '' : 'MATERIALIZED';

        $sql = <<<SQL
            CREATE $type VIEW "$schema"."$name" AS $definition
            SQL;

        return self::connect($database)->statement($sql);
    }

    public static function createFromDefinitions(PartnerIntegration $database, array $definitions)
    {
        $connection = self::connect($database);

        $results = [];
        foreach ($definitions as $definition) {
            try {
                $results[] = $connection->statement($definition);
            } catch (Exception $e) {
                // May need to add a suppress errors trigger here
                $results[] = FALSE;
            }
        }

        return $results;
    }

    public static function test(PartnerIntegration $database, string $sql)
    {
        $permitted_chars = 'abcdefghijklmnopqrstuvwxyz';
        $name = substr(str_shuffle($permitted_chars), 0, 10);

        $sql = <<<SQL
            CREATE VIEW $name AS $sql
            SQL;

        try {
            self::connect($database)->statement($sql);
            self::drop($database, 'public', $name, 'normal');
        } catch (Exception $e) {
            return FALSE;
        }

        return TRUE;
    }

    public static function rename(PartnerIntegration $database, string $schema, string $orig_name, string $new_name, string $type)
    {
        $type = empty($type) || $type == 'normal' ? '' : 'MATERIALIZED';

        $sql = <<<SQL
            ALTER $type VIEW "$schema"."$orig_name" RENAME TO "$new_name"
            SQL;

        return self::connect($database)->statement($sql);
    }

    public static function rebuild(PartnerIntegration $database, ViewDefinition $view)
    {
        if (empty($view->view_schema) || empty($view->view_name) || empty($view->view_definition_sql) || empty($view->view_type)) {
            return FALSE;
        }
        
        $db_view = self::get($database, $view->view_schema, $view->view_name);
        if (! empty($db_view)) {
            $dropped = self::drop($database, $db_view->view_schema, $db_view->view_name, $db_view->view_type);

            if (! $dropped) {
                throw new Exception("Rebuilding view failed because existing view ({$view->view_schema}.{$view->view_name}) could not be dropped.");
            }
        }

        return self::create($database, $view->view_schema, $view->view_name, $view->view_type, $view->view_definition_sql);
    }

    public static function refresh(PartnerIntegration $database, string $schema, string $name)
    {
        $sql = <<<SQL
            REFRESH MATERIALIZED VIEW "$schema"."$name"
            SQL;

        return self::connect($database)->statement($sql);
    }

    public static function drop(PartnerIntegration $database, string $schema, string $name, string $type)
    {
        $type = empty($type) || $type == 'normal' ? '' : 'MATERIALIZED';

        $sql = <<<SQL
            DROP $type VIEW IF EXISTS "$schema"."$name"
            SQL;

        return self::connect($database)->statement($sql);
    }

    /**
     * Drop views that depend on parent object
     *
     * @param  PartnerIntegration $database        Database instance
     * @param  string             $schema          Schema name of parent object
     * @param  string             $table           Name of parent object (table or view)
     * @param  bool               $terminate_locks Whether to check for locks and terminate them prior to drop
     * @return string[]
     *                                            Array containing the definitions of dropped view so they can be recreated later
     */
    public static function dropForTable(PartnerIntegration $database, string $schema, string $table, bool $terminate_locks = FALSE)
    {
        $connection = self::connect($database);
        if (! Table::exists($database, $schema, $table)) {
            return [];
        }

        $object = '"' . $schema . '"."' . $table . '"';

        $sql = <<<SQL
            with recursive views as (
                -- get the directly depending views
                select
                    v.oid::regclass as view, 1 as level
                from
                    pg_depend as d
                join pg_rewrite as r on
                    r.oid = d.objid
                join pg_class as v on
                    v.oid = r.ev_class
                where
                    d.classid = 'pg_rewrite'::regclass
                    and d.refclassid = 'pg_class'::regclass
                    and d.deptype = 'n'
                    and d.refobjid = '$object'::regclass
                union
                -- add the views that depend on these
                select
                    v.oid::regclass, views.level + 1
                from
                    views
                join pg_depend as d on
                    d.refobjid = views.view
                join pg_rewrite as r on
                    r.oid = d.objid
                join pg_class as v on
                    v.oid = r.ev_class
                where
                    d.classid = 'pg_rewrite'::regclass
                    and d.refclassid = 'pg_class'::regclass
                    and d.deptype = 'n'
                    and v.oid <> views.view
                    -- avoid loop 
            )
            select
                view,
                pg_get_viewdef(view) as definition
            from
                views
            group by
                view
            order by
                max(level);
            SQL;

        $views = $connection->select($sql);

        $reversed_views = array_reverse($views, TRUE);
        $views = [];

        $connection->transaction(function () use ($reversed_views, $database, $schema, $table, &$views, $terminate_locks) {
            foreach ($reversed_views as $reversed_view) {
                $parts = explode('.', $reversed_view->view, 2);
                if (count($parts) > 1) {
                    $view_schema = $parts[0];
                    $view_name = $parts[1];
                } else {
                    $view_schema = 'public';
                    $view_name = $parts[0];
                }

                if ($view_name == $table && $view_schema == $schema) {
                    continue;
                }

                $db_view = self::get($database, $view_schema, $view_name);

                $views[] = (object) [
                    'view_schema' => $view_schema,
                    'view_name'   => $view_name,
                    'type'        => $db_view->view_type == 'normal' ? '' : $db_view->view_type,
                    'definition'  => $reversed_view->definition
                ];

                $view = ViewDefinition::where('view_schema', $view_schema)
                    ->where('view_name', $view_name)
                    ->where('partner_integration_id', $database->id)
                    ->first();

                if (! empty($view)) {
                    $definition_history = new ViewDefinitionHistory([
                        'view_history_guid'   => $view->view_history_guid,
                        'view_type'           => $view->view_type,
                        'view_schema'         => $view->view_schema,
                        'view_name'           => $view->view_name,
                        'view_definition_sql' => $view->view_definition_sql,
                        'view_created_by'     => $view->created_by,
                        'view_created_at'     => $view->updated_at
                    ]);
                    $definition_history->timestamps = FALSE;
                    $definition_history->save();
                }

                if ($terminate_locks) {
                    // Terminate any locks on the view
                    Connection::terminateProcessesLockingObject($database, $schema, $table, ['v', 'm']);
                }

                self::drop($database, $view_schema, $view_name, $db_view->view_type);
            }
        });

        $definition_array = [];
        foreach ($views as $view) {
            if (! empty($view->definition)) {
                $definition_array[] = "CREATE $view->type VIEW  $view->view_schema.$view->view_name AS $view->definition";
            }
        }

        return array_reverse($definition_array);
    }
}