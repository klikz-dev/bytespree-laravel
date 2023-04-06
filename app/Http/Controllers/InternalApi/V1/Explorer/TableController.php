<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\Project;
use App\Models\User;
use App\Classes\Database\Connection;
use App\Classes\Database\Table;
use App\Classes\Database\StudioExplorer;
use App\Classes\Database\StudioQuery;
use App\Models\Explorer\ProjectView;
use DB;
use Exception;

class TableController extends Controller
{
    public function activeUsers(Request $request, Project $project, string $schema, string $table)
    {
        $users = User::activeIn($project, $schema, $table, 25)
            ->get()
            ->map(function ($user) {
                $user->gravatar = $user->getGravatar();
                $user->namei = substr($user->name, 0, 1);

                return $user;
            });

        return response()->success($users);
    }

    public function columns(Request $request, Project $project, string $schema, string $table)
    {
        $prefix = $request->input('prefix', '');
        $schema = $request->input('schema', '');
        $previous_prefix = $request->input('previous_prefix', '');
        $joins = $request->input('joins', []);
        $transformations = $request->input('transformations', []);
        $columns = $request->input('columns', []);
        $ignore_user_preferences = filter_var($request->input('ignore_user_preferences', FALSE), FILTER_VALIDATE_BOOLEAN);

        if (! is_array($joins)) {
            $joins = [];
        }

        if (! is_array($columns)) {
            $columns = [];
        }

        if (! is_array($transformations)) {
            $transformations = [];
        }

        if (count($columns) > 0 && $ignore_user_preferences === FALSE) {
            $results = app(StudioExplorer::class)->getProjectTableColumnsForVisible(
                $project,
                $prefix,
                $schema,
                $table,
                $joins,
                $transformations,
                $columns,
                $previous_prefix
            );
        } else {
            $results = app(StudioExplorer::class)->getProjectTableColumns(
                $project,
                $project->primary_database,
                $prefix,
                $schema,
                $table,
                $joins,
                $transformations
            );
        }

        return response()->success($results);
    }

    public function longestCounts(Request $request, Project $project, string $schema, string $table)
    {
        $prefix = $request->input('prefix', '');
        $schema = $request->input('schema', '');
        $joins = $request->input('joins', []);
        $transformations = $request->input('transformations', []);
        $columns = $request->input('columns', []);
        $filters = $request->input('filters', []);
        $column = $request->selected_column;
        $selected_prefix = $request->selected_prefix;
        $filtered = filter_var($request->input('filtered', FALSE), FILTER_VALIDATE_BOOLEAN);
        $selected_sql_definition = $request->input('selected_sql_definition', NULL);
        $unions = $request->input('unions', []);
        $union_all = filter_var($request->input('union_all', FALSE), FILTER_VALIDATE_BOOLEAN);

        try {
            $records = app(StudioQuery::class)->getLongestForColumn(
                $project,
                $table,
                $column,
                $selected_prefix,
                $schema,
                $prefix,
                $joins,
                $filtered,
                $filters,
                $transformations,
                $columns,
                FALSE,
                $selected_sql_definition,
                $unions,
                $union_all
            );

            $vars = [
                "control_id"      => $project->id,
                "selected_table"  => '',
                "selected_column" => $request->selected_column,
                "longest"         => $records
            ];

            return response()->success($vars);
        } catch (Exception $e) {
            return response()->error("An error occurred when getting records.", ['error' => $e->getMessage()], 500);
        }
    }

