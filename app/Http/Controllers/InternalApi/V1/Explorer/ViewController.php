<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Manager\ViewDefinitionHistory;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectView;
use App\Models\Explorer\PublishingDestination;
use App\Models\Explorer\ProjectPublishingSchedule;
use App\Models\Explorer\ProjectColumnMapping;
use App\Models\Explorer\ProjectColumnFlag;
use App\Models\Explorer\ProjectColumnComment;
use App\Models\Explorer\ProjectColumnAttachment;
use App\Classes\IntegrationJenkins;
use App\Classes\Database\Connection;
use App\Classes\Database\Table;
use App\Classes\Database\View;
use App\Classes\Database\StudioExplorer;
use App\Attributes\Can;
use Exception;

class ViewController extends Controller
{
    public function history(Request $request, Project $project, ProjectView $view)
    {
        $request->validateWithErrors([
            'page' => 'required'
        ]);

        $offset = 10 * $request->page;
        $histories = ViewDefinitionHistory::where('view_history_guid', $view->view_history_guid)
            ->where('view_name', $view->view_name)
            ->with('user')
            ->limit(10)
            ->offset($offset)
            ->orderBy('created_at', 'desc');

        $counts = ViewDefinitionHistory::where('view_history_guid', $view->view_history_guid)
            ->where('view_name', $view->view_name)
            ->count();

        if (! empty($request->filter)) {
            $histories->where('created_at', '>=', $request->filter);
        }

        $histories = $histories->get()->map(function ($history) {
            $history->profile_picture = app('environment')->getGravatar($history->user->email ?? '');

            return $history;
        });

        $users = $histories->mapWithKeys(function ($history) {
            return [$history->user->name => $history->view_created_by];
        });

        $pages = ceil($counts / 10);

        return response()->success(['view_history' => $histories, 'users' => $users, 'pages' => $pages]);
    }

    public function historyDetail(Request $request, Project $project, ProjectView $view, ViewDefinitionHistory $history)
    {
        return response()->success($history);
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function create(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'prefix'     => 'required',
            'table'      => 'required',
            'schema'     => 'required',
            'order'      => 'required',
            'columns'    => 'required',
            'is_grouped' => 'required',
            'view_name'  => 'required',
            'view_type'  => 'required'
        ]);

        if (Table::exists($project->primary_database, $project->name, $request->view_name)) {
            return response()->error("Unable to publish view at this time. View already exists.");
        }

        if (! preg_match("/^[a-z][a-z0-9_]*$/", $request->view_name)) {
            return response()->error("Unable to publish view at this time.  Name must contain only letters, numbers, and underscores and must start with a letter.");
        }

        $columns_checked = collect($request->columns)->filter(function ($column) {
            return $column['checked'];
        })->map(function ($column) {
            return (object) $column;
        });

        if ($columns_checked->count() == 1 && strtolower($columns_checked->first()['column_name']) == 'count(*)') {
            return response()->error("Unable to publish view at this time. View cannot be created using only record count column. Please select more columns to publish the table as a view.");
        }

        // @todo: After testing is finished, convert this to use StudioQuery class
        $query = app(StudioExplorer::class)->getRecordsQuery(
            $project,
            $request->prefix,
            $request->table,
            $request->schema,
            $request->filters,
            $request->joins,
            $request->order,
            $request->columns,
            $request->is_grouped,
            $request->transformations,
            $request->input('unions', []),
            filter_var($request->union_all, FILTER_VALIDATE_BOOLEAN),
        );

        if (is_string($query)) {
            $view_definition = $query;
        } else {
            $view_definition = $query->toSql();
        }

        if (! View::create($project->primary_database, $project->name, $request->view_name, $request->view_type, $view_definition)) {
            return response()->error("Unable to publish view at this time.");
        }

        $db_view = View::get($project->primary_database, $project->name, $request->view_name);

