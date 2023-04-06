<?php

namespace App\Classes\Database;

use App\Models\PartnerIntegration;
use App\Models\Explorer\Project;
use Exception;
use DB;

class StudioExplorer extends Connection
{
    /**
     * Return array of all columns and set the visiblity and alias based
     * on user preferences.
     *
     * @param  int    $project_id      The id for the project record
     * @param  int    $control_id      the id for the partner integration record
     * @param  string $prefix          The selected table's prefix
     * @param  string $schema          The schema of for the table
     * @param  string $table           The table to get columns for
     * @param  array  $joins           The list of joins to the table
     * @param  array  $transformations The list of transformations for the table
     * @return array
     */
    public function getProjectTableColumns(Project $project, PartnerIntegration $database, $prefix, $schema, $table, $joins, $transformations)
    {
        $connection = self::connect($database);
        
        if (empty($schema)) {
            $schema = Table::getSchema($project->primary_database, $table, [$project->name, 'public']);
        }

        $type = Table::getTableType($database, $schema, $table);

        if ($type == "normal") {
            $columns = $connection->table("information_schema.columns")
                ->where("table_schema", $schema)
                ->where("table_name", $table)
                ->where("table_catalog", $database->database)
                ->orderBy("ordinal_position")
                ->select(["column_name", "table_name", "data_type", "character_maximum_length", "numeric_scale", "numeric_precision", DB::raw("'{$prefix}' as prefix"), DB::raw("'' as transformation"), DB::raw("'' as sql_definition")])
                ->get();
        } else {
            $columns = $connection->table('pg_catalog.pg_attribute as attr')
                ->select([
                    "attr.attname as column_name",
                    "cls.relname as table_name",
                    DB::raw("(regexp_split_to_array(pg_catalog.format_type(attr.atttypid, attr.atttypmod), '\('))[1] as data_type"),
                    DB::raw("information_schema._pg_char_max_length(attr.atttypid, attr.atttypmod) as character_maximum_length"), 
                    DB::raw("information_schema._pg_numeric_scale(attr.atttypid, attr.atttypmod)  as numeric_scale"),
                    DB::raw("information_schema._pg_numeric_precision(attr.atttypid, attr.atttypmod) as numeric_precision"),
                    DB::raw("'$prefix' as prefix"),
                    DB::raw("'' as transformation"),
                    DB::raw("'' as sql_definition"),
                ])
                ->where("ns.nspname", $schema)
                ->where("cls.relname", $table)
                ->where(DB::raw('cast("tp"."typanalyze" as text)'), "array_typanalyze")
                ->where("attr.attnum", ">", "0")
                ->where(DB::raw("not attr.attisdropped"))
                ->join("pg_catalog.pg_class as cls", "cls.oid", "=", "attr.attrelid")
                ->join("pg_catalog.pg_namespace as ns", "ns.oid", "=", "cls.relnamespace")
                ->join("pg_catalog.pg_type as tp", "tp.typelem", "=", "attr.atttypid")
                ->orderBy("attr.attnum")
                ->get();
        }

        if (! $columns) {
            return [];
        }  
        $columns = $columns->toArray();
        
        array_unshift($columns, ["column_name" => "count(*)", "alias" => "count__records", "prefix" => "aggregate", "sql_definition" => "", "added" => FALSE, "data_type" => "integer"]);
        $target_columns = [];

        foreach ($columns as $key => $column) {
            $column = (array) $column;
            $columns[$key] = (array) $column;
            $columns[$key]["added"] = FALSE;
            if ($column['column_name'] == 'count(*)') {
                $target_column_name = $column['column_name'];
            } elseif (count($joins) > 0) {
                $target_column_name = $prefix . '_' . $column['column_name'];
            } else {
                $target_column_name = $column['column_name'];
            }
            $target_column_name = $this->fixIdentifierName($target_column_name, $target_columns);
            $columns[$key]['target_column_name'] = $target_column_name;
            $target_columns[] = $target_column_name;
        }

        $index = 0;
        foreach ($columns as $key => $column) {
            if ($index != 0 && $key < 401) {
                $columns[$key]['checked'] = TRUE;
            } else {
                $columns[$key]['checked'] = FALSE;
            }

            $columns[$key]['editing'] = FALSE;
            if (! isset($columns[$key]['alias'])) {
                $columns[$key]['alias'] = "";
            }

            ++$index;
        }

        foreach ($joins as $join) {
            $table = $join['table'];
            $prefix = $join['prefix'];
            if (! isset($join['schema']) || empty($join['schema'])) {
                $schema = Table::getSchema($project->primary_database, $table, [$project->name, 'public']);
            } else {
                $schema = $join['schema'];
            }

            $join_columns = $this->getJoinTableColumns($project, $table, $prefix, $schema);

            foreach ($join_columns as $join_column) {
                $target_column_name = $join_column['prefix'] . '_' . $join_column['column_name'];
                $target_column_name = $this->fixIdentifierName($target_column_name, $target_columns);
                $target_columns[] = $target_column_name;
                $join_column['target_column_name'] = $target_column_name;
                if (count($columns) > 401) {
                    $join_column['checked'] = FALSE;
                } else {
                    $join_column['checked'] = TRUE;
                }

                $join_column['editing'] = FALSE;
                $join_column['added'] = FALSE;
                $join_column['alias'] = '';
                $columns[] = $join_column;
            }
        }

        if (count($transformations) > 0) {
            foreach ($columns as $key => $column) {
                if (empty($transformations[$column["prefix"] . "_" . $column["column_name"]])) {
                    $table_transformations = [];
                } else {
                    $table_transformations = $transformations[$column["prefix"] . "_" . $column["column_name"]];
                }

                $transformation_value = "";
                if (count($table_transformations) > 0) {
                    if ($column["prefix"] == "aggregate") {
                        $transformation_value = $column["column_name"];
                    } else {
                        $transformation_value = '"' . $column["prefix"] . '"."' . $column["column_name"] . '"';
                    }
                }

                $transformation_value = $this->buildTransformation($table_transformations, $transformation_value, $columns[$key]["data_type"]);

                foreach ($table_transformations as $transformation) {
                    if ($transformation["transformation_type"] == "Cast") {
                        $scale = NULL;
                        $precision = NULL;
                        if ($transformation["transformation"]['field_1']['value'] == "decimal(13, 2)") {
                            $type = "decimal";
                            $scale = 2;
                            $precision = 13;
                        } else {
                            $type = $transformation["transformation"]['field_1']['value'];
                        }

                        $columns[$key]["data_type"] = $type;
                        $columns[$key]["character_maximum_length"] = NULL;
                        $columns[$key]["numeric_scale"] = $scale;
                        $columns[$key]["numeric_precision"] = $precision;
                    }
                }

                $columns[$key]["sql_definition"] = $transformation_value;
            }
        }

        return $columns;
    }

