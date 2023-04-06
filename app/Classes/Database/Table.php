<?php

namespace App\Classes\Database;

use App\Models\PartnerIntegration;
use App\Models\Manager\ImportedTable;
use Illuminate\Database\QueryException;
use Exception;
use DB;

/**
 * Class description
 */
class Table extends Connection
{
    public static function list(PartnerIntegration $database, array $schemas, bool $include_public = TRUE)
    {
        $schemas_formatted = array_map(function ($schema) {
            return "'$schema'";
        }, $schemas);

        if ($include_public) {
            $schemas_formatted[] = "'public'";
        }

        $schema_str = implode(',', $schemas_formatted);

        $sql = <<<SQL
            select T.table_catalog,
            T.table_name,
            '0' as table_id,
            T.table_schema,
            T.table_type,
            S.n_live_tup as "num_records",
            pg_size_pretty(pg_total_relation_size(T.table_schema || '."' || t.table_name || '"')) as "total_size"
            from information_schema.tables as T
            left join pg_stat_all_tables as S
            on '"' || t.table_name || '"' = '"' || s.relname || '"' and t.table_schema = S.schemaname
            where (table_schema in ($schema_str)) and
            table_name not like 'dropped_%' and
            table_catalog = '$database->database'
            union all
            select  '$database->database' as table_catalog,
                    T.matviewname as table_name,
                    '0' as table_id,
                    T.schemaname as table_schema,
                    'Materialized View' as table_type,
                    S.n_live_tup as "num_records",
                    pg_size_pretty(pg_total_relation_size(T.schemaname || '.' || T.matviewname)) as "total_size"
            from pg_matviews as T
            left join pg_stat_all_tables as S
            on T.matviewname = S.relname
            where (T.schemaname in ($schema_str)) and
            T.matviewname not like 'dropped_%'
            order by table_schema, table_name
            SQL;

        $custom_tables = ImportedTable::where('control_id', $database->id)
            ->get();

        return array_map(function ($table) use ($schemas, $custom_tables) {
            $custom_table = $custom_tables->filter(function ($custom_table) use ($table) {
                return $custom_table->table_name == $table->table_name;
            });

            if ($custom_table->count() > 0) {
                $table->table_id = $custom_table->first()->id;
                $table->table_type = "Custom Table";
            } else if ($table->table_type == "BASE TABLE") {
                $table->table_type = "Table";
            } else if ($table->table_type == "VIEW") {
                $table->table_type = "View";
            } else if ($table->table_type == "FOREIGN TABLE") {
                $table->table_type = "Foreign Table";
            }

            $catalog = array_search($table->table_schema, $schemas);
            if (! empty($catalog)) {
                $table->table_catalog = $catalog;
            }

            return $table;
        }, self::connect($database)->select($sql));
    }

    public static function get(PartnerIntegration $database, string $schema, string $name)
    {
        $custom_tables = [];

        $sql = <<<SQL
            select T.table_catalog,
            T.table_name,
            '0' as table_id,
            T.table_schema,
            T.table_type,
            S.n_live_tup as "num_records",
            pg_size_pretty(pg_total_relation_size(T.table_schema || '."' || t.table_name || '"')) as "total_size"
            from information_schema.tables as T
            left join pg_stat_all_tables as S
            on '"' || t.table_name || '"' = '"' || s.relname || '"' and t.table_schema = S.schemaname
            where table_schema = '$schema' and
            table_name = '$name' and
            table_catalog = '$database->database'
            union all
            select '$database->database' as table_catalog,
                   T.matviewname as table_name,
                   '0' as table_id,
                   T.schemaname as table_schema,
                   'Materialized View' as table_type,
                   S.n_live_tup as "num_records",
                   pg_size_pretty(pg_total_relation_size(T.schemaname || '.' || T.matviewname)) as "total_size"
            from pg_matviews as T
            left join pg_stat_all_tables as S
            on T.matviewname = S.relname
            where T.schemaname = '$schema' and
            T.matviewname = '$name'
            order by table_schema, table_name
            SQL;

        $custom_tables = ImportedTable::where('control_id', $database->id)
            ->get();

        $table = array_map(function ($table) use ($custom_tables) {
            $custom_table = $custom_tables->filter(function ($custom_table) use ($table ) {
                return $custom_table->table_name == $table->table_name;
            });

            if ($custom_table->count() > 0) {
                $table->table_id = $custom_table->first()->id;
                $table->table_type = "Custom Table";
            } else if ($table->table_type == "BASE TABLE") {
                $table->table_type = "Table";
            } else if ($table->table_type == "VIEW") {
                $table->table_type = "View";
            } else if ($table->table_type == "FOREIGN TABLE") {
                $table->table_type = "Foreign Table";
            }

            return $table;
        }, self::connect($database)->select($sql));
        
        return array_shift($table);
    }

