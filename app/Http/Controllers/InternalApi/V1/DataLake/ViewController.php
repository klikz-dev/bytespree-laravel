<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\Manager\ViewDefinition;
use App\Models\Manager\ViewSchedule;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectTableNote;
use App\Classes\IntegrationJenkins;
use App\Classes\Database\Connection;
use App\Classes\Database\View;
use App\Classes\Database\ForeignDatabase;
use App\Classes\Database\Table;
use App\Attributes\Can;
use App\Models\Explorer\ProjectPublishingSchedule;
use Exception;

class ViewController extends Controller
{
    protected $frequency_default;

    protected $schedule_default;

    protected $integration_jenkins;

    public function __construct()
    {
        $this->schedule_default = (object) [
            'month_day' => 1,
            'week_day'  => 0,
            'hour'      => 0,
            'month'     => 1
        ];
        $this->frequency_default = 'daily';

        $this->integration_jenkins = new IntegrationJenkins();
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function list(PartnerIntegration $database)
    {
        $db_views = View::list($database);
        $dependencies = Connection::getAllDepndencies($database);

        $views = ViewDefinition::where('partner_integration_id', $database->id)
            ->get()
            ->map(function ($view) use ($database, $db_views, $dependencies) {
                if (! empty($view->schedule)) {
                    $schedule_id = $view->schedule->id;
                    $schedule = $view->schedule->schedule;
                    $frequency = $view->schedule->frequency;
                } else {
                    $schedule_id = 0;
                    $frequency = $this->frequency_default;
                    $schedule = $this->schedule_default;
                }

                $db_view = array_filter($db_views, function ($db_view) use ($view) {
                    return $db_view->view_name == $view->view_name && $db_view->view_schema == $view->view_schema;
                });

                if (empty($db_view)) {
                    $exists = TRUE;
                    $synchronized = FALSE;
                } else {
                    $db_view = array_shift($db_view);
                    $exists = TRUE;

                    if ($view->view_type != $db_view->view_type || $view->view_definition_sql != $db_view->view_definition) {
                        $synchronized = FALSE;
                    } else {
                        $synchronized = TRUE;
                    }
                }

                $downstream_views = ViewDefinition::where('upstream_build_id', $view->id)
                    ->where('view_type', 'materialized')
                    ->get()
                    ->map(function ($child_view) {
                        return $child_view->view_name;
                    });

                $dependent_views = array_values(array_filter($dependencies, function ($dep) use ($view) {
                    return $dep->source_schema == $view->view_schema && $dep->source_name == $view->view_name;
                }));

                return (object) [
                    "id"                      => $view->id,
                    "table_name"              => "$view->view_schema.$view->view_name",
                    "view_history_guid"       => $view->view_history_guid,
                    "view_schema"             => $view->view_schema,
                    "view_name"               => $view->view_name,
                    "view_definition"         => $view->view_definition_sql,
                    "user_definition"         => $view->view_user_sql,
                    "view_type"               => empty($view->view_type) ? 'normal' : $view->view_type,
                    "exists"                  => $exists,
                    "synchronized"            => $synchronized,
                    "build_on"                => $view->build_on,
                    "upstream_build_id"       => $view->upstream_build_id,
                    "schedule_id"             => $schedule_id,
                    "schedule"                => $schedule,
                    "frequency"               => $frequency,
                    "dependent_views"         => $dependent_views,
                    "foreign_dependent_views" => Connection::getForeignObjectDependencies($database, $view->view_name),
                    "downstream_views"        => $downstream_views
                ];
            });

        // Checks database views for views that exist in the database but not in bytespree
        // TODO: Not sure I like the logic here. Seems like it should be outside of the controller. Revisit this, maybe, in the future?
        foreach ($db_views as $view) {
            $table_name = "$view->view_schema.$view->view_name";
            $found = FALSE;

            // Does the database exist for data lake? (public?)
            $check = $views->filter(function ($database_view) use ($table_name) {
                return $database_view->table_name == $table_name;
            });

            if ($check->count() > 0) {
                continue;
            }

            // Does the database exist in a studio project?
            foreach ($database->projects as $project) {
                foreach ($project->views as $project_view) {
                    if ($view->view_schema == $project_view->view_schema && $view->view_name == $project_view->view_name) {
                        $found = TRUE;
                    }
                }
            }
            
            $views->push((object) [
                "id"                => 0,
                "view_name"         => $view->view_name,
                "view_schema"       => $view->view_schema,
                "view_type"         => $view->view_type,
                "view_definition"   => $view->view_definition,
                "frequency"         => $this->frequency_default,
                "schedule"          => $this->schedule_default,
                "exists"            => $found,
                "synchronized"      => $found,
                "table_name"        => $table_name,
                "view_history_guid" => ""
            ]);
        }

        return response()->success($views);
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function create(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'name'      => 'required',
            'sql'       => 'required',
            'type'      => 'required',
            'frequency' => 'required',
            'schedule'  => 'required'
        ]);
        
        $build_on = $request->build_on ?? 'schedule';
        $upstream_build_id = $build_on == 'chained' && $request->upstream_build_id ? $request->upstream_build_id : NULL;
        $frequency = $request->type == 'normal' || $request->frequency == 'none' ? "" : $request->frequency;
        $schedule = $request->type == 'normal' ? "" : (object) $request->schedule;

        if (strlen($request->name) > 63) {
            return $this->_sendAjax("error", "View name cannot be more then 63 characters.", [], 400);
        }

        if (Table::exists($database, 'public', $request->name) && ! $request->recreate) {
            return response()->error("View name given is already in use. Give a different view name.");
        }

        // Only set our upstream_build_id if chained && materialized view
        if ($request->type == 'materialized' && $build_on == 'chained') {
            if (empty($upstream_build_id)) {
                return response()->error("You must select a view to trigger this view's refresh.");
            }
        }

        if (! View::test($database, $request->sql)) {
            return response()->error("Your query is invalid.");
        }

        try {
            View::create($database, 'public', $request->name, $request->type, $request->sql);
            
            if (empty(View::get($database, 'public', $request->name))) {
                throw new Exception('Could not retrieve view definition');
            }
        } catch (Exception $e) {
            logger()->error(
                "Database Manager: View could not be created\n" . $e->getMessage(),
                func_get_args()
            );

            return response()->error("View creation has failed.");
        }

        $db_view = View::get($database, 'public', $request->name);
        
        $view = ViewDefinition::create([
            'partner_integration_id' => $database->id,
            'view_history_guid'      => uniqid('view_'),
            'view_type'              => $request->type,
            'view_schema'            => 'public',
            'view_name'              => $request->name,
            'view_definition_sql'    => $db_view->view_definition,
            'view_user_sql'          => $request->sql,
            'created_by'             => auth()->user()->user_handle,
            'build_on'               => $build_on,
            'upstream_build_id'      => $upstream_build_id
        ]);

        $this->updateUpstreamView($upstream_build_id);

        if (! empty($frequency)) {
            ViewSchedule::create([
                'control_id'  => $database->id,
                'view_name'   => $request->name,
                'view_schema' => 'public',
                'frequency'   => $frequency,
                'schedule'    => $schedule
            ]);
        }

        // Schedule a Jenkins Job 
        if (! $this->integration_jenkins->createOrUpdateRefreshViewJob($database->id, 'public', $request->name, $frequency, $schedule)) {
            logger()->error(
                "Database Manager: Error occurred while creating or updating materialized view refresh Jenkins job",
                $request->all()
            );

            return response()->error("View creation has failed.");
        }

        $this->updateUpstreamView($view->id);

        // Try catch is here in case something failed in the past and no foreign data table currently exists
        try {
            ForeignDatabase::addTable($database, $request->name);
        } catch (Exception $e) {
        }

        return response()->success([], 'Created view successfully.');
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function rebuild(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'view_id' => 'required|integer|min:1'
        ]);

