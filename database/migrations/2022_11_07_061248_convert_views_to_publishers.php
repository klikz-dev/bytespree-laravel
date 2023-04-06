<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Manager\ViewSchedule;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectView;
use App\Models\Explorer\PublishingDestination;
use App\Models\Explorer\ProjectPublishingSchedule;
use App\Classes\IntegrationJenkins;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $projects = Project::get();

        foreach($projects as $project) 
        {
            $project_views  = ProjectView::where('project_id', $project->id)->get();
            
            foreach($project_views as $project_view)
            {
                $view_schedule = ViewSchedule::where('control_id', $project->id)
                    ->where('view_schema', $project_view->view_schema)
                    ->where('view_name', $project_view->view_name)
                    ->first();

                if(! empty($view_schedule)) {
                    $schedule = (object) $view_schedule->schedule;
                    $schedule->frequency = $view_schedule->frequency;
                    $options = [ "name" => $project_view->view_name ];

                    $result = $this->createSchedule($project, $project_view->view_schema, $project_view->view_name, $schedule, $options, $project_view->id);

                    if($result) {
                        $view_schedule->delete();
                        $this->deleteOldSchedule($project, $project_view->view_schema, $project_view->view_name);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach($projects as $project) 
        {
            $project_views  = ProjectView::where('project_id', $project->id)->get();
            
            foreach($project_views as $project_view)
            {
                $view_schedule = ViewSchedule::where('control_id', $project->id)
                    ->where('view_schema', $project_view->view_schema)
                    ->where('view_name', $project_view->view_name)
                    ->withTrashed()
                    ->first();

                if(! empty($view_schedule)) {
                    $schedule = (object) $view_schedule->schedule;
                    $schedule->frequency = $view_schedule->frequency;

                    $result = $this->createOldSchedule($project, $project_view->view_schema, $project_view->view_name, $schedule);

                    if($result) {
                        $view_schedule->update([ 'deleted_at' => NULL ]);
                        $this->deleteSchedule($project, $project_view->view_schema, $project_view->view_name, $project_view->id);
                    }
                }
            }
        }
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
                'username'            => NULL,
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
                'user'           => NULL,
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

    public function createOldSchedule(Project $project, string $schema, string $table, object $schedule)
    {
        return app(IntegrationJenkins::class)->createOrUpdateRefreshViewJob(
            $project->primary_database->id,
            $schema,
            $table,
            $schedule->frequency,
            $schedule
        );
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
                'user'           => NULL,
                'table'          => $table,
                'schema'         => $schema
            ]);

            return FALSE;
        }
        
        $publishing_schedule->delete();

        return TRUE;
    }

    public function deleteOldSchedule(Project $project, string $schema, string $table)
    {
        return app(IntegrationJenkins::class)->deleteRefreshViewJob($project->primary_database->id, $schema, $table);
    }
};