    public function popularCounts(Request $request, Project $project, string $schema, string $table)
    {
        $prefix = $request->input('prefix', '');
        $schema = $request->input('schema', '');
        $joins = $request->input('joins', []);
        $transformations = $request->input('transformations', []);
        $columns = $request->input('columns', []);
        $filters = $request->input('filters', []);
        $limit = $request->input('limit', 25);
        $order = $request->input('order', NULL);
        $is_grouped = filter_var($request->input('is_grouped', FALSE), FILTER_VALIDATE_BOOLEAN);
        $selected_column = $request->selected_column;
        $selected_prefix = $request->selected_prefix;
        $selected_sql_definition = $request->input('selected_sql_definition', NULL);
        $is_aggregate = filter_var($request->input('is_aggregate', FALSE), FILTER_VALIDATE_BOOLEAN);
        $is_filtered = filter_var($request->input('filtered', FALSE), FILTER_VALIDATE_BOOLEAN);
        $unions = $request->input('unions', []);
        $union_all = filter_var($request->input('union_all', FALSE), FILTER_VALIDATE_BOOLEAN);

        if (filter_var($request->input('filtered', TRUE), FILTER_VALIDATE_BOOLEAN) === FALSE) {
            $filters = [];
        }

        // Is the column aliased? Only apply to non-aggregated fields.
        if ($selected_prefix != 'aggregate' && $is_aggregate === FALSE) {
            foreach ($columns as $column) {
                if ($column['prefix'] == $selected_prefix && $column['column_name'] == $selected_column) {
                    if (! empty($column['alias'])) {
                        $selected_column = $column['alias'];
                    } else {
                        $selected_column = $column['target_column_name'];
                    }
                }
            }
        }

        try {
            $records = app(StudioQuery::class)->getCountsForColumn(
                $project,
                $table,
                $selected_column,
                $schema,
                $limit,
                $prefix,
                $joins,
                $is_filtered,
                $filters,
                $transformations,
                $columns,
                $is_aggregate,
                $is_grouped,
                $unions,
                $union_all
            );

            $vars = [
                "control_id"      => $project->id,
                "selected_table"  => '',
                "selected_column" => $request->selected_column,
                "counts"          => $records
            ];

            return response()->success($vars);
        } catch (Exception $e) {
            return response()->error("An error occurred when getting records.", ['error' => $e->getMessage()], 500);
        }
    }

    public function stats(Request $request, Project $project, string $schema, string $table)
    {
        $mapping_counts = $project->mappings
            ->where('schema_name', $schema)
            ->where('source_table_name', $table)
            ->count();

        $flag_counts = $project->flags
            ->where('schema_name', $schema)
            ->where('table_name', $table)
            ->count();

        $columns = app(StudioExplorer::class)->getProjectTableColumns(
            $project,
            $project->primary_database,
            $table,
            $schema,
            $table,
            [],
            []
        );

        $views = Table::views($project->primary_database, $schema, $table, TRUE);

        $data = [
            "mapping" => $mapping_counts,
            "column"  => count($columns) - 1, // Do this because count records is returned in query
            "view"    => count($views),
            "flag"    => $flag_counts
        ];

        return response()->success($data);
    }

    public function records(Request $request, Project $project, string $schema, string $table)
    {
        $prefix = $request->input('prefix', '');
        $schema = $request->input('schema', '');
        $joins = $request->input('joins', []);
        $transformations = $request->input('transformations', []);
        $columns = $request->input('columns', []);
        $filters = $request->input('filters', []);
        $limit = $request->input('limit', 10);
        $order = $request->input('order', NULL);
        $offset = $request->input('offset', 0);
        $page_num = $request->input('page_num', 1);
        $is_grouped = filter_var($request->input('is_grouped', FALSE), FILTER_VALIDATE_BOOLEAN);
        $unions = $request->input('unions', []);
        $union_all = filter_var($request->input('union_all', FALSE), FILTER_VALIDATE_BOOLEAN);

        $offset = $limit * ($page_num - 1);

        try {
            $result = app(StudioQuery::class)->getRecords(
                $project,
                $prefix,
                $table,
                $schema,
                $filters,
                $joins,
                $order,
                $columns,
                $is_grouped,
                $transformations,
                $limit,
                $offset,
                FALSE,
                $unions,
                $union_all
            );

            return response()->success([
                'records'   => $result['records'],
                'md5_query' => md5(serialize(compact('prefix', 'schema', 'table', 'joins', 'transformations', 'columns', 'filters', 'order', 'is_grouped', 'unions', 'union_all'))),
            ]);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'cannot be matched') !== FALSE) {
                return response()->error("Union could not be applied; please check unioned tables.", [], 500);
            }