        $view = ViewDefinition::find($request->view_id);

        if (empty($view)) {
            return response()->error("View was not found and could not be rebuilt.");
        }

        $result = View::rebuild($database, $view);
        
        if (! $result) {
            return response()->error("View failed to rebuild.");
        }

        ForeignDatabase::addTable($database, $view->view_name);

        return response()->success();
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function refresh(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'name'   => 'required',
            'schema' => 'required'
        ]);

        $result = $this->integration_jenkins->runRefreshView($database, $request->schema, $request->name);

        if (! $result) {
            return response()->error("View refresh has failed.");
        }

        return response()->success();
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function update(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'history_guid' => 'required',
            'name'         => 'required',
            'sql'          => 'required',
            'type'         => 'required',
            'frequency'    => 'required',
            'schedule'     => 'required',
            'orig_name'    => 'required',
            'orig_type'    => 'required',
        ]);

        $orig_view = ViewDefinition::where('view_history_guid', $request->history_guid)->first();

        $foreign_dependencies = Connection::getForeignObjectDependencies($database, $request->orig_name);
        $build_on = $request->build_on ?? 'schedule';
        $upstream_build_id = $build_on == 'chained' && $request->upstream_build_id ? $request->upstream_build_id : NULL;
        $frequency = $request->type == 'normal' || $request->frequency == 'none' ? "" : $request->frequency;
        $schedule = $request->type == 'normal' ? "" : (object) $request->schedule;

        if (count($foreign_dependencies) > 0) {
            $dependent_databases = implode(', ', array_column($foreign_dependencies, 'foreign_database'));

            return response()->error("Your view cannot be modified because another view in these database(s) depends on it.\n" . $dependent_databases);
        }

        if (strlen($request->name) > 63) {
            return $this->_sendAjax("error", "View name cannot be more then 63 characters.", [], 400);
        }

        if ($request->name != $request->orig_name && Table::exists($database, 'public', $request->name)) {
            return response()->error("View name given is already in use. Give a different view name.");
        }

        // Only set our upstream_build_id if chained && materialized view
        if ($request->type == 'materialized' && $build_on == 'chained') {
            if (empty($upstream_build_id)) {
                return response()->error("You must select a view to trigger this view's refresh.");
            }
        }

        if (! View::test($database, $request->sql)) {
            return response()->error("Your query is invalid.");
        }
        
        try {
            $old_db_view = View::get($database, 'public', $request->orig_name);
            $definitions = [];

            if (! empty($old_db_view)) {
                $definitions = View::dropForTable($database, 'public', $request->orig_name);
                View::drop($database, 'public', $request->orig_name, $request->orig_type);
            }
        } catch (Exception $e) {
            return response()->error("Unable to update view at this time. Database view couldn't be found.");
        }

        try {
            View::create($database, 'public', $request->name, $request->type, $request->sql);
        } catch (Exception $e) {
            View::create($database, 'public', $request->orig_name, $request->orig_type, $old_db_view->view_definition);
            View::createFromDefinitions($database, $definitions);

            return response()->error("Unable to publish view at this time.");
        }

        $view_results = View::createFromDefinitions($database, $definitions);

        if (array_search(FALSE, $view_results) !== FALSE) {
            // Failed to recreate dependent views.  Someone probably changed or removed a dependent column
            // Put everything back to the way it was.
            View::drop($database, 'public', $request->name, $request->type);
            View::create($database, 'public', $request->orig_name, $request->orig_type, $old_db_view->view_definition);
            View::createFromDefinitions($database, $definitions);

            return response()->error("Due to dependent views, we are unable to update this view at this time.");
        }

        $db_view = View::get($database, 'public', $request->name);

        $orig_view->update([
            'view_type'           => $request->type,
            'view_name'           => $request->name,
            'view_definition_sql' => $db_view->view_definition,
            'view_user_sql'       => $request->sql,
            'updated_by'          => auth()->user()->user_handle,
            'build_on'            => $build_on,
            'upstream_build_id'   => $upstream_build_id
        ]);
        $view = ViewDefinition::where('view_history_guid', $request->history_guid)->first();

        if ($request->type != 'materialized' || $orig_view->upstream_build_id != $view->upstream_build_id) {
            $this->updateUpstreamView($orig_view->upstream_build_id);
        }

        if ($request->type != 'materialized' || $request->name != $request->orig_name) {
            // Make sure we remove our view job from Jenkins if not materialized or name has changed
            $this->integration_jenkins->deleteRefreshViewJob($database->id, $orig_view->view_schema, $orig_view->view_name);
        }

        $this->updateUpstreamView($upstream_build_id);

        if ($build_on == 'chained') {
            // Make sure we soft delete the schedule of a chained mat view if it exists
            ViewSchedule::where('control_id', $database->id)
                ->where('view_name', $request->orig_name)
                ->where('view_schema', 'public')
                ->delete();

            $frequency = '';
            $schedule = '';
        } else {
            if (! empty($frequency)) {
                ViewSchedule::updateOrCreate(
                    [
                        'control_id'  => $database->id,
                        'view_name'   => $request->orig_name,
                        'view_schema' => 'public'
                    ],
                    [
                        'view_name'   => $request->name,
                        'view_schema' => 'public',
                        'frequency'   => $frequency,
                        'schedule'    => (array) $schedule
                    ]
                );
            }
        }

        // Schedule a Jenkins Job
        if ($request->type == 'materialized') {
            if (! $this->integration_jenkins->createOrUpdateRefreshViewJob($database->id, 'public', $request->name, $frequency, $schedule)) {
                logger()->error(
                    "Database Manager: Error occurred while creating or updating materialized view refresh Jenkins job",
                    $request->all()
                );

                return response()->error("View creation has failed.");
            }
        } else {
            if ($request->type != 'materialized') {
                $view->downstreamBuilds()->update(['upstream_build_id' => NULL]);
            }
        }

        $this->updateUpstreamView($view->id);

        // Try catch is here in case something failed in the past and no foreign data table currently exists
        try {
            ForeignDatabase::addTable($database, $request->name);
            if ($request->name != $request->orig_name) {
                ForeignDatabase::removeTable($database, $request->orig_name);

                $projects = Project::where('partner_integration_id', $database->id)->get();
                foreach ($projects as $project) {
                    ProjectTableNote::where('project_id', $project->id)
                        ->where('schema', 'public')
                        ->where('table', $request->orig_name)
                        ->update(["table" => $request->name]);
                }
            }
        } catch (Exception $e) {
        }

        return response()->success([], 'View updated successfully.');
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function destroy(Request $request, PartnerIntegration $database, ViewDefinition $view)
    {
        $ignore_warning = filter_var($request->ignore_warning, FILTER_VALIDATE_BOOLEAN);

        $schedules = ProjectPublishingSchedule::database($database)
            ->where('schema_name', $view->view_schema)
            ->where('table_name', $view->view_name)
            ->get();

        if ($schedules->count() > 0 && ! $ignore_warning) {
            return response()->success("warning", "$view->view_name is being published on a recurring basis. Deleting this table will also delete the publishing job. Do you want to continue?");
        }

        $result = View::drop($database, 'public', $view->view_name, $view->view_type);

        if (! $result) {
            return response()->error("View delete has failed.");
        }

        $this->integration_jenkins->deleteRefreshViewJob($database->id, $view->view_schema, $view->view_name);

        ForeignDatabase::removeTable($database, $view->view_name);
        if ($request->has('schedule_id') && ! empty($request->schedule_id)) {
            ViewSchedule::find($request->schedule_id)->delete();
        }

        if ($schedules->count() > 0) {
            app(IntegrationJenkins::class)->removePublishJobForDatabase($database, $view->view_schema, $view->view_name);

            foreach ($schedules as $schedule) {
                $schedule->delete();
            }
        }

        $view->delete();

        return response()->empty();
    }

    public function updateUpstreamView(int|NULL $view_id)
    {
        if (empty($view_id)) {
            return TRUE;
        }
        
        $view = ViewDefinition::find($view_id);

        if (empty($view)) {
            logger()->error(
                "Database Manager: Error trying to update an upstream view (updateUpstreamView).",
                func_get_args()
            );

            return FALSE;
        } else if ($view->view_type == 'normal') {
            return TRUE;
        }

        $upstream_views = ViewDefinition::where('upstream_build_id', $view->id)
            ->get()
            ->map(function ($upstream_view) {
                return $upstream_view->view_name;
            })->toArray();

        // Build out our string for Jenkins post-build jobs
        $downstream_view_string = implode(', ', $upstream_views);

        $frequency = $this->frequency_default;
        $schedule = $this->schedule_default;

        $schedule_row = ViewSchedule::where('control_id', $view->partner_integration_id)
            ->where('view_name', $view->view_name)
            ->where('view_schema', $view->view_schema)
            ->first();

        if (! empty($schedule_row)) {
            $schedule = $schedule_row->schedule;
            $frequency = $schedule_row->frequency;
        }

        return $this->integration_jenkins->createOrUpdateRefreshViewJob(
            $view->partner_integration_id,
            $view->view_schema,
            $view->view_name,
            $frequency,
            $schedule,
            $downstream_view_string
        );
    }
}