    public function searchColumns(Project $project, PartnerIntegration $database, string $column)
    {
        // Grab our relevant schemas so we only pull columns within them
        $query_schemas = [
            'public',
            $project->name,
            ...array_values($project->getSchemas())
        ];

        $connection = self::connect($database);

        $columns = $connection->table('pg_catalog.pg_attribute as attr')
            ->select([
                "ns.nspname as table_schema",
                "cls.relname as table_name", 
                "attr.attname as column_name"
            ])
            ->where(DB::raw('cast("tp"."typanalyze" as text)'), "array_typanalyze")
            ->where("attr.attnum", ">", "0")
            ->where(DB::raw("not attr.attisdropped"))
            ->where('cls.relkind', '!=', 'i')
            ->whereIn('ns.nspname', $query_schemas)
            ->join("pg_catalog.pg_class as cls", "cls.oid", "=", "attr.attrelid")
            ->join("pg_catalog.pg_namespace as ns", "ns.oid", "=", "cls.relnamespace")
            ->join("pg_catalog.pg_type as tp", "tp.typelem", "=", "attr.atttypid")
            ->orderBy("attr.attnum"); 
        
        if (str_contains($column, '%')) {
            $columns->where('attr.attname', 'ilike', $column);
        } else {
            $columns->where(DB::raw('LOWER(attr.attname)'), $column);
        }
        
        $columns = $columns->get()
            ->map(function ($column) use ($connection) {
                $sample_records = $connection->table("$column->table_schema.$column->table_name")
                    ->limit(3)
                    ->get()
                    ->pluck($column->column_name);
                
                $index = 1;
                foreach ($sample_records as $sample_record) {
                    $column->$index = $sample_record;
                    ++$index;
                }
                
                return $column;
            });

        return $columns;
    }

    /**
     * Generate Postgres compatible identifier name given list of names
     * already in use.
     *
     * @param  string $identifier          purposed name of identifier
     * @param  array  $current_identifiers List of identifier names in use
     * @return void
     */
    public function fixIdentifierName($identifier, $current_identifiers)
    {
        if (strlen($identifier) > 63) {
            $identifier = substr($identifier, 0, 63);
            $key = array_search($identifier, $current_identifiers);
            $count = 0;
            while ($key !== FALSE) {
                $identifier = substr($identifier, 0, 59) . str_pad($count, 4, '0', STR_PAD_LEFT);
                $key = array_search($identifier, $current_identifiers);
                ++$count;
            }
        }

        return $identifier;
    }

    public function getRecordsQuery(
        $project,
        $prefix,
        $table,
        $schema,
        $filters = [],
        $joins = [],
        $order = NULL,
        $columns = [],
        $is_grouped = FALSE,
        $transformations = [],
        $unions = [],
        $union_all = FALSE
    ) {
        if (empty($schema)) {
            Table::getSchema($project->primary_database, $table, [$project->name, 'public']);
        }

        $where_string = "";
        $group_by_names = [];

        if (! is_array($filters)) {
            $filters = [];
        }
        if (! is_array($joins)) {
            $joins = [];
        } else {
            $joins = array_map(fn ($join) => (object) $join, $joins);
        }

        if (! is_array($columns)) {
            $columns = [];
        }
        if (! is_array($transformations)) {
            $transformations = [];
        }

        $unions = $this->buildUnionColumnMapping($unions, $columns);

        $columns_array = $this->buildColumnsArray($prefix, $columns, $is_grouped, $group_by_names, $transformations, FALSE);

        $isJsonFilter = $this->hasJsonFilters($filters);  // Has filter(s) on unstructured data

        $temp_joins = $this->buildJoinObjects($project, $joins, $prefix, $columns); // todo

        $where_string = $this->buildWhereString($filters, $transformations);

        $having_string = $this->buildHavingString($filters, $transformations);

        if ($isJsonFilter) {
            $join_string = $this->buildJoinString($prefix, $temp_joins);

            $group_string = $this->buildGroupString($is_grouped, $group_by_names);

            $query = $this->buildJsonFilterSelectString($schema, $prefix, $table, $columns_array, $where_string, $having_string, $join_string, $group_string, $order);
        } else {
            $query = DB::table(DB::raw("\"$schema\".\"$table\" as \"$prefix\""));
        
            foreach ($temp_joins as $join) {
                $this->applyJoinToQuery($query, $join->schema, $join->table, $join->prefix, $join->target_column, $join->source_column, $join->join_type);
            }

            $query->select($columns_array);

            if (! empty($unions)) {
                foreach ($unions as $union) {
                    $union_query = DB::table(DB::raw("\"{$union['schema']}\".\"{$union['table']}\""));
        
                    // Map our unioned columns to the base table columns
                    foreach ($union['columns'] as $original_column => $aliased_to_match_base_table_column ) {
                        $union_query->addSelect(DB::raw("\"{$original_column}\" as \"{$aliased_to_match_base_table_column}\""));
                    }
        
                    $query->union(
                        $union_query,
                        $union_all
                    );
                }
            }

            if ($is_grouped == TRUE) {
                $group_by_names = array_map(fn($col) => DB::raw($col), $group_by_names);

                $query->groupBy($group_by_names);
            }

            $order_result = $this->buildOrderArray($order, ! empty($unions));
            if (! empty($order_result)) {
                if (empty($order_result['order_type'])) {
                    $query->orderByRaw($order_result['order_column']);
                } else {
                    $query->orderBy(DB::raw($order_result['order_column']), $order_result['order_type']);
                }
            }

            if (! empty($where_string)) {
                $query->whereRaw($where_string);
            }

            if (! empty($having_string)) {
                $query->havingRaw($having_string);
            }
        }

        return $query;
    }