            return response()->error("An error occurred when getting records.", ['error' => $e->getMessage()], 500);
        }
    }

    public function count(Request $request, Project $project, string $schema, string $table)
    {
        $prefix = $request->input('prefix', '');
        $schema = $request->input('schema', '');
        $joins = $request->input('joins', []);
        $transformations = $request->input('transformations', []);
        $columns = $request->input('columns', []);
        $filters = $request->input('filters', []);
        $limit = $request->input('limit', 10);
        $order = $request->input('order', NULL);
        $offset = $request->input('offset', 0);
        $page_num = $request->input('page_num', 1);
        $is_grouped = filter_var($request->input('is_grouped', FALSE), FILTER_VALIDATE_BOOLEAN);
        $unions = $request->input('unions', []);
        $union_all = filter_var($request->input('union_all', FALSE), FILTER_VALIDATE_BOOLEAN);

        $result = app(StudioQuery::class)->getRecords(
            $project,
            $prefix,
            $table,
            $schema,
            $filters,
            $joins,
            $order,
            $columns,
            $is_grouped,
            $transformations,
            $limit,
            $offset,
            TRUE,
            $unions,
            $union_all
        );

        return response()->success($result);
    }

    // Get the actual columns from the table/view
    public function tableColumns(Request $request, Project $project, string $schema, string $table)
    {
        $columns = app(Table::class)->columns($project->primary_database, $schema, $table);

        return response()->success($columns);
    }

    /**
     * @todo fix commented code when studio views are available in studio
     */
    public function meta(Request $request, Project $project, string $schema, string $table)
    {
        $view = ProjectView::where('project_id', $project->id)
            ->with('user')
            ->where('view_name', $table)
            ->first();

        if (! $view) {
            return response()->error('View not found');
        }

        if (! property_exists($view->view_definition, 'schema') || empty($view->view_definition->schema)) {
            // $view['view_definition']->schema = $this->BP_ProjectsModel->getProjectTableSchema($project->id, $view['table']); // de heck? $view['table'] can't exist in this context...
        }

        if (! property_exists($view->view_definition, 'view_schema') || empty($view->view_definition->view_schema)) {
            // $view['view_definition']['view_schema'] = $this->BP_ProjectsModel->getProjectTableSchema($project->id, $table);
        }

        if (! empty($view->publisher())) {
            $view->schedule = $view->publisher()->schedule;
        }

        if (! empty($view->view_definition->joins) && is_array($view->view_definition->joins)) {
            foreach ($view->view_definition->joins as $index => $join) {
                if (! property_exists($join, 'schema')) {
                    // $join->schema = $this->BP_ProjectsModel->getProjectTableSchema($project->id, $join->table);
                }
                if (! property_exists($join, 'schema_table')) {
                    $join->schema_table = $join->schema . '.' . $join->table;
                }
                if (! property_exists($join, 'source_prefix') && property_exists($view['view_definition'], 'prefix')) {
                    $join->source_prefix = $view['view_definition']->prefix;
                }
                $view->view_definition->joins[$index] = $join;
            }
        }

        $view->dependent_views = array_map(function ($dep) {
            return $dep->schema . '.' . $dep->name;
        }, Connection::getObjectDependencies($project->primary_database, $view->view_schema, $table));

        $view->foreign_dependent_views = array_map(function ($dep) {
            return $dep->schema . '.' . $dep->name;
        }, Connection::getForeignObjectDependencies($project->primary_database, $table));

        $view->profile_picture = app('environment')->getGravatar($view->user->email);

        return response()->success($view, 'View found');
    }
}