    public static function exists(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $sql = <<<SQL
            SELECT table_catalog,
                table_name,
                table_schema,
                table_type
            FROM information_schema.tables
            WHERE table_name = '$table_name' and table_schema = '$table_schema'
            UNION ALL
            SELECT  '' as table_catalog,
                matviewname as table_name,
                schemaname as table_schema,
                'MATERIALIZED VIEW' as table_type
            FROM pg_matviews
            WHERE matviewname = '$table_name' and schemaname = '$table_schema'
            ORDER BY table_name
            SQL;

        $table = self::connect($database)->select($sql);

        return count($table) > 0 ? TRUE : FALSE;
    }

    public static function ordinals(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $sql = <<<SQL
            SELECT ordinal_position,
                column_name
            FROM information_schema.columns
            WHERE table_name = '$table_name' and table_schema = '$table_schema' and table_catalog = '$database->database'
            ORDER BY ordinal_position
            SQL;

        $ordinals = collect(self::connect($database)->select($sql));

        return $ordinals->mapWithKeys(function ($ordinal) {
            return [$ordinal->column_name => $ordinal->ordinal_position];
        })->toArray();
    }

    public static function views(PartnerIntegration $database, string $table_schema, string $table_name, bool $exclude_base_table = FALSE)
    {
        $full_table_name = '"' . $table_schema . '"."' . $table_name . '"';

        $sql = <<<SQL
            SELECT 
                distinct v.oid::regclass AS view,
                case split_part(v.oid::regclass::varchar, '.'::varchar, 2)
                    when '' then 'public'
                    else split_part(v.oid::regclass::varchar, '.'::varchar, 1)
                end as view_schema,
                case split_part(v.oid::regclass::varchar, '.'::varchar, 2)
                    when '' then v.oid::regclass::varchar
                    else split_part(v.oid::regclass::varchar, '.'::varchar, 2)
                end as view_name
            FROM pg_depend AS d
               JOIN pg_rewrite AS r
                  ON r.oid = d.objid
               JOIN pg_class AS v
                  ON v.oid = r.ev_class
            WHERE v.relkind in ('m','v')
              AND d.classid = 'pg_rewrite'::regclass
              AND d.refclassid = 'pg_class'::regclass
              AND d.deptype = 'n'
              AND d.refobjid = '$full_table_name'::regclass;
            SQL;

        try {
            $views = self::connect($database)->select($sql);
        } catch (QueryException $e) {
            $views = [];
        }

        if ($exclude_base_table) {
            $views = array_filter($views, function ($view) use ($table_name, $table_schema) {
                return $view->view_name != $table_name || $view->view_schema != $table_schema;
            });
        }

        return $views;
    }

    public static function relationships(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $sql = <<<SQL
            SELECT
                tc.table_schema, 
                tc.constraint_name, 
                tc.table_name, 
                kcu.column_name, 
                ccu.table_schema AS foreign_table_schema,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name 
            FROM 
                information_schema.table_constraints AS tc 
                JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
            WHERE 
                tc.constraint_type = 'FOREIGN KEY' AND 
                tc.table_schema = '$table_schema' AND
                tc.table_name = '$table_name'
            SQL;

        return self::connect($database)->select($sql);
    }