        $project_view = ProjectView::create([
            'project_id'          => $project->id,
            'view_schema'         => $project->name,
            'view_name'           => $request->view_name,
            'view_message'        => $request->view_message,
            'view_definition'     => $request->except('view_name', 'view_type', 'view_frequency', 'view_schedule'),
            'view_definition_sql' => $db_view->view_definition,
            'view_history_guid'   => uniqid('view_'),
            'view_type'           => $request->view_type,
            'user_id'             => auth()->user()->user_handle
        ]);

        if ($request->view_type == 'materialized' && ! empty($request->view_frequency)) {
            $view_schedule = (object) $request->view_schedule;
            $view_schedule->frequency = $request->view_frequency;
            $options = [
                'name' => $request->view_name
            ];

            $this->createSchedule($project, $request->schema, $request->table, $view_schedule, $options, $project_view->id);
        }

        return response()->success();
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function update(Request $request, Project $project, ProjectView $view)
    {
        $request->validateWithErrors([
            'prefix'     => 'required',
            'table'      => 'required',
            'schema'     => 'required',
            'order'      => 'required',
            'columns'    => 'required',
            'is_grouped' => 'required'
        ]);

        try {
            $old_db_view = View::get($project->primary_database, $view->view_schema, $view->view_name);

            if (empty($old_db_view)) {
                return response()->error("Unable to publish view at this time. Database view couldn't be found.");
            }

            $definitions = View::dropForTable($project->primary_database, $view->view_schema, $view->view_name);

            View::drop($project->primary_database, $view->view_schema, $view->view_name, $view->view_type);
        } catch (Exception $e) {
            return response()->error("Unable to publish view at this time. Database view couldn't be found.");
        }

        // @todo: After testing is finished, convert this to use StudioQuery class
        $query = app(StudioExplorer::class)->getRecordsQuery(
            $project,
            $request->prefix,
            $request->table,
            $request->schema,
            $request->filters,
            $request->joins,
            $request->order,
            $request->columns,
            $request->is_grouped,
            $request->transformations,
            $request->input('unions', []),
            filter_var($request->union_all, FILTER_VALIDATE_BOOLEAN),
        );

        if (is_string($query)) {
            $view_definition = $query;
        } else {
            $view_definition = $query->toSql();
        }

        $view_type = empty($request->view_type) ? $view->view_type : $request->view_type;

        try {
            View::create($project->primary_database, $view->view_schema, $view->view_name, $view_type, $view_definition);
        } catch (Exception $e) {
            View::create($project->primary_database, $view->view_schema, $view->view_name, $view->view_type, $old_db_view->view_definition);
            View::createFromDefinitions($project->primary_database, $definitions);

            return response()->error("Unable to publish view at this time.");
        }

        $view_results = View::createFromDefinitions($project->primary_database, $definitions);

        if (array_search(FALSE, $view_results) !== FALSE) {
            // Failed to recreate dependent views.  Someone probably changed or removed a dependent column
            // Put everything back to the way it was.
            View::drop($project->primary_database, $view->view_schema, $view->view_name, $view_type);
            View::create($project->primary_database, $view->view_schema, $view->view_name, $view->view_type, $old_db_view->view_definition);
            View::createFromDefinitions($project->primary_database, $definitions);

            return response()->error("Due to dependent views, we are unable to publish this view at this time.");
        }

        $db_view = View::get($project->primary_database, $view->view_schema, $view->view_name);
        $view_message = empty($request->view_message) && $request->view_message != "" ? $view->view_message : $request->view_message;
        $old_definition = $view->view_definition;

        $view->update([
            'view_definition'     => $request->except('view_type', 'view_message', 'view_frequency', 'view_schedule'),
            'view_definition_sql' => $db_view->view_definition,
            'view_message'        => $view_message,
            'view_type'           => $view_type,
            'user_id'             => auth()->user()->user_handle
        ]);

        if ($view->view_type == 'materialized') {
            if (empty($request->view_frequency)) {
                $this->deleteSchedule($project, $request->schema, $request->table, $view->id);
            } else if (! empty($request->view_frequency)) {
                $view_schedule = (object) $request->view_schedule;
                $view_schedule->frequency = $request->view_frequency;
                $options = [
                    'name' => $view->view_name
                ];

                $this->createSchedule($project, $request->schema, $request->table, $view_schedule, $options, $view->id);
            }
        }

        $this->compareColumnsFromUpdate(
            $project,
            $view->view_name,
            (array) $old_definition->columns,
            (array) $view->view_definition->columns
        );

        return response()->success();
    }