    /**
     * Build an array of columns to select
     *
     * @param  string $prefix          Prefix for the primary table
     * @param  array  $columns         Array of columns to be selected
     * @param  bool   $is_grouped      Whether or not to group on all visible columns
     * @param  array  $group_by_names  The columns to be grouped in an array
     * @param  array  $transformations The transformations for the table
     * @param  bool   $ignore_aliases  If the column string includes aliases
     * @return array
     */
    public function buildColumnsArray($prefix, $columns, $is_grouped, &$group_by_names = NULL, $transformations = [], $ignore_aliases = FALSE)
    {
        $column_array = [];
        $group_by_names = [];
        foreach ($columns as $column) {
            $column = (array) $column;
            $show = TRUE;
            $is_aggregate = array_key_exists('is_aggregate', $column) && $column['is_aggregate'];
            if ($column["prefix"] == "aggregate") {
                if ($is_grouped == FALSE) {
                    $column["column_name"] = $column["column_name"] . ' over()';
                }
            }

            if (key_exists('checked', $column)) {
                $show = $column['checked'];
            }
            if (empty($transformations[$column["prefix"] . "_" . $column["column_name"]])) {
                $table_transformations = [];
            } else {
                $table_transformations = $transformations[$column["prefix"] . "_" . $column["column_name"]];
            }

            if (filter_var($column["added"], FILTER_VALIDATE_BOOLEAN) == TRUE) {
                $alias = $column["column_name"];
            } elseif (empty($column["alias"])) {
                $alias = $column["target_column_name"];
            } else {
                $alias = $column["alias"];
            }

            if (filter_var($column["added"], FILTER_VALIDATE_BOOLEAN) == TRUE) {
                $column["column_name"] = $column["sql_definition"];
                if (filter_var($is_aggregate, FILTER_VALIDATE_BOOLEAN) == TRUE && $is_grouped == FALSE) {
                    $column["column_name"] = $column["column_name"] . ' over()';
                }
            } elseif ($column["prefix"] != "aggregate") {
                $column["column_name"] = '"' . $column["prefix"] . '"."' . $column["column_name"] . '"';
            }

            if (filter_var($column["added"], FILTER_VALIDATE_BOOLEAN) == TRUE && filter_var($is_aggregate, FILTER_VALIDATE_BOOLEAN) == FALSE) {
                $custom_column_name = addslashes($alias);
                $group_by_names[] = '"' . $custom_column_name . '"';
            } elseif ($column["prefix"] != "aggregate" && $show == TRUE && filter_var($is_aggregate, FILTER_VALIDATE_BOOLEAN) == FALSE) {
                $group_by_names[] = $column["column_name"];
            }

            if (array_key_exists('data_type', $column)) {
                $column["column_name"] = $this->buildTransformation($table_transformations, $column["column_name"], $column["data_type"]);
            }

            if ($show == TRUE) {
                if ($ignore_aliases) {
                    $column_array[] = DB::raw($column["column_name"]);
                } else {
                    $column_array[] = DB::raw($column["column_name"] . ' as "' . $alias . '"');
                }
            }
        }

        return $column_array;
    }

    /**
     * Build ORDER BY array
     *
     * @param  object $order Properties of the column being sorted on
     * @return array
     */
    public function buildOrderArray($order, $skip_table_prefix = FALSE)
    {
        $order = (object) $order;
        if (is_null($order) || (empty($order->order_column) && empty($order->custom_expression))) {
            return [];
        }
        $order_column = '';
        $order_type = '';
        $order_result = ['order_column' => '', 'order_type' => ''];
        if (! is_null($order) && ($order->order_column != "" || $order->custom_expression != "")) {
            $order_type = $order->order_type;
            if (! empty($order->custom_expression)) {
                $order_column = $order->custom_expression;
                $order_type = '';
            } elseif ($order->prefix == "aggregate" || $order->prefix == "custom") {
                if ($order->alias == "") {
                    $order_column = '"' . $order->order_column . '"';
                } else {
                    $order_column = '"' . $order->alias . '"';
                }
            } elseif (! empty($order->sql_definition)) {
                $order_column = $order->sql_definition;
            } else {
                $order_column = "\"{$order->prefix}\".\"{$order->order_column}\"";
            }

            if ($skip_table_prefix) {
                // Since unions are subselected, we need to consider the alias of the column
                if (! empty($order->alias)) {
                    $order_column = $order->alias;
                }

                $order_column = str_replace("\"{$order->prefix}\".", '', $order_column);
            }
        }
        $order_result['order_column'] = $order_column;
        $order_result['order_type'] = $order_type;

        return $order_result;
    }

    /**
     * Build GROUP BY strng
     *
     * @param  bool   $is_grouped     Whether or not to group on all visible columnss
     * @param  array  $group_by_names The columns to be grouped in an array
     * @return string
     */
    public function buildGroupString($is_grouped, $group_by_names)
    {
        $group_string = '';
        if ($is_grouped == TRUE) {
            $group_string = "GROUP BY";
            $index = 0;
            foreach ($group_by_names as $name) {
                if ($index == count($group_by_names) - 1) {
                    $group_string .= $name;
                } else {
                    $group_string .= $name . ',';
                }

                ++$index;
            }
        }

        return $group_string;
    }

    /**
     * Determine if filters contain any JSON filters
     *
     * @param  array $filters All filters applied in a multidimentional array
     * @return bool
     */
    public function hasJsonFilters($filters)
    {
        $hasJsonFilter = FALSE;
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                $filter = (object) $filter;
            }