    public static function indexes(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $sql = <<<SQL
            SELECT
                i.relname as index_name, 
                a.attname as column_name, 
                pix.indexdef
            FROM 
                pg_class as t
                JOIN pg_index as ix ON t.oid = ix.indrelid
                JOIN pg_class as i ON i.oid = ix.indexrelid
                JOIN pg_attribute as a ON a.attrelid = t.oid
                JOIN pg_indexes as pix ON i.relname = pix.indexname
            WHERE 
                t.relkind = 'r' AND 
                pix.schemaname = '$table_schema' AND
                pix.tablename = '$table_name' AND
                a.attnum = ANY(ix.indkey)
            SQL;

        return self::connect($database)->select($sql);
    }

    public static function columns(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $connection = self::connect($database);
        $type = self::getTableType($database, $table_schema, $table_name);

        if ($type == "normal") {
            $columns = $connection->table("information_schema.columns")
                ->where("table_schema", $table_schema)
                ->where("table_name", $table_name)
                ->where("table_catalog", $database->database)
                ->select([
                    "column_name", 
                    "udt_name", 
                    "ordinal_position", 
                    DB::raw("coalesce(character_maximum_length::varchar, '') as character_maximum_length"), 
                    DB::raw("coalesce(numeric_precision::varchar, '') as numeric_precision"),
                    DB::raw("coalesce(numeric_precision_radix::varchar, '') as numeric_precision_radix"),
                    "column_default", 
                    "is_nullable", 
                    "data_type"
                ])
                ->get();
        } else {
            $columns = $connection->table('pg_catalog.pg_attribute as attr')
                ->select([
                    "attr.attname as column_name",
                    DB::raw("'' as udt_name"),
                    DB::raw("'' as ordinal_position"),
                    DB::raw("information_schema._pg_char_max_length(attr.atttypid, attr.atttypmod) as character_maximum_length"),
                    DB::raw("information_schema._pg_numeric_precision(attr.atttypid, attr.atttypmod) as numeric_precision"),
                    DB::raw("'' as numeric_precision_radix"),
                    DB::raw("'' as column_default"),
                    DB::raw("'' as is_nullable"),
                    DB::raw("(regexp_split_to_array(pg_catalog.format_type(attr.atttypid, attr.atttypmod), '\('))[1] as data_type"),
                ])
                ->where("ns.nspname", $table_schema)
                ->where("cls.relname", $table_name)
                ->where(DB::raw('cast("tp"."typanalyze" as text)'), "array_typanalyze")
                ->where("attr.attnum", ">", "0")
                ->where(DB::raw("not attr.attisdropped"))
                ->join("pg_catalog.pg_class as cls", "cls.oid", "=", "attr.attrelid")
                ->join("pg_catalog.pg_namespace as ns", "ns.oid", "=", "cls.relnamespace")
                ->join("pg_catalog.pg_type as tp", "tp.typelem", "=", "attr.atttypid")
                ->orderBy("attr.attnum")
                ->get();
        }
        
        return $columns->toArray();
    }

    /**
     * Get an array of column names for a given table
     */
    public static function columnNames(PartnerIntegration $database, string $table_schema, string $table_name): array
    {
        return array_column(
            self::columns($database, $table_schema, $table_name),
            'column_name'
        );
    }

    public static function randomColumnValues(PartnerIntegration $database, string $table_schema, string $table_name, string $column_name, int $limit = 3)
    {
        $sql = <<<SQL
            SELECT $column_name, COUNT("$column_name") as value_occurence
            FROM "$table_schema"."$table_name"
            WHERE "$column_name" is NOT NULL and CAST("$column_name" as VARCHAR) != ''
            GROUP BY "$column_name"
            ORDER BY value_occurence DESC
            LIMIT $limit
            SQL;

        return self::connect($database)->select($sql);
    }

    public static function dependencies(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $views = self::views($database, $table_schema, $table_name);
        $foreign_views = self::getForeignObjectDependencies($database, $table_name);
        
        $foreign_views = array_map(function ($foreign_view) {
            return (object) [
                'view'        => "$foreign_view->schema.$foreign_view->name",
                'view_schema' => $foreign_view->schema,
                'view_name'   => $foreign_view->name
            ];
        }, $foreign_views);

        return array_merge($views, $foreign_views);
    }