    #[Can(permission: 'refresh_materialized_view', product: 'studio', id: 'project.id')]
    public function refresh(Request $request, Project $project, ProjectView $view)
    {
        View::refresh($project->primary_database, $view->view_schema, $view->view_name);

        return response()->success();
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function rename(Request $request, Project $project, ProjectView $view)
    {
        $request->validateWithErrors([
            'view_name' => 'required'
        ]);

        if (count(Connection::getObjectDependencies($project->primary_database, $project->name, $view->view_name)) > 0) {
            return response()->error("Your view cannot be modified because another view or resource depends on it.");
        }

        if (Table::exists($project->primary_database, $project->name, $request->view_name)) {
            return response()->error("Unable to publish view at this time. View already exists.");
        }

        if (! preg_match("/^[a-z][a-z0-9_]*$/", $request->view_name)) {
            return response()->error("Unable to publish view at this time.  Name must contain only letters, numbers, and underscores and must start with a letter.");
        }

        View::rename($project->primary_database, $project->name, $view->view_name, $request->view_name, $view->view_type);
        $view->update([
            'view_name' => $request->view_name
        ]);

        if ($view->view_type == 'materialized' && ! empty($view->publisher())) {
            $view_schedule = (object) $view->publisher()->schedule;
            $options = [
                'name' => $request->view_name
            ];

            $this->createSchedule($project, $view->view_definition->schema, $view->view_definition->table, $view_schedule, $options, $view->id);
        }

        return response()->success();
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function restore(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'view_name' => 'required',
            'fix_type'  => 'required'
        ]);

        $view = ProjectView::where('project_id', $project->id)
            ->where('view_name', $request->view_name)
            ->first();

        if ($request->fix_type == "restore") {
            if (Table::exists($project->primary_database, $view->view_schema, $view->view_name)) {
                return response()->error("Unable to restore view. Another table already exists with this name.");
            }

            $definition = $view->view_definition;

            $query = app(StudioExplorer::class)->getRecordsQuery(
                $project,
                $definition->prefix,
                $definition->table,
                $definition->schema,
                $definition->filters,
                $definition->joins,
                $definition->order,
                $definition->columns,
                $definition->is_grouped,
                $definition->transformations,
                $definition->unions ?? [],
                $definition->union_all ?? FALSE,
            );

            if (is_string($query)) {
                $view_definition = $query;
            } else {
                $view_definition = $query->toSql();
            }

            if (! View::create($project->primary_database, $view->view_schema, $view->view_name, $view->view_type, $view_definition)) {
                return response()->error("Unable to publish view at this time.");
            }
        }

        $db_view = View::get($project->primary_database, $view->view_schema, $view->view_name);

        if (empty($db_view)) {
            return response()->error("Unable to publish view at this time. Database view couldn't be found.");
        }

        $view->update([
            "view_definition_sql" => $db_view->view_definition,
            "view_type"           => $db_view->view_type,
            "deleted_at"          => NULL
        ]);

        return response()->success();
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function switch(Request $request, Project $project, ProjectView $view)
    {
        try {
            $db_view = View::get($project->primary_database, $view->view_schema, $view->view_name);

            if (empty($db_view)) {
                return response()->error("Unable to publish view at this time. Database view couldn't be found.");
            }

            $definitions = View::dropForTable($project->primary_database, $view->view_schema, $view->view_name);

            View::drop($project->primary_database, $view->view_schema, $view->view_name, $view->view_type);
        } catch (Exception $e) {
            return response()->error("Unable to publish view at this time. Database view couldn't be found.");
        }

        $view_type = $view->view_type == 'normal' ? 'materialized' : 'normal';
        View::create($project->primary_database, $view->view_schema, $view->view_name, $view_type, $db_view->view_definition);
        View::createFromDefinitions($project->primary_database, $definitions);

        $view->update([
            'view_type'    => $view_type,
            'view_message' => $request->view_message,
            'user_id'      => auth()->user()->user_handle
        ]);
        
        if ($view->view_type == 'normal') {
            $this->deleteSchedule($project, $view->view_definition->schema, $view->view_definition->table, $view->id);
        } else {
            if (! empty($request->view_frequency)) {
                $view_schedule = (object) $request->view_schedule;
                $view_schedule->frequency = $request->view_frequency;
                $options = [
                    'name' => $view->view_name
                ];

                $this->createSchedule($project, $view->view_definition->schema, $view->view_definition->table, $view_schedule, $options, $view->id);
            }
        }

        return response()->success($view_type);
    }

    public function destroy(Request $request, Project $project, ProjectView $view)
    {
        $request->validateWithErrors([
            'ignore_warning' => 'required'
        ]);
        $ignore_warning = filter_var($request->ignore_warning, FILTER_VALIDATE_BOOLEAN);

        $views = collect(Table::dependencies($project->primary_database, $view->view_schema, $view->view_name))
            ->filter(function ($dependant_view) use ($view) {
                return $dependant_view->view_schema != $view->view_schema || $dependant_view->view_name != $view->view_name;
            });

        $schedules = ProjectPublishingSchedule::where('project_id', $project->id)
            ->where('schema_name', $view->view_schema)
            ->where('table_name', $view->view_name)
            ->get();
        
        if ($views->count() > 0) {
            return response()->error("This view has dependent view(s). Please delete these view(s) to delete this view.");
        } 
        
        if ($schedules->count() > 0 && $ignore_warning == FALSE) {
            return response()->success("warning", "$view->view_name has a scheduled publish job. Deleting this table will also delete the publishing job. Do you want to continue?");
        }

        View::drop($project->primary_database, $view->view_schema, $view->view_name, $view->view_type);

        if ($schedules->count() > 0) {
            app(IntegrationJenkins::class)->removePublishJobForProject($project, $view->view_schema, $view->view_name);

            foreach ($schedules as $schedule) {
                $schedule->delete();
            }
        }

        $this->deleteSchedule($project, $view->view_definition->schema, $view->view_definition->table, $view->id);
        $view->delete();

        return response()->success();
    }

    public function createSchedule(Project $project, string $schema, string $table, object $schedule, array $options, int $publisher_id)
    {
        $updating = TRUE;
        $destination = PublishingDestination::className('View');
        $publishing_schedule = ProjectPublishingSchedule::where('publisher_id', $publisher_id)
            ->where('destination_id', $destination->id)
            ->withTrashed()
            ->first();

        if (empty($publishing_schedule)) {
            $updating = FALSE;
            $publishing_schedule = ProjectPublishingSchedule::create([
                'project_id'          => $project->id,
                'destination_id'      => $destination->id,
                'destination_options' => $options,
                'username'            => auth()->user()->user_handle,
                'schema_name'         => $schema,
                'table_name'          => $table,
                'schedule'            => $schedule,
                'publisher_id'        => $publisher_id
            ]);
        }

        // Create it in Jenkins...
        $result = app(IntegrationJenkins::class)->createOrUpdatePublishJob(
            $project,
            $destination->name,
            $schema,
            $table,
            $publishing_schedule->id,
            $schedule
        );

        if (! $result ) {
            if (! $updating) {
                $publishing_schedule->delete();
            }

            logger()->error("Unable to schedule publishing at this time", [
                'publisher_id'   => $publishing_schedule->id,
                'project_id'     => $project->id,
                'destination_id' => $destination->id,
                'options'        => $options,
                'user'           => auth()->user()->user_handle,
                'table'          => $table,
                'schema'         => $schema,
                'schedule'       => $schedule,
            ]);

            return FALSE;
        }

        if ($updating) {
            $publishing_schedule->update([
                'destination_options' => $options,
                'schedule'            => $schedule,
                'deleted_at'          => NULL
            ]);
        }

        return TRUE;
    }

    public function deleteSchedule(Project $project, string $schema, string $table, int $publisher_id)
    {
        $updating = TRUE;
        $destination = PublishingDestination::className('View');
        $publishing_schedule = ProjectPublishingSchedule::where('publisher_id', $publisher_id)
            ->where('destination_id', $destination->id)
            ->first();

        if (empty($publishing_schedule)) {
            return TRUE;
        }

        $result = app(IntegrationJenkins::class)->deletePublisherJob(
            $publishing_schedule,
            $project,
            $schema,
            $table
        );

        if (! $result ) {
            logger()->error("Unable to delete publishing at this time", [
                'publisher_id'   => $publishing_schedule->id,
                'project_id'     => $project->id,
                'destination_id' => $destination->id,
                'user'           => auth()->user()->user_handle,
                'table'          => $table,
                'schema'         => $schema
            ]);

            return FALSE;
        }
        
        $publishing_schedule->delete();

        return TRUE;
    }

    public function compareColumnsFromUpdate(Project $project, string $view_name, array $old_columns, array $new_columns)
    {
        $changed_columns = $this->getRenamedColumns($old_columns, $new_columns);

        // Update our flags for changed columns
        foreach ($changed_columns as $changed_column) {
            ProjectColumnFlag::where('project_id', $project->id)->where('table_name', $view_name)->where('column_name', $changed_column->old_key)->update([
                'column_name' => empty($changed_column->alias) ? $changed_column->target_column_name : $changed_column->alias
            ]);

            ProjectColumnMapping::where('project_id', $project->id)->where('source_table_name', $view_name)->where('source_column_name', $changed_column->old_key)->update([
                'source_column_name' => empty($changed_column->alias) ? $changed_column->target_column_name : $changed_column->alias
            ]);

            ProjectColumnComment::where('project_id', $project->id)->where('table_name', $view_name)->where('column_name', $changed_column->old_key)->update([
                'column_name' => empty($changed_column->alias) ? $changed_column->target_column_name : $changed_column->alias
            ]);

            ProjectColumnAttachment::where('project_id', $project->id)->where('table_name', $view_name)->where('column_name', $changed_column->old_key)->update([
                'column_name' => empty($changed_column->alias) ? $changed_column->target_column_name : $changed_column->alias
            ]);
        }
    }

    /**
     * Rifle through the "old" columns, comparing them to the "new" columns. When a column is detected as renamed, set the old_key to the former name so it can be used
     * to update child foreign rows
     *
     * @param  array $old_columns Columns in the old definition
     * @param  array $new_columns Columns in the new definition
     * @return array Array of renamed columns
     */
    public function getRenamedColumns(array $old_columns, array $new_columns)
    {
        $columns = array_map(function ($column) use ($old_columns) {
            foreach ($old_columns as $old_column) {
                if ($column->target_column_name == $old_column->target_column_name || ($column->sql_definition == $old_column->sql_definition && ! empty($column->sql_definition))) {
                    if ($column->alias != $old_column->alias) {
                        $column->old_key = empty($old_column->alias) ? $old_column->target_column_name : $old_column->alias;
                    } elseif ($column->target_column_name != $old_column->target_column_name) {
                        $column->old_key = $old_column->target_column_name;
                    }
                }
            }

            return $column;
        }, $new_columns);

        return array_filter($columns, function ($column) {
            return property_exists($column, 'old_key');
        });
    }
}
