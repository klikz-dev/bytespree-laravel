<?php

namespace App\Classes\Database;

use App\Models\PartnerIntegration;
use App\Models\Explorer\Project;
use Exception;
use DB;

class StudioQuery extends Connection
{
    private $connection = NULL; // I really don't like having this, but to cross between methods, I need it

    /**
     * getEstimatedRecords gets estimated record count for selected table.
     *
     * @param  Project $project The project record being used
     * @param  string  $table   The table that we are fetching records from
     * @param  string  $schema  The schema of the table we are fetching records from
     * @return int     The estimated record count via the pg_catalog record
     */
    public function getEstimatedRecords($project, $table, $schema) : int
    {
        $foreign_schemas = $project->getSchemas();
        $foreign_schema_database = array_search($schema, $foreign_schemas);

        if ($foreign_schema_database !== FALSE) {
            $connection = app(Connection::class)->connect(PartnerIntegration::where('database', $foreign_schema_database)->first());
            $schema = 'public';
        } else {
            $connection = app(Connection::class)->connect($project->primary_database);
        }

        $results = $connection->table('pg_catalog.pg_class')
            ->select('*')
            ->where('pg_catalog.pg_namespace.nspname', '=', $schema)
            ->where('pg_catalog.pg_class.relname', '=', $table)
            ->join('pg_catalog.pg_namespace', 'pg_catalog.pg_class.relnamespace', '=', 'pg_catalog.pg_namespace.oid')
            ->first();

        if ($results) {
            return $results->reltuples;
        }

        return 0;
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
     * Generate and run a query built on provided parameter
     *
     * @param  int    $project_id      The id for the project record
     * @param  string $prefix          Prefix for the primary table
     * @param  string $table           The table that we are fetching records from
     * @param  string $schema          The schema for the table that we are fetching records from
     * @param  string $username        Current users username
     * @param  bool   $count_records   Whether or not record count should be returned (optional)
     * @param  array  $filters         All filters the user applied (optional)
     * @param  array  $joins           All joins to the table the user applied (optional)
     * @param  object $order           Properties of the column being sorted on (optional)
     * @param  array  $columns         Array of column preferences the user has applied (optional)
     * @param  bool   $is_grouped      Whether or not to group on all visible columns (optional)
     * @param  int    $limit           How many records are being retrieved (optional)
     * @param  int    $offset          What record number to start the selection at (optional)
     * @param  array  $transformations The transformations for the table (optional)
     * @return array
     */
    public function getRecords(
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
        $limit = 10,
        $offset = 0,
        $count_records = FALSE,
        $unions = [],
        $union_all = FALSE,
    ) {
        if (empty($schema)) {
            $schema = $this->getProjectTableSchema($project, $table);
        }

        $where_string = "";
        $group_by_names = [];

        $filters = is_array($filters) ? $filters : [];
        $joins = is_array($joins) ? $joins : [];
        $columns = is_array($columns) ? $columns : [];
        $transformations = is_array($transformations) ? $transformations : [];
        $unions = is_array($unions) ? $unions : [];

        $unions = $this->buildUnionColumnMapping($unions, $columns);

        // Using by reference (&) in this scenario is a bit of an anti-pattern. It's not clear that group_by_names is being modified in the function. todo: Maybe redesign this a bit?
        $columns_array = $this->buildColumnsArray($prefix, $columns, $is_grouped, $group_by_names, $transformations, FALSE); 

        $temp_joins = $this->buildJoinObjects($project, $joins, $prefix, $columns);

        $where_string = $this->buildWhereString($filters, $transformations);

        $having_string = $this->buildHavingString($filters, $transformations);

        // Are we filtering on unstructured data?
        if ($this->hasJsonFilters($filters)) {
            $join_string = $this->buildJoinString($prefix, $temp_joins);

            $group_string = $this->buildGroupString($is_grouped, $group_by_names);

            $sql = $this->buildJsonFilterSelectString($schema, $prefix, $table, $columns_array, $where_string, $having_string, $join_string, $group_string, $order, $limit, $offset);

            if ($count_records) {
                $sql = <<<SQL
                    SELECT count(*) over() FROM
                    "{$schema}"."{$table}" as "{$prefix}" {$join_string} WHERE {$where_string} {$group_string} {$having_string}
                    SQL;

                $results = app(Connection::class)->connect($project->primary_database)->select($sql);

                if (empty($results)) {
                    $total = 0;
                } else {
                    $total = $results[0]->count;
                }

                return compact('total');
            }

            $this->connection = app(Connection::class)->connect($project->primary_database);

            $records = $this->connection->select($sql);

            return compact('records');
        }

        $records = [];

        try {
            if ($count_records) {
                return $this->getRecordCount($project, $schema, $prefix, $table, $columns_array, $group_by_names, $where_string, $having_string, $temp_joins, $is_grouped, $unions, $union_all);
            }

            $this->connection = app(Connection::class)->connect($project->primary_database);

            $query = $this->buildSelect($schema, $prefix, $table, $columns_array, $group_by_names, $where_string, $having_string, $temp_joins, $order, $is_grouped, $limit, $offset, $unions, $union_all);

            $records = $query->get();

            return compact('records');
        } catch (Exception $e) {
            throw $e; // Bubble it up to the browser for now. @todo Gracefully fail
        }
    }

    /**
     * Return the number of records the current query will yield.
     *
     * @param  string $control_id     The schema containing the table in the from clause
     * @param  string $schema         The schema containing the table in the from clause
     * @param  string $prefix         The prefix for the table in the from clause
     * @param  string $table          The table used in the from clause
     * @param  array  $columns_array  Array of column names to select
     * @param  array  $group_by_names List of columns to group on
     * @param  string $where_string   All filters in a where clause
     * @param  array  $joins          All joins to the table the user applied
     * @param  bool   $is_grouped     Whether or not to group on all visible columns
     * @return int
     */
    public function getRecordCount(
        $project,
        $schema,
        $prefix,
        $table,
        $columns_array,
        $group_by_names,
        $where_string,
        $having_string,
        $joins,
        $is_grouped,
        $unions,
        $union_all = FALSE
    ) {
        $estimated_counts = $this->getEstimatedRecords($project, $table, $schema);

        if ($estimated_counts > 5000000 && empty($where_string) && count($joins) == 0) {
            return ['total' => $estimated_counts, 'is_estimated' => TRUE];
        }

        $connection = app(Connection::class)->connect($project->primary_database);

        $query = $connection->table(DB::raw("\"$schema\".\"$table\" as \"$prefix\""));

        foreach ($joins as $join) {
            $this->applyJoinToQuery($query, $join->schema, $join->table, $join->prefix, $join->target_column, $join->source_column, $join->join_type);
        }

        if (! empty($where_string)) {
            $query->whereRaw($where_string);
        }

        if (! empty($having_string)) {
            $query->havingRaw($having_string);
        }

        foreach ($unions as $union) {
            $union_query = $connection->table(DB::raw("\"{$union['schema']}\".\"{$union['table']}\""));

            // Map our unioned columns to the base table columns
            foreach ($union['columns'] as $original_column => $aliased_to_match_base_table_column ) {
                $union_query->addSelect(DB::raw("\"{$original_column}\" as \"{$aliased_to_match_base_table_column}\""));
            }

            $query->union(
                $union_query,
                $union_all
            );
        }

        try {
            if ($is_grouped == TRUE || ! empty($unions)) {
                $query->groupBy($group_by_names);

                $columns_array = array_map(fn($column) => DB::raw($column), $columns_array);

                $query->select($columns_array);

                $total = $connection->query()->fromSub($query, 'aggregate_subquery')->count();
            } else {
                $total = $query->count();
            }
        } catch (Exception $e) {
            throw $e; // Bubble it up to the browser for now. @todo
            $total = 0;
        }

        return ['total' => $total, 'is_estimated' => FALSE];
    }

    /**
     * Get the SQL used when filters are applied to unstructured data
     *
     * @param  string       $schema        The schema containing the table in the from clause
     * @param  string       $prefix        The prefix for the table in the from clause
     * @param  string       $table         The table used in the from clause
     * @param  array        $columns_array Array of columns to be retrieved
     * @param  string       $where_string  All filters in a where clause
     * @param  string       $join_string   All joins applied in a string
     * @param  string       $group_string  Group by statement
     * @param  object       $order         The type of order I.E. desc, asc
     * @param  int          $limit         How many records are being retrieved (optional)
     * @param  int          $offset        What record number to start the selection at (optional)
     * @param  Connection   $connection    The connection to use (optional)
     * @return string
     * @return QueryBuilder
     * @todo write tests
     * @todo Refactor to remove raw SQL writing so we have a unified way of building queries
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
        $order,
        $limit = 0,
        $offset = 0,
        $connection = NULL
    ) {
        $offset_string = "";
        $order_string = "";
        $column_string = implode(', ', $columns_array);

        if ($limit > 0) {
            $offset_string = "offset {$offset} limit {$limit}";
        }

        $order_result = $this->buildOrderArray($order);
        if (! empty($order_result)) {
            $order_string = $order_result['order_column'];
            if (isset($order_result['order_type']) && ! empty($order_result['order_type'])) {
                $order_string .= ' ' . $order_result['order_type'];
            }
        }

        if (is_null($connection)) {
            if ($order_string != "") {
                return <<<SQL
                    SELECT {$column_string} FROM "{$schema}"."{$table}" as "{$prefix}" {$join_string} 
                    WHERE {$where_string} {$group_string} {$having_string} order by {$order_string} {$offset_string}
                    SQL;
            }

            return <<<SQL
                SELECT {$column_string} FROM "{$schema}"."{$table}" as "{$prefix}" {$join_string}
                WHERE {$where_string} {$group_string} {$having_string} {$offset_string}
                SQL;
        } 

        $query = $connection->table(DB::raw("\"$schema\".\"$table\" as \"$prefix\""))
            ->selectRaw($column_string)
            ->whereRaw($where_string);

        if (! empty($having_string)) {
            $query->havingRaw($having_string);
        }

        if (! empty($order_string)) {
            $query->orderByRaw($order_string);
        }

        return $query;
    }

    /**
     * Use query builder to build the select statement used for getting records.
     *
     * @param  string $schema         The schema containing the table in the from clause
     * @param  string $prefix         The prefix for the table in the from clause
     * @param  string $table          The table used in the from clause
     * @param  array  $columns_array  Array of columns to be retrieved
     * @param  array  $group_by_names The columns to be grouped in an array
     * @param  string $where_string   All filters in a where clause
     * @param  array  $joins          All joins applied in an array of objects
     * @param  object $order          Properties of the column being sorted on
     * @param  bool   $is_grouped     Whether or not to group on all visible columns
     * @param  int    $limit          How many records are being retrieved (optional)
     * @param  int    $offset         What record number to start the selection at (optional)
     * @return object
     */
    public function buildSelect(
        $schema,
        $prefix,
        $table,
        $columns_array,
        $group_by_names,
        $where_string,
        $having_string,
        $joins,
        $order,
        $is_grouped,
        $limit = 0,
        $offset = 0,
        $unions = [],
        $union_all = FALSE
    ) {
        $query = $this->connection->table(DB::raw("\"{$schema}\".\"{$table}\" as \"{$prefix}\""));

        foreach ($joins as $join) {
            $query = $this->applyJoinToQuery(
                $query,
                $join->schema,
                $join->table,
                $join->prefix,
                $join->source_column,
                $join->target_column,
                $join->join_type,
                $join->cast ? $join->cast_type : NULL
            );
        }

        $columns_array = array_map(fn($column) => DB::raw($column), $columns_array);

        $query->select($columns_array);

        foreach ($unions as $union) {
            $union_query = $this->connection->table(DB::raw("\"{$union['schema']}\".\"{$union['table']}\""));

            // Map our unioned columns to the base table columns
            foreach ($union['columns'] as $original_column => $aliased_to_match_base_table_column ) {
                $union_query->addSelect(DB::raw("\"{$original_column}\" as \"{$aliased_to_match_base_table_column}\""));
            }

            $query->union(
                $union_query,
                $union_all
            );
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

        if ($is_grouped == TRUE) {
            $query->groupBy($group_by_names);
        }

        if (! empty($having_string)) {
            $query->havingRaw($having_string);
        }

        // Always limit AFTER unions are applied
        if ($limit > 0) {
            $query->limit($limit);
            $query->offset($offset);
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

            if (! array_key_exists('is_aggregate', $column)) {
                $column['is_aggregate'] = FALSE;
            }

            if (! array_key_exists('data_type', $column)) {
                $column['data_type'] = 'varchar';
            }

            if (filter_var($column["added"], FILTER_VALIDATE_BOOLEAN) == TRUE) {
                $column["column_name"] = $column["sql_definition"];
                if (filter_var($column["is_aggregate"], FILTER_VALIDATE_BOOLEAN) == TRUE && $is_grouped == FALSE) {
                    $column["column_name"] = $column["column_name"] . ' over()';
                }
            } elseif ($column["prefix"] != "aggregate") {
                $column["column_name"] = '"' . $column["prefix"] . '"."' . $column["column_name"] . '"';
            }

            if (filter_var($column["added"], FILTER_VALIDATE_BOOLEAN) == TRUE && filter_var($column["is_aggregate"], FILTER_VALIDATE_BOOLEAN) == FALSE) {
                $custom_column_name = addslashes($alias);
                $group_by_names[] = DB::raw('"' . $custom_column_name . '"');
            } elseif ($column["prefix"] != "aggregate" && $show == TRUE && filter_var($column["is_aggregate"], FILTER_VALIDATE_BOOLEAN) == FALSE) {
                $group_by_names[] = DB::raw($column["column_name"]);
            }

            $column["column_name"] = $this->buildTransformation($table_transformations, $column["column_name"], $column["data_type"]);

            if ($show == TRUE) {
                if ($ignore_aliases) {
                    $column_array[] = $column["column_name"];
                } else {
                    $column_array[] = $column["column_name"] . ' as "' . $alias . '"';
                }
            }
        }

        return $column_array;
    }

    public function buildUnionColumnMapping(array $unions, array $columns)
    {
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
            $union['columns'] = array_combine($union['columns'], $columns_to_use);

            return $union;
        }, $unions);
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
     * @param  int    $project The project record
     * @param  array  $joins   All joins applied in an array or objects
     * @param  string $prefix  Prefix for the primary table
     * @param  array  $columns All columns needed for select statement
     * @return array
     * @todo add tests to support variants
     */
    public function buildJoinObjects($project, $joins, $prefix, $columns)
    {
        $temp_joins = [];
        foreach ($joins as $join) {
            $join = clone (object) $join;
            $is_transformed = FALSE;
            $source_prefix = $join->source_prefix;
            if (empty($join->source_prefix)) {
                $source_prefix = $prefix;
            }

            if (empty($join->schema)) {
                $join->schema = $this->getProjectTableSchema($project, $join->table);
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
     * @todo add test
     * @todo Remove $prefix param or use it (original does not use it)
     */
    public function buildJoinString($prefix, $joins)
    {
        $join_string = '';
        foreach ($joins as $join) {
            $join = (object) $join;
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
     * @todo add tests
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
     * Generate Postgres compatible identifier name given list of names
     * already in use.
     *
     * @param  string $identifier          purposed name of identifier
     * @param  array  $current_identifiers List of identifier names in use
     * @return string The fixed identifier
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

    /**
     * Escape an identifier
     * @param  string $identifier The identifier to escape
     * @return string
     * @todo add test
     */
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
     * Builds the filter string for havings and wheres
     *
     * @param  array  $filters               The filters we are using to build these
     * @param  array  $table_transformations The transformations for the string
     * @return string
     * @todo add tests; possibly break this out to individual methods?
     */
    public function buildFilterString($filters, $table_transformations)
    {
        $where_string = "";
        $index = 0;
        foreach ($filters as $filter) {
            if (! property_exists($filter, 'data_type')) {
                $filter->data_type = 'character varying';
            }

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
     * Build where clause given filters
     *
     * @param  array  $filters               Array of filters used to construct where clause
     * @param  array  $table_transformations The transformations applied to the table
     * @return string
     * @todo add tests
     */
    public function buildWhereString($filters, $table_transformations)
    {
        $temp_filters = [];
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                $filter = (object) $filter;
            }

            if (! property_exists($filter, 'is_aggregate')) {
                $filter->is_aggregate = FALSE;
            }

            if ($filter->prefix != "aggregate" && $filter->is_aggregate != TRUE) {
                $temp_filters[] = json_decode(json_encode($filter));
            }
        }

        return $this->buildFilterString($temp_filters, $table_transformations);
    }

    /**
     * Build where clause given filters
     *
     * @param  array  $filters               Array of filters used to construct where clause
     * @param  array  $table_transformations The transformations applied to the table
     * @return string
     * @todo add tests
     */
    public function buildHavingString($filters, $table_transformations)
    {
        $temp_filters = [];
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                $filter = (object) $filter;
            }

            if (! property_exists($filter, 'is_aggregate')) {
                $filter->is_aggregate = FALSE;
            }
            if ($filter->prefix == "aggregate" || $filter->is_aggregate == TRUE) {
                $temp_filters[] = json_decode(json_encode($filter));
            }
        }

        return $this->buildFilterString($temp_filters, $table_transformations);
    }

    /**
     * Append a join o the query passed in
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
     * applyFindReplace applies the find and replace transformation.
     *
     * @param  string $column         The name of the column
     * @param  array  $transformation The Transformation values
     * @param  string $data_type      The data type of the column being transformed
     * @return string
     * @todo add test
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
     * @todo add test
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
     * @todo add test
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
     * @todo add test
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
     * @todo add test
     */
    public function applyCast($column, $transformation, $data_type)
    {
        $column = <<<SQL
            CAST({$column} as {$transformation['field_1']['value']})
            SQL;

        return $column;
    }

    /**
     * getLongestForColumn
     *
     * @param  int    $project_id              The id for the studio project
     * @param  string $table                   The table we are getting the data from
     * @param  string $column                  The column we are getting longest from
     * @param  string $schema                  The schema of for the table
     * @param  string $prefix                  The selected tables prefix (optional)
     * @param  array  $joins                   The joins that are applied to the table (optional)
     * @param  bool   $filtered                If the filters that the user applied are used (optional)
     * @param  array  $filters                 The list of filters the user applied (optional)
     * @param  string $transformations         The transformations applied to the table (optional)
     * @param  array  $columns                 Array of column preferences the user has applied (optional)
     * @param  bool   $is_aggregate            If the column is an aggregate (optional)
     * @param  string $selected_sql_definition The sql definition of the column (optional)
     * @return array
     */
    public function getLongestForColumn($project, $table, $column, $selected_prefix, $schema, $prefix = "", $joins = [], $filtered = FALSE, $filters = [], $transformations = [], $columns = [], $is_aggregate = FALSE, $selected_sql_definition = NULL, $unions = [], $union_all = FALSE)
    {
        $where_string = '';
        $having_string = '';

        $connection = app(Connection::class)->connect($project->primary_database);
        
        if (empty($prefix)) {
            $query = $connection->table(DB::raw("\"$schema\".\"$table\""));
        } else {
            $query = $connection->table(DB::raw("\"$schema\".\"$table\" as \"$prefix\""));
        }

        if (! empty($joins) && count($joins) > 0) {
            $temp_joins = $this->buildJoinObjects($project, $joins, $prefix, $columns);

            foreach ($temp_joins as $join) {
                $this->applyJoinToQuery($query, $join->schema, $join->table, $join->prefix, $join->target_column, $join->source_column, $join->join_type);
            }
        }

        if ($filtered === TRUE && count($filters) > 0) {
            $where_string = $this->buildWhereString($filters, $transformations);
            $having_string = $this->buildHavingString($filters, $transformations);
        }

        if (! empty($where_string)) {
            $query->whereRaw($where_string);
        }

        if (! empty($having_string)) {
            $query->havingRaw($having_string);
        }

        $unions = $this->buildUnionColumnMapping($unions, $columns);

        if ($selected_prefix == "aggregate" || $is_aggregate == TRUE) {
            if (! empty($selected_prefix) && $selected_prefix != "aggregate") {
                $column = "\"{$selected_prefix}\".\"{$column}\"";
            }

            $group_by_names = $this->getGroupByColumnsForGrouped($columns, $transformations);
            $query->groupBy(array_map(fn($c) => DB::raw($c), $group_by_names));
            $query->selectRaw("char_length(cast($column as text)) AS col_length, " . $column . " AS value");
        } else {
            $union_column = $column; // We manipulate it the next few lines. We want the original value to find the match in the union array.
            
            // Is the column already escaped with quotes, e.g. a JSON field? Only apply quotes if they aren't already present.
            if (strpos($column, '"') === FALSE) {
                $column = "\"{$column}\"";
            }

            if (! empty($selected_prefix)) {
                $column = "\"{$selected_prefix}\".{$column}";
            }

            if (! empty($selected_sql_definition)) {
                $column = $selected_sql_definition;
            }

            if (count($unions) === 0) {
                $columns = [
                    DB::raw("max(char_length(cast($column as text))) AS col_length"),
                    DB::raw("$column AS value")
                ];
    
                $query->select($columns);
                $query->groupBy("value");
            } else {
                $columns = [
                    DB::raw("char_length(cast($column as text)) AS col_length"),
                    DB::raw("$column AS value")
                ];
    
                $query->select($columns);

                foreach ($unions as $union) {
                    $union_query = $connection->table(DB::raw("\"{$union['schema']}\".\"{$union['table']}\""));
                    
                    // Map our unioned columns to the base table columns
                    foreach ($union['columns'] as $original_column => $aliased_to_match_base_table_column ) {
                        if ($aliased_to_match_base_table_column === $union_column) {
                            $union_query->select([
                                DB::raw("char_length(cast(\"{$original_column}\" as text)) AS col_length"),
                                DB::raw("\"{$original_column}\" AS value")
                            ]);
                    
                            $query->union(
                                $union_query,
                                $union_all
                            );
                    
                            break;
                        }
                    }
                }

                $query = $connection->table(DB::raw('(' . $query->toSql() . ') as "unioned"'));
                $query->groupByRaw('"value", "col_length"');
            }
        }

        $query->orderBy('col_length', 'desc');
        $query->limit(10);        

        $longest_ten = $query->get()->map(fn($item) => (array) $item)->toArray();

        // This dedupes a multidimentional array used mostly for aggregate columns
        $longest_ten = array_map("unserialize", array_unique(array_map("serialize", $longest_ten)));

        // Removes empty values and adds a empty entry if there are less then 10 records
        $has_emptys = FALSE;
        foreach ($longest_ten as $key => $value) {
            if (strtolower($value["value"]) == 'null' || isset($value['value']) == FALSE || empty(trim($value["value"]))) {
                $has_emptys = TRUE;
                unset($longest_ten[$key]);
            }
        }

        if (count($longest_ten) < 10 && $has_emptys) {
            $longest_ten[] = [
                "col_length" => 0,
                "value"      => ""
            ];
        }

        $longest_ten = array_values($longest_ten);

        return $longest_ten;
    }

    /**
     * Get an array of group by columns (sql expressions, no aliases)
     *
     * @param  array $columns         All columns related to the query
     * @param  array $transformations Transformations that may be applied to the table
     * @return array An array of grouping compatible columns
     */
    public function getGroupByColumnsForGrouped(array $columns = [], array $transformations = [])
    {
        // Remove any columns that aren't checked or are aggregates
        $columns = array_filter($columns, function ($column) {
            if (! filter_var($column["checked"], FILTER_VALIDATE_BOOLEAN)) {
                return FALSE;
            }

            return ! filter_var($column["is_aggregate"] ?? FALSE, FILTER_VALIDATE_BOOLEAN) && $column['prefix'] != 'aggregate';
        });

        // Pluck out the columns (and their transformations) to be thrown into the group by
        $group_by_names = array_map(function ($column) use ($transformations) {
            $table_transformations = [];

            if (! empty($transformations[$column["prefix"] . "_" . $column["column_name"]])) {
                $table_transformations = $transformations[$column["prefix"] . "_" . $column["column_name"]];
            }

            if (filter_var($column["added"], FILTER_VALIDATE_BOOLEAN) == TRUE) {
                $column["column_name"] = $column["sql_definition"];
            } elseif ($column["prefix"] != "aggregate") {
                $column["column_name"] = '"' . $column["prefix"] . '"."' . $column["column_name"] . '"';
            }

            return $this->buildTransformation($table_transformations, $column["column_name"], $column["data_type"]);
        }, $columns);

        return $group_by_names;
    }

    /**
     * getCountsForColumn
     *
     * @param  Project $project         The project record
     * @param  string  $table           The table we are getting the data from
     * @param  string  $column          The column we are getting longest from
     * @param  string  $schema          The schema of for the table
     * @param  int     $limit           The amount of counts we are getting (optional)
     * @param  string  $prefix          The selected tables prefix (optional)
     * @param  array   $joins           The joins applied to the table (optional)
     * @param  bool    $is_filtered     If the filters that the user applied are used (optional)
     * @param  array   $filters         The list of filters the user applied (optional)
     * @param  array   $transformations The transformations applied to the table (optional)
     * @param  array   $columns         Array of column preferences the user has applied (optional)
     * @param  array   $unions          Array of union queries (optional)
     * @param  bool    $union_all       If the unions are union all (optional)
     * @return array
     */
    public function getCountsForColumn($project, $table, $column, $schema, $limit = 25, $prefix = "", $joins = [], $is_filtered = FALSE, $filters = [], $transformations = [], $columns = [], $is_aggregate = FALSE, $is_grouped = FALSE, $unions = [], $union_all = FALSE)
    {
        if (! $is_filtered) {
            $filters = [];
        }

        $aliased = FALSE;

        // Are we aliasing?
        foreach ($columns as $key => $column_in_column_list) {
            if ($column_in_column_list['column_name'] == $column || $column_in_column_list['target_column_name'] == $column) {
                if (! empty($column_in_column_list['alias'])) {
                    $column = $column_in_column_list['alias'];
                    $aliased = TRUE;
                }

                if ($column_in_column_list['checked'] == FALSE) {
                    $columns[$key]['checked'] = TRUE;
                }

                break;
            }
        }

        if (! $aliased) {
            $column = $this->escapeIdentifierName($column);
        }

        // Using by reference (&) in this scenario is a bit of an anti-pattern. It's not clear that group_by_names is being modified in the function. todo: Maybe redesign this a bit?
        $columns_array = $this->buildColumnsArray($prefix, $columns, $is_grouped, $group_by_names, $transformations, FALSE); 

        $temp_joins = $this->buildJoinObjects($project, $joins, $prefix, $columns);

        $where_string = $this->buildWhereString($filters, $transformations);

        $having_string = $this->buildHavingString($filters, $transformations);

        $unions = $this->buildUnionColumnMapping($unions, $columns);

        $this->connection = app(Connection::class)->connect($project->primary_database);

        // Are we filtering on unstructured data?
        if ($this->hasJsonFilters($filters)) {
            $join_string = $this->buildJoinString($prefix, $temp_joins);

            $group_string = $this->buildGroupString($is_grouped, $group_by_names);

            $sql = $this->buildJsonFilterSelectString($schema, $prefix, $table, $columns_array, $where_string, $having_string, $join_string, $group_string, NULL, NULL, 0, $this->connection);
        } else {
            $sql = $this->buildSelect($schema, $prefix, $table, $columns_array, $group_by_names, $where_string, $having_string, $temp_joins, NULL, $is_grouped, NULL, 0, $unions, $union_all);
        }

        $distinct_values_and_counts = $this->connection->table($sql, 'bytespree_sub_query')
            ->selectRaw("\"bytespree_sub_query\".{$column} as value, count(*) as qty")
            ->orderBy('qty', 'DESC')
            ->groupBy(DB::raw($column))
            ->limit($limit)
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();

        // Combines (null, NULL, and (empty strings)) together in the counts modal
        $saved_key = NULL;
        $counts = [];
        foreach ($distinct_values_and_counts as $key => $value) {
            if (isset($saved_key) && (strtolower($value["value"]) == 'null' || isset($value['value']) == FALSE || empty(trim($value["value"])))) {
                $counts[$saved_key]["qty"] += $value["qty"];
                continue;
            }

            if ($value['value'] === FALSE) {
                $value['value'] = 'false';
            }

            if ($value['value'] === TRUE) {
                $value['value'] = 'true';
            }

            $counts[] = $value;
            if (strtolower($value["value"]) == 'null' || isset($value['value']) == FALSE || empty(trim($value["value"]))) {
                $saved_key = array_key_last($counts);
            }
        }

        usort($counts, function ($a, $b) {
            return $b['qty'] <=> $a['qty'];
        });

        return $counts;
    }
}