    public static function create(PartnerIntegration $database, string $table_schema, string $table_name, array $columns)
    {
        $sql = "CREATE TABLE $table_schema.$table_name (";
        $i = 0;
        foreach ($columns as $column) {
            if (substr($column["column_name"], 0, strlen('__bytespree')) == '__bytespree') {
                continue;
            }

            $length = $column['character_maximum_length'] ?? $column['value'];
            $column_name = $column['column_name'];
            $type = $column["type"];
            $precision = $column['precision'];

            if ($i > 0) {
                $sql .= ",";
            }

            if ($type == 'varchar') {
                $sql .= <<<SQL
                    "$column_name" VARCHAR($length) DEFAULT NULL
                    SQL;
            } elseif ($type == 'decimal') {
                $sql .= <<<SQL
                    "$column_name" DECIMAL($length, $precision) DEFAULT NULL
                    SQL;
            } else {
                $sql .= <<<SQL
                    "$column_name" $type DEFAULT NULL
                    SQL;
            }
            ++$i;
        }

        $sql .= ")";

        return self::connect($database)->statement($sql);
    }

    public static function createIndex(PartnerIntegration $database, string $table_name, string $column_name)
    {
        $index_name = "bytespree_" . md5("$table_name" . "_" . $column_name . "_index");
        $column_name = "\"$column_name\"";

        $sql = <<<SQL
            CREATE INDEX $index_name
            ON "public"."$table_name" 
            USING btree ($column_name)
            SQL;

        return self::connect($database)->statement($sql);
    }

    public static function truncate(PartnerIntegration $database, string $schema, string $name)
    {
        $sql = <<<SQL
            TRUNCATE "$schema"."$name"
            SQL;

        return self::connect($database)->statement($sql);
    }

    // todo: add cascade/rebuild dependencies
    public static function drop(PartnerIntegration $database, string $schema, string $name)
    {
        $sql = <<<SQL
            DROP TABLE IF EXISTS "$schema"."$name"
            SQL;

        return self::connect($database)->statement($sql);
    }

    public static function dropIndex(PartnerIntegration $database, string $name)
    {
        $sql = <<<SQL
            DROP INDEX $name
            SQL;
            
        return self::connect($database)->statement($sql);
    }

    public static function getSchema(PartnerIntegration $database, string $table, array $schemas_to_search = [])
    {
        if (empty($schemas_to_search)) {
            $schemas = ['public'];
        } else {
            $schemas = $schemas_to_search;
        }

        $schemas = implode(', ', array_map(function ($schema) {
            return "'$schema'";
        }, $schemas));

        $sql = <<<SQL
            select table_schema from (
                select table_schema, table_name
                from information_schema.tables
                where table_schema in ($schemas)
                union 
                select schemaname as table_schema, matviewname as table_name
                from pg_matviews
                where schemaname in ($schemas)
            ) as s 
            where table_name = ?
            SQL;

        $results = self::connect($database)->select($sql, [$table]);

        if (count($results) === 0) {
            throw new Exception("Table $table not found");
        }

        return $results[0]->table_schema;
    }

    /**
     * Get the count from a table without any qualifiers
     */
    public static function count(PartnerIntegration $database, string $table_schema, string $table_name): int
    {
        $sql = <<<SQL
            SELECT COUNT(*) as count
            FROM "{$table_schema}"."{$table_name}"
            SQL;

        return self::connect($database)
            ->select($sql)[0]->count;
    }
    
    /**
     * Get the type of a table or view.
     * 
     * @return string 'normal' or 'materialized'
     */
    public static function getTableType(PartnerIntegration $database, string $schema, string $table): string
    {
        $sql = <<<SQL
            select table_type from (
                select table_schema, table_name, 'normal' as table_type
                from information_schema.tables
                union 
                select schemaname as table_schema, matviewname as table_name, 'materialized' as table_type
                from pg_matviews
            ) as s 
            where table_name = ?
            and table_schema = ?
            SQL;

        $results = self::connect($database)->select($sql, [$table, $schema]);
        
        if (count($results) > 0) {
            return $results[0]->table_type;
        }

        return 'normal';
    }
}