            if (strpos($filter->column, '->')) {
                $hasJsonFilter = TRUE;
            }
        }

        return $hasJsonFilter;
    }

    /**
     * Loop through joins and fetch the schema name for the table used in the join.
     *
     * @param  int    $project_id The id for the project record
     * @param  int    $control_id The id for the partner integration record
     * @param  array  $joins      All joins applied in an array or objects
     * @param  string $prefix     Prefix for the primary table
     * @param  array  $columns    All columns needed for select statement
     * @return array
     */
    public function buildJoinObjects($project, $joins, $prefix, $columns) // todo
    {
        $temp_joins = [];
        foreach ($joins as $join) {
            $join = clone $join;
            $is_transformed = FALSE;
            $source_prefix = $join->source_prefix;
            if (empty($join->source_prefix)) {
                $source_prefix = $prefix;
            }

            if (empty($join->schema)) {
                $join->schema = Table::getSchema($project->primary_database, $join->table, [$project->name, 'public']);
            }

            foreach ($columns as $column) {
                $column = (array) $column;
                if ($column['target_column_name'] == $join->source_target_column && $column["prefix"] == $source_prefix && ! empty($column["sql_definition"])) {
                    $join->source_column = $column["sql_definition"];
                    $join->target_column = '"' . $join->prefix . '"."' . $join->target_column . '"';
                    $is_transformed = TRUE;
                    break;
                }
            }

            if (! $is_transformed) {
                if (! $join->is_custom) {
                    $join->source_column = '"' . $source_prefix . '"."' . $join->source_column . '"';
                }
                $join->target_column = '"' . $join->prefix . '"."' . $join->target_column . '"';
            }

            if ($join->cast == TRUE) {
                $join->source_column = "CAST(" . $join->source_column . " as " . $join->cast_type . ")";
                $join->target_column = "CAST(" . $join->target_column . " as " . $join->cast_type . ")";
            }

            $temp_joins[] = $join;
        }

        return $temp_joins;
    }

    /**
     * Create a string for joining tables from array of objects
     *
     * @param  string $prefix Prefix of selected table
     * @param  array  $joins  All joins applied in an array or objects
     * @return void
     */
    public function buildJoinString($prefix, $joins)
    {
        $join_string = '';
        foreach ($joins as $join) {
            $join_string .= $join->join_type . ' JOIN "' . $join->schema . '"."' . $join->table . '" AS "' . $join->prefix . '" ON ' . $join->target_column . " = " . $join->source_column . " ";
        }

        return $join_string;
    }

    /**
     * Builds the transformation for a column
     *
     * @param  array  $transformations The transformations for this column
     * @param  string $value           The column we are transforming
     * @param  string $data_type       The columns data type
     * @return string
     */
    public function buildTransformation($transformations, $value, $data_type)
    {
        $transformation_groups = [];
        $group_no = 0;
        $prev_type = "";
        foreach ($transformations as $transformation) {
            if ($transformation["transformation_type"] == "IfThen") {
                if ($prev_type != "IfThen") {
                    $if_then_transformations = [];
                    ++$group_no;
                }

                $if_then_transformations[] = $transformation["transformation"];
                $transformation_groups["group_" . $group_no] = ["type" => $transformation["transformation_type"], "transformation" => $if_then_transformations, "data_type" => $data_type];
                $prev_type = $transformation["transformation_type"];
            } elseif ($transformation["transformation_type"] == "ConditionalLogic") {
                if ($prev_type != "ConditionalLogic") {
                    $conditional_logic_transformations = [];
                    ++$group_no;
                } else {
                    ++$group_no;
                }

                $conditional_logic_transformations = $transformation["transformation"];
                $transformation_groups["group_" . $group_no] = ["type" => $transformation["transformation_type"], "transformation" => $conditional_logic_transformations, "data_type" => $data_type];
                $prev_type = $transformation["transformation_type"];
            } else {
                if ($transformation["transformation_type"] == "Cast") {
                    if ($transformation["transformation"]['field_1']['value'] == "decimal(13, 2)") {
                        $data_type = "decimal";
                    } else {
                        $data_type = $transformation["transformation"]['field_1']['value'];
                    }
                }

                ++$group_no;
                $transformation_groups["group_" . $group_no] = ["type" => $transformation["transformation_type"], "transformation" => $transformation["transformation"], "data_type" => $data_type];
                $prev_type = $transformation["transformation_type"];
            }
        }
        foreach ($transformation_groups as $group) {
            $method = "apply" . $group["type"];
            $value = $this->$method($value, $group["transformation"], $group["data_type"]);
        }

        return $value;
    }

    /**
     * Build where clause given filters
     *
     * @param  array  $filters               Array of filters used to construct where clause
     * @param  array  $table_transformations The transformations applied to the table
     * @return string
     */
    public function buildWhereString($filters, $table_transformations)
    {
        $temp_filters = [];
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                $filter = (object) $filter;
            }

            if ($filter->prefix != 'aggregate') {
                if (! property_exists($filter, 'is_aggregate') || $filter->is_aggregate !== TRUE) {
                    $temp_filters[] = json_decode(json_encode($filter));
                }
            }
        }

        return $this->buildFilterString($temp_filters, $table_transformations);
    }

    /**
     * Get the SQL used when filters are applied to unstructured data
     *
     * @param  string $schema        The schema containing the table in the from clause
     * @param  string $prefix        The prefix for the table in the from clause
     * @param  string $table         The table used in the from clause
     * @param  array  $columns_array Array of columns to be retrieved
     * @param  string $where_string  All filters in a where clause
     * @param  string $join_string   All joins applied in a string
     * @param  string $group_string  Group by statement
     * @param  object $order         The type of order I.E. desc, asc
     * @param  int    $limit         How many records are being retrieved (optional)
     * @param  int    $offset        What record number to start the selection at (optional)
     * @return string
     */
    public function buildJsonFilterSelectString(
        $schema,
        $prefix,
        $table,
        $columns_array,
        $where_string,
        $having_string,
        $join_string,
        $group_string,
        $order
    ) {
        $offset_string = "";
        $order_string = "";
        $column_string = implode(', ', $columns_array);

        $order_result = $this->buildOrderArray($order);
        if (! empty($order_result)) {
            $order_string = $order_result['order_column'];
            if (isset($order_result['order_type']) && ! empty($order_result['order_type'])) {
                $order_string .= ' ' . $order_result['order_type'];
            }
        }

        if ($order_string != "") {
            $sql = <<<SQL
                SELECT {$column_string} FROM "{$schema}"."{$table}" as "{$prefix}" {$join_string} 
                WHERE {$where_string} {$group_string} {$having_string} order by {$order_string}
                SQL;
        } else {
            $sql = <<<SQL
                SELECT {$column_string} FROM "{$schema}"."{$table}" as "{$prefix}" {$join_string}
                WHERE {$where_string} {$group_string} {$having_string}
                SQL;
        }

        return $sql;
    }

    /**
     * Build where clause given filters
     *
     * @param  array  $filters               Array of filters used to construct where clause
     * @param  array  $table_transformations The transformations applied to the table
     * @return string
     */
    public function buildHavingString($filters, $table_transformations)
    {
        $temp_filters = [];
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                $filter = (object) $filter;
            }

            if ($filter->prefix == "aggregate" || (property_exists($filter, 'is_aggregate') && $filter->is_aggregate == TRUE)) {
                $temp_filters[] = json_decode(json_encode($filter));
            }
        }

        return $this->buildFilterString($temp_filters, $table_transformations);
    }

    /**
     * Builds the filter string for havings and wheres
     *
     * @param  array  $filters               The filters we are using to build these
     * @param  array  $table_transformations The transformations for the string
     * @return string
     */
    public function buildFilterString($filters, $table_transformations)
    {
        $where_string = "";
        $index = 0;
        foreach ($filters as $filter) {
            if (empty($table_transformations[$filter->prefix . "_" . $filter->column])) {
                $transformations = [];
            } else {
                $transformations = $table_transformations[$filter->prefix . "_" . $filter->column];
            }

            if ($filter->prefix == "custom") {
                $filter->column = $filter->sql_definition;
            } elseif (strpos($filter->sql_definition, 'jsonb') !== FALSE && strpos($filter->sql_definition, '->') !== FALSE) {
                // Are we filtering on a casted JSON field?
                $filter->column = $filter->sql_definition;
            } elseif ($filter->prefix != "aggregate") {
                $filter->column = $this->escapeIdentifierName($filter->prefix) . '.' . $this->escapeIdentifierName($filter->column);
            }

            if (! property_exists($filter, 'data_type')) {
                $filter->data_type = NULL;
            }

            $filter->column = $this->buildTransformation($transformations, $filter->column, $filter->data_type);

            if ($filter->operator == 'between' || $filter->operator == '<' || $filter->operator == '<=' || $filter->operator == '>' || $filter->operator == '>=') {
                $filter->column = $filter->column;
            } elseif ($filter->operator == 'in' || $filter->operator == 'not in') {
                $filter->column = 'CAST(' . $filter->column . ' as text)';
            } else {
                if ($filter->data_type == "character varying" || $filter->data_type == "character" || $filter->data_type == "text") {
                    $filter->column = 'CAST(' . $filter->column . ' as text)';
                } else {
                    $filter->column = $filter->column;
                }
            }

            if (($filter->data_type == "character varying" || $filter->data_type == "character" || $filter->data_type == "text") && ($filter->operator == 'empty' || $filter->operator == 'not empty')) {
                $filter->column = 'LOWER(' . $filter->column . ")";
            }

            if ($filter->data_type == "character varying" || $filter->data_type == "character" || $filter->data_type == "text") {
                $filter->column = 'TRIM(' . $filter->column . ')';
            }

            if ($filter->operator == "in" || $filter->operator == "not in") {
                if ($filter->value->type == 'string') {
                    $vals = $filter->value->info;
                    $filter->value = "(";
                    $index2 = 0;
                    foreach ($vals as $val) {
                        if ($index2 == count($vals) - 1) {
                            $filter->value = $filter->value . "E'" . addslashes($val) . "'";
                        } else {
                            $filter->value = $filter->value . "E'" . addslashes($val) . "'" . ",";
                        }
                        ++$index2;
                    }
                    $filter->value = $filter->value . ")";
                } else {
                    $subselect_column = $filter->value->info->column;
                    $subselect_schema = $filter->value->info->schema;
                    $subselect_table = $filter->value->info->table;
                    $filter->value = "(SELECT CAST(\"$subselect_column\" as text) FROM \"$subselect_schema\".\"$subselect_table\")";
                }
            } elseif ($filter->operator == 'between') {
                if (gettype($filter->value) == "array") {
                    $low_val = str_replace("T", " ", $filter->value[0]);
                    $high_val = str_replace("T", " ", $filter->value[1]);
                    $filter->value = "E'" . addslashes($low_val) . "'";
                    $filter->value = $filter->value . " and E'" . addslashes($high_val) . "'";
                } elseif (isset($filter->value->info) && gettype($filter->value->info) == "object") {
                    $low_val = $filter->value->info->low_val;
                    $high_val = $filter->value->info->high_val;
                    if ($filter->value->type == "interval") {
                        $low_val = json_decode($low_val);
                        $high_val = json_decode($high_val);

                        $direction = $low_val->direction;
                        $time = $low_val->time;
                        $type = $low_val->type;
                        $low_val = "now() $direction INTERVAL '$time $type'";

                        $direction = $high_val->direction;
                        $time = $high_val->time;
                        $type = $high_val->type;
                        $high_val = "now() $direction INTERVAL '$time $type'";
                        $filter->value = $low_val . " and " . $high_val;
                    } else {
                        $low_val = str_replace("T", " ", $low_val);
                        $high_val = str_replace("T", " ", $high_val);
                        $filter->value = "E'" . addslashes($low_val) . "'";
                        $filter->value = $filter->value . " and E'" . addslashes($high_val) . "'";
                    }
                }
            } elseif ($filter->operator == "ilike" || $filter->operator == "not ilike" ||
            $filter->operator == "like" || $filter->operator == "not like") {
                if (strpos($filter->value, "%") === FALSE) {
                    $filter->value = "E'%" . addslashes($filter->value) . "%'";
                } else {
                    $filter->value = "E'" . addslashes($filter->value) . "'";
                }
            } elseif ($filter->operator == 'empty') {
                if ($filter->data_type == "character varying" || $filter->data_type == "character" || $filter->data_type == "text") {
                    $filter->value = "({$filter->column} IS NULL or TRIM({$filter->column}) = '' or LOWER({$filter->column}) = 'null')";
                } else {
                    $filter->value = "({$filter->column} IS NULL)";
                }
            } elseif ($filter->operator == 'not empty') {
                if ($filter->data_type == "character varying" || $filter->data_type == "character" || $filter->data_type == "text") {
                    $filter->value = "({$filter->column} IS NOT NULL and TRIM({$filter->column}) != '' and LOWER({$filter->column}) != 'null')";
                } else {
                    $filter->value = "({$filter->column} IS NOT NULL)";
                }
            } elseif (! empty($filter->value) || $filter->value == 0) {
                if (! empty($filter->value->type) && $filter->value->type == "interval") {
                    $interval_value = json_decode($filter->value->info);
                    $direction = $interval_value->direction;
                    $time = $interval_value->time;
                    $type = $interval_value->type;
                    $filter->value = "now() $direction INTERVAL '$time $type'";
                } else {
                    if ($filter->value == 'null') {
                        $filter->value = "" . addslashes($filter->value) . "";
                        if ($filter->data_type == "character varying" || $filter->data_type == "character" || $filter->data_type == "text") {
                            $filter->value = $filter->value . " or " . $filter->column . " = 'null'";
                        }
                        $filter->operator = 'is';
                    } else {
                        $filter->value = "E'" . addslashes($filter->value) . "'";
                    }
                }
            }

            if ($filter->operator == 'empty' || $filter->operator == 'not empty') {
                if ($index == count($filters) - 1) {
                    $where_string = $where_string . "(" . $filter->value . ")";
                } else {
                    $where_string = $where_string . "(" . $filter->value . ") and ";
                }
            } else {
                if (empty($filter->value) && $filter->value != 0 && $index == count($filters) - 1) {
                    $where_string = $where_string . "(" . $filter->column . " is null or " . $filter->column . " = '')";
                } elseif (empty($filter->value) && $filter->value != 0) {
                    $where_string = $where_string . "(" . $filter->column . " is null or " . $filter->column . " = '') and ";
                } elseif ($index == count($filters) - 1) {
                    $where_string = $where_string . "(" . $filter->column . " " . $filter->operator . " " . $filter->value . ")";
                } else {
                    $where_string = $where_string . "(" . $filter->column . " " . $filter->operator . " " . $filter->value . ") and ";
                }
            }
            ++$index;
        }

        return $where_string;
    }

    /**
     * Find the schema name for a table in a given project
     *
     * @param  Project $project_id The project to search
     * @param  string  $table_name The table name to find
     * @return string  The suspected schema for the table
     */
    public function getProjectTableSchema($project, $table_name)
    {
        $schemas = implode(',', ["'public'", "'$project->name'"]);

        $connection = app(Connection::class)->connect($project->primary_database);

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

        $results = $connection->select($sql, [$table_name]);

        if (! empty($results)) {
            return $results[0]->table_schema;
        }

        return 'public';
    }

    /**
     * Return array of all columns and set the visiblity and alias based
     * on array in $visible_columns.
     *
     * @param  int    $project_id      The id for the project record
     * @param  int    $control_id      the id for the partner integration record
     * @param  string $prefix          The selected table's prefix
     * @param  string $schema          The schema of for the table
     * @param  string $table           The table to get columns for
     * @param  array  $joins           The list of joins to the table
     * @param  array  $transformations The transformations for the table (optional)
     * @param  array  $visible_columns An array containing visible column settings
     * @param  string $previous_prefix The previous prefix of the table
     * @return array
     */
    public function getProjectTableColumnsForVisible($project, $prefix, $schema, $table, $joins, $transformations, $visible_columns, $previous_prefix)
    {
        $connection = app(Connection::class)->connect($project->primary_database);

        if (empty($schema)) {
            $schema = $this->getProjectTableSchema($project, $table);
        }

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

        $results = $connection->select($sql, [$table, $schema]);
        $type = '';

        if (empty($results)) {
            $type = 'normal';
        } else {
            $type = $results[0]->table_type;
        }

        $columns_array = [];

        $visible_columns_array = [];
        $table_key_columns = [];
        foreach ($visible_columns as $column) {
            if (! array_key_exists('table_name', $column)) {
                $column['table_name'] = '';
            }

            $key = $this->getPrefixedColumnName($column);
            $visible_columns_array[$key] = $column;

            $table_key_columns[$column["table_name"] . "_" . $column["column_name"]] = $column;
            if (filter_var($column["added"], FILTER_VALIDATE_BOOLEAN) == TRUE) {
                $columns_array[$key] = $column;
            }
        }

        if ($type == "normal") {  // For Table or Normal View
            $columns = $connection->table('information_schema.columns')
                ->select(DB::raw("column_name, table_catalog, table_name, data_type, character_maximum_length, numeric_scale, numeric_precision, '$prefix' as prefix, '' as sql_definition"))
                ->where('table_schema', $schema)
                ->where('table_name', $table)
                ->where('table_catalog', $project->primary_database->database)
                ->orderBy('ordinal_position')
                ->get()
                ->map(fn($col) => (array) $col)
                ->toArray();
        } else {
            $columns = $connection->table('pg_catalog.pg_attribute')
                ->join('pg_catalog.pg_class', 'pg_catalog.pg_class.oid', '=', 'pg_catalog.pg_attribute.attrelid')
                ->join('pg_catalog.pg_namespace', 'pg_catalog.pg_namespace.oid', '=', 'pg_catalog.pg_class.relnamespace')
                ->join('pg_catalog.pg_type', 'pg_catalog.pg_type.typelem', '=', 'pg_catalog.pg_attribute.atttypid')
                ->select(DB::raw("pg_catalog.pg_attribute.attname as column_name, 
                    (regexp_split_to_array(pg_catalog.format_type(pg_catalog.pg_attribute.atttypid, pg_catalog.pg_attribute.atttypmod), '\('))[1] as data_type,
                    information_schema._pg_char_max_length(pg_catalog.pg_attribute.atttypid, pg_catalog.pg_attribute.atttypmod) as character_maximum_length, 
                    information_schema._pg_numeric_scale(pg_catalog.pg_attribute.atttypid, pg_catalog.pg_attribute.atttypmod)  as numeric_scale,
                    information_schema._pg_numeric_precision(pg_catalog.pg_attribute.atttypid, pg_catalog.pg_attribute.atttypmod) as numeric_precision,
                    pg_catalog.pg_class.relname as table_name,
                    '$prefix' as prefix,
                    '' as sql_definition"))
                ->where('pg_catalog.pg_namespace.nspname', $schema)
                ->where('pg_catalog.pg_class.relname', $table)
                ->where(function ($query) {
                    $query->where('pg_catalog.pg_attribute.attisdropped', '=', FALSE)
                        ->whereRaw("cast(pg_catalog.pg_type.typanalyze as text) = 'array_typanalyze'")
                        ->where('pg_catalog.pg_attribute.attnum', '>', 0);
                })
                ->orderBy('pg_catalog.pg_attribute.attnum')
                ->get()
                ->map(fn($col) => (array) $col)
                ->toArray();
        }

        $columns = array_map(fn ($column) => (array) $column, $columns);

        array_unshift($columns, ["column_name" => "count(*)", "alias" => "count__records", "prefix" => "aggregate", "sql_definition" => "", "added" => FALSE, "data_type" => "integer"]);
        $target_columns = [];

        foreach ($columns as $key => $column) {
            $columns[$key]["added"] = FALSE;

            if ($column['column_name'] == 'count(*)') {
                $target_column_name = $column['column_name'];
            } elseif (count($joins) > 0) {
                $target_column_name = $prefix . '_' . $column['column_name'];
            } else {
                $target_column_name = $column['column_name'];
            }
            $target_column_name = $this->fixIdentifierName($target_column_name, $target_columns);
            $columns[$key]['target_column_name'] = $target_column_name;
            $target_columns[] = $target_column_name;
        }

        foreach ($columns as $column) {
            $column['editing'] = FALSE;
            $key = $this->getPrefixedColumnName($column);
            $columns_array[$key] = $column;
        }

        foreach ($joins as $join) {
            $table = $join['table'];
            $prefix = $join['prefix'];
            $new = $join['new'];
            if (! isset($join['schema']) || empty($join['schema'])) {
                $schema = $this->getProjectTableSchema($project, $table);
            } else {
                $schema = $join['schema'];
            }

            $join_columns = $this->getJoinTableColumns($project, $table, $prefix, $schema);

            foreach ($join_columns as $join_column) {
                $join_column["added"] = FALSE;

                $table_column_key = $join_column['table_name'] . '_' . $join_column['column_name'];
                $target_column_name = $join_column['prefix'] . '_' . $join_column['column_name'];
                $target_column_name = $this->fixIdentifierName($target_column_name, $target_columns);
                $target_columns[] = $target_column_name;
                $join_column['target_column_name'] = $target_column_name;

                if ($new === TRUE) {
                    $join_column['checked'] = TRUE;
                }

                $join_column['editing'] = FALSE;
                $join_column['alias'] = '';
                if (count($visible_columns) > 0) {
                    $key = $this->getPrefixedColumnName($join_column);
                    $columns_array[$key] = $join_column;
                } else {
                    $columns[] = $join_column;
                }
            }
        }

        if (count($visible_columns_array) > 0) {
            $columns = [];
            $visible_count = 0;

            foreach ($visible_columns_array as $column) {
                $key = $this->getPrefixedColumnName($column);
                if (array_key_exists($key, $columns_array)) {
                    if (array_key_exists('checked', $columns_array[$key]) === FALSE) {
                        $columns_array[$key]['checked'] = $visible_columns_array[$key]['checked'];
                    }
                    if (isset($visible_columns_array[$key]['alias'])) {
                        $columns_array[$key]['alias'] = $visible_columns_array[$key]['alias'];
                    } else {
                        $columns_array[$key]['alias'] = "";
                    }

                    if ($visible_count < 400 && $columns_array[$key]['checked'] == TRUE) {
                        $visible_count = $visible_count + 1;
                    } else {
                        $columns_array[$key]['checked'] = FALSE;
                    }

                    $columns[] = $columns_array[$key];
                    unset($columns_array[$key]);
                }
            }

            $joined_columns = [];

            // Get the joined table's columns so that they're shown by default, unless already hidden via column preferences
            foreach ($joins as $join) {
                foreach ($join['target_columns'] as $column) {
                    $joined_columns[] = $join['prefix'] . '_' . $column['column_name'];
                }
            }

            foreach ($columns_array as $column) {
                $key = $this->getPrefixedColumnName($column);

                if (array_key_exists($key, $visible_columns_array)) {
                    if (array_key_exists('checked', $column) === FALSE) {
                        $column['checked'] = $visible_columns_array[$key]['checked'];
                    }

                    if (isset($visible_columns_array[$key]['alias'])) {
                        $column['alias'] = $visible_columns_array[$key]['alias'];
                    } else {
                        $column['alias'] = "";
                    }
                } else {
                    // Assume defaults for newly detected columns
                    $column['alias'] = '';

                    // If the column is in the joined_columns array, it's already been added to the display preference list, just not saved to the database yet.
                    if (in_array($column['target_column_name'], $joined_columns)) {
                        $column['checked'] = TRUE;
                    } else {
                        $column['checked'] = FALSE;
                    }
                }

                if ($visible_count < 400 && $column['checked'] == TRUE) {
                    $visible_count = $visible_count + 1;
                } else {
                    $column['checked'] = FALSE;
                }

                if ($column["column_name"] == "count(*)") {
                    array_unshift($columns, $column);
                } else {
                    $columns[] = $column;
                }
            }
        } else {
            $index = 0;
            foreach ($columns as $key => $column) {
                if ($index != 0) {
                    $columns[$key]['checked'] = TRUE;
                } else {
                    $columns[$key]['checked'] = FALSE;
                }

                $columns[$key]['editing'] = FALSE;
                if (! isset($columns[$key]['alias'])) {
                    $columns[$key]['alias'] = "";
                }

                ++$index;
            }
        }

        $target_columns = [];
        foreach ($columns as $key => $column) {
            if (empty($transformations[$column["prefix"] . "_" . $column["column_name"]])) {
                $table_transformations = [];
            } else {
                $table_transformations = $transformations[$column["prefix"] . "_" . $column["column_name"]];
            }

            if ($column["prefix"] != "custom") {
                $transformation_value = "";
                if (count($table_transformations) > 0) {
                    if ($column["prefix"] == "aggregate") {
                        $transformation_value = $column["column_name"];
                    } else {
                        $transformation_value = '"' . $column["prefix"] . '"."' . $column["column_name"] . '"';
                    }
                }

                $transformation_value = $this->buildTransformation($table_transformations, $transformation_value, $columns[$key]["data_type"]);

                foreach ($table_transformations as $transformation) {
                    if ($transformation["transformation_type"] == "Cast") {
                        $scale = NULL;
                        $precision = NULL;
                        if ($transformation["transformation"]['field_1']['value'] == "decimal(13, 2)") {
                            $type = "decimal";
                            $scale = 2;
                            $precision = 13;
                        } else {
                            $type = $transformation["transformation"]['field_1']['value'];
                        }

                        $columns[$key]["data_type"] = $type;
                        $columns[$key]["character_maximum_length"] = NULL;
                        $columns[$key]["numeric_scale"] = $scale;
                        $columns[$key]["numeric_precision"] = $precision;
                    }
                }

                $columns[$key]["sql_definition"] = $transformation_value;
            }
        }

        return $columns;
    }

    public function escapeIdentifierName($identifier)
    {
        preg_match("/(\")(.*)(\")(.*)/", $identifier, $matches);
        if (count($matches) > 0) {
            $identifier = str_replace('"', '""', $matches[2]);
            $identifier = '"' . $identifier . '"';
            if (count($matches) > 4) {
                $identifier .= $matches[4];
            }
        } else {
            $identifier = str_replace('"', '""', $identifier);
            $identifier = '"' . $identifier . '"';
        }

        return $identifier;
    }

    /**
     * applyFindReplace applies the find and replace transformation.
     *
     * @param  string $column         The name of the column
     * @param  array  $transformation The Transformation values
     * @param  string $data_type      The data type of the column being transformed
     * @return string
     */
    public function applyFindReplace($column, $transformation, $data_type)
    {
        $column = <<<SQL
            REPLACE(CAST({$column} as text), '{$transformation["field_1"]["value"]}', '{$transformation["field_2"]["value"]}')
            SQL;

        return $column;
    }

    /**
     * applyIfThen applies the find and replace transformation.
     *
     * @param  string $column         The name of the column
     * @param  array  $transformation The If Then values
     * @param  string $data_type      The data type of the column being transformed
     * @return string
     */
    public function applyIfThen($column, $transformations, $data_type)
    {
        $sql = <<<SQL
            CASE 
            SQL;

        foreach ($transformations as $transformation) {
            if ($this->needsQuotes($data_type)) {
                $transformation["field_2"]["value"] = "'" . $transformation["field_2"]["value"] . "'";
            }

            $sql = $sql . <<<SQL
                WHEN {$column} {$transformation['field_1']['value']} {$transformation["field_2"]["value"]} THEN '{$transformation["field_3"]["value"]}' 
                SQL;
        }

        $sql = $sql . <<<SQL
            ELSE {$column} END
            SQL;

        return $sql;
    }

    /**
     * ConditionalLogic applies the find and replace transformation.
     *
     * @param  string $column         The name of the column
     * @param  array  $transformation The If Then Else If values
     * @param  string $data_type      The data type of the column being transformed
     * @return string
     */
    public function applyConditionalLogic($column, $transformations, $data_type)
    {
        $if_array = [];
        $else_array = [];
        $else_if_array = [];
        $sql = <<<SQL
            CASE 
            SQL;

        foreach ($transformations as $key => $value) {
            if (array_key_exists("if_field_1", $value) || array_key_exists("if_field_2", $value) || array_key_exists("if_field_3", $value)) {
                $if_array = $value;
            } elseif (array_key_exists("else_field_1", $value) || array_key_exists("else_field_2", $value)) {
                $else_array = $value;
            } else {
                $else_if_array[$key] = $value;
            }
        }

        // Appending if
        if ($this->needsQuotes($data_type)) {
            $if_array["if_field_2"]["value"] = "'" . $if_array["if_field_2"]["value"] . "'";
        }

        $sql = $sql . <<<SQL
            WHEN {$column} {$if_array['if_field_1']['value']} {$if_array["if_field_2"]["value"]} THEN '{$if_array["if_field_3"]["value"]}' 
            SQL;

        // Appending else if
        foreach ($else_if_array as $key => $value) {
            if ($this->needsQuotes($data_type)) {
                $value["else_if_field_2"]["value"] = "'" . $value["else_if_field_2"]["value"] . "'";
            }

            $sql = $sql . <<<SQL
                WHEN {$column} {$value['else_if_field_1']['value']} {$value["else_if_field_2"]["value"]} THEN '{$value["else_if_field_3"]["value"]}' 
                SQL;
        }

        // Appending else
        $else_status = 0;
        if (! empty($else_array)) {
            if (! empty($else_array["else_field_1"]["value"])) {
                if ($this->needsQuotes($data_type)) {
                    $else_array["else_field_1"]["value"] = "'" . $else_array["else_field_1"]["value"] . "'";
                }

                $sql = $sql . <<<SQL
                    ELSE  {$else_array["else_field_1"]["value"]}  END
                    SQL;
                $else_status = 0;
            } else {
                $else_status = 1;
            }
        } else {
            $else_status = 1;
        }

        if ($else_status == 1) {
            $sql = $sql . <<<SQL
                         ELSE {$column} END
                SQL;
        }

        return $sql;
    }

    /**
     * applyUpperLower applies the UPPER and LOWER transformation.
     *
     * @param  string $column         The name of the column
     * @param  array  $transformation The Transformation values
     * @param  string $data_type      The data type of the column being transformed
     * @return string
     */
    public function applyUpperLower($column, $transformation, $data_type)
    {
        $column = <<<SQL
            {$transformation['field_1']['value']}({$column})
            SQL;

        return $column;
    }

    /**
     * applyCast applies the Cast transformation.
     *
     * @param  string $column         The name of the column
     * @param  array  $transformation The Transformation values
     * @param  string $data_type      The data type of the column being transformed
     * @return string
     */
    public function applyCast($column, $transformation, $data_type)
    {
        $column = <<<SQL
            CAST({$column} as {$transformation['field_1']['value']})
            SQL;

        return $column;
    }

    /**
     * Get table columns for table added via a join
     *
     * @param  int    $control_id The id for the partner integration record
     * @param  string $table      The table to get columns for
     * @param  string $prefix     The prefix applied to the table
     * @param  string $schema     The schema containing the table
     * @return array
     * @todo I don't like this here. It's also VERY similar to self::getProjectTableColumns
     */
    public function getJoinTableColumns(Project $project, $table, $prefix, $schema = "public")
    {
        $connection = self::connect($project->primary_database);
        
        if (empty($schema)) {
            $schema = Table::getSchema($project->primary_database, $table, [$project->name, 'public']);
        }

        $type = Table::getTableType($project->primary_database, $schema, $table);

        if ($type == "normal") {
            $columns = $connection->table("information_schema.columns")
                ->where("table_schema", $schema)
                ->where("table_name", $table)
                ->orderBy("ordinal_position")
                ->select(["column_name", "table_name", "data_type", "character_maximum_length", "numeric_scale", "numeric_precision", DB::raw("'{$prefix}' as prefix"), DB::raw("'' as transformation"), DB::raw("'' as sql_definition")])
                ->get();
        } else {
            $columns = $connection->table("pg_catalog.pg_attribute as attr")
                ->select([
                    "attr.attname as column_name",
                    "cls.relname as table_name",
                    DB::raw("(regexp_split_to_array(pg_catalog.format_type(attr.atttypid, attr.atttypmod), '\('))[1] as data_type"),
                    DB::raw("information_schema._pg_char_max_length(attr.atttypid, attr.atttypmod) as character_maximum_length"), 
                    DB::raw("information_schema._pg_numeric_scale(attr.atttypid, attr.atttypmod)  as numeric_scale"),
                    DB::raw("information_schema._pg_numeric_precision(attr.atttypid, attr.atttypmod) as numeric_precision"),
                    DB::raw("'$prefix' as prefix"),
                    DB::raw("'' as transformation"),
                    DB::raw("'' as sql_definition"),
                ])
                ->where("ns.nspname", $schema)
                ->where("cls.relname", $table)
                ->where(DB::raw('cast("tp"."typanalyze" as text)'), "array_typanalyze")
                ->where("attr.attnum", ">", "0")
                ->where(DB::raw("not attr.attisdropped"))
                ->join("pg_catalog.pg_class as cls", "cls.oid", "=", "attr.attrelid")
                ->join("pg_catalog.pg_namespace as ns", "ns.oid", "=", "cls.relnamespace")
                ->join("pg_catalog.pg_type as tp", "tp.typelem", "=", "attr.atttypid")
                ->orderBy("attr.attnum")
                ->get();
        }

        return array_map(fn($column) => (array) $column, $columns->toArray());
    }

    /**
     * Return prefixed column name based on array values
     *
     * @param  array $column Array containing properties of column
     * @return void
     */
    public function getPrefixedColumnName($column)
    {
        if ($column['column_name'] == 'count(*)') {
            $column_name = 'aggregate_' . $column['column_name'];
        } else {
            $column_name = $column['prefix'] . '_' . $column['column_name'];
        }

        return $column_name;
    }

    /**
     * Append a join to the query passed in
     * @param  object $query              The query to append the join to
     * @param  string $join_schema        The schema of the table to join
     * @param  string $join_table         The table to join
     * @param  string $join_prefix        The prefix of the table to join
     * @param  string $join_target_column The column to join on
     * @param  string $join_source_column The column to match the join on
     * @param  string $join_type          The type of join to perform
     * @param  string $cast_as            The type to cast the join columns as to eliminate type differences
     * @return void
     * @todo add tests - these will be fun
     */
    public function applyJoinToQuery($query, $join_schema, $join_table, $join_prefix, $join_target_column, $join_source_column, $join_type = 'inner', $cast_as = NULL)
    {
        $join_type = strtolower($join_type);

        if ($cast_as) {
            $join_target_column = "CAST({$join_target_column} AS {$cast_as})";
            $join_source_column = "CAST({$join_source_column} AS {$cast_as})";
        }

        if ($join_type === 'inner') {
            $query->join(
                DB::raw("\"{$join_schema}\".\"{$join_table}\" as \"{$join_prefix}\""),
                DB::raw("{$join_target_column}"),
                "=",
                DB::raw("{$join_source_column}")
            );
        }

        if ($join_type === 'left') {
            $query->leftJoin(
                DB::raw("\"{$join_schema}\".\"{$join_table}\" as \"{$join_prefix}\""),
                DB::raw("{$join_target_column}"),
                "=",
                DB::raw("{$join_source_column}")
            );
        }

        if ($join_type === 'right') {
            $query->rightJoin(
                DB::raw("\"{$join_schema}\".\"{$join_table}\" as \"{$join_prefix}\""),
                DB::raw("{$join_target_column}"),
                "=",
                DB::raw("{$join_source_column}")
            );
        }

        return $query;
    }

    /**
     * Build the column mapping for a union query. We want a k => v pair of
     * base table column => union table column so we can alias them together later in queries
     */
    public function buildUnionColumnMapping(array $unions, array $columns): array
    {
        $columns = array_map(fn($column) => (array) $column, $columns);

        $columns = array_filter($columns, function ($column) {
            return $column['checked'];
        });

        $columns_to_use = array_map(function ($column) {
            if (filter_var($column["added"], FILTER_VALIDATE_BOOLEAN) == TRUE) {
                return $column["column_name"];
            }

            if (empty($column["alias"])) {
                return $column["target_column_name"];
            }

            return $column['alias'];
        }, $columns);

        return array_map(function ($union) use ($columns_to_use) {
            $union = (array) $union;
            $union['columns'] = array_combine($union['columns'], $columns_to_use);

            return $union;
        }, $unions);
    }
}
