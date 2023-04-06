<?php

namespace App\Classes;

use App\Models\PartnerIntegration;
use App\Models\Manager\ViewSchedule;
use App\Models\Explorer\Project;
use App\Models\Explorer\PublishingDestination;
use App\Models\Explorer\ProjectPublishingSchedule;
use App\Models\IntegrationScheduleType;
use App\Models\Manager\JenkinsBuild;
use Exception;

class IntegrationJenkins extends Jenkins
{
    /**
     * Check if Jenkins is available
     *
     * @return bool
     */
    public function checkAvailability()
    {
        $jenkins_url = config("services.jenkins.region_url");
        $this->setJenkins($jenkins_url);

        return $this->jenkins->isAvailable();
    }

    /**
     * Get console text for a Jenkins Build
     *
     * @param  string       $database  Name of the database
     * @param  string       $team      Name of the team
     * @param  string       $name      Name of the Jenkins job
     * @param  int          $timestamp Timestamp of a build
     * @param  string       $type      Type of delivery (file or email)
     * @return string|array
     */
    public function getLogConsoleText($database, $team, $name, $timestamp, $type)
    {
        $jenkins_url = config("services.jenkins.region_url");
        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/sync");

        try {
            $job = $this->jenkins->getJob($name);
            if ($job) {
                $builds = $job->getBuilds();
            } else {
                $builds = [];
            }
        } catch (Exception $e) {
            $builds = [];
        }

        foreach ($builds as $build) {
            if ($timestamp == str_replace(".", "", $build->getTimestamp())) {
                if ($type == "file") {
                    return $this->getConsoleTextFile("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/sync", $name, $build->getNumber());
                } elseif ($type == "email") {
                    return $this->getConsoleText("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/sync", $name, $build->getNumber());
                }
            }
        }
    }

    /**
     * Get the log text for a Jenkins Build
     */
    public function getConsoleLogTextByBuildId(string $database, string $team, string $name, $build_id)
    {
        $jenkins_url = config("services.jenkins.region_url");
        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/sync");
        
        return $this->getConsoleText("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/sync", $name, $build_id);
    }

    /**
     * Get sync jobs for a team and database
     *
     * @param  string $database Name of database
     * @param  string $team     Name of team
     * @return array
     */
    public function getDatabaseJobs($database, $team)
    {
        $jenkins_url = $this->environment->getJenkinsUrl();
        $this->setJenkins($jenkins_url);

        $queue_list = $this->getQueue($jenkins_url);

        $queue = [];
        if (! empty($queue_list)) {
            foreach ($queue_list->items as $queue_item) {
                $queue[] = [
                    "name" => $queue_item->task->name,
                    "url"  => $queue_item->task->url
                ];
            }
        }

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/sync");

        try {
            $sync_jobs = $this->jenkins->getJobs();
        } catch (Exception $err) {
            $sync_jobs = [];
        }

        $syncs = [];
        $color = "blue";
        foreach ($sync_jobs as $sync_job) {
            $last_build = $sync_job->getLastBuild();
            if (empty($last_build)) {
                // Has not been run
                $running = FALSE;
            } else {
                $running = $last_build->isRunning();
            }

            if ($running === FALSE) {
                foreach ($queue as $queue_item) {
                    if ($sync_job->getName() == $queue_item["name"] && strpos($queue_item["url"], $database)) {
                        $running = TRUE;
                    }
                }
            }

            if ($sync_job->getColor() == "red") {
                $color = "red";
            }

            if ($sync_job->getName() == "sync") {
                $syncs = [
                    "name"       => $sync_job->getName(),
                    "is_running" => $running,
                    "database"   => $database
                ];
            } else {
                $syncs[] = [
                    "name"       => $sync_job->getName(),
                    "is_running" => $running,
                    "database"   => $database,
                    "color"      => $sync_job->getColor()
                ];
            }
        }

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/test");

        try {
            $test_jobs = $this->jenkins->getJobs();
        } catch (Exception $err) {
            $test_jobs = [];
        }

        foreach ($test_jobs as $test_job) {
            if ($test_job->getColor() == "red") {
                $color = "yellow";
            }
        }

        if (empty($syncs)) {
            $syncs = ["name" => "", "is_running" => TRUE];
        }

        return ["syncs" => $syncs, "color" => $color];
    }

    /**
     * Run an integration
     *
     * @param  string             $name     Name of Jenkins job
     * @param  PartnerIntegration $database Name of database
     * @return bool
     */
    public function runIntegration(string $name, PartnerIntegration $database)
    {
        $jenkins_url = config("services.jenkins.region_url");
        $team = app('environment')->getTeam();

        if ($name == "sync") {
            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}/job/sync");
        } else {
            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}/job/test");
        }

        try {
            $job = $this->jenkins->getJob($name);
        } catch (Exception $e) {
            $job = NULL;
        }

        if (! empty($job)) {
            if (! empty($job->getLastBuild())) {
                if ($job->getLastBuild()->isRunning()) {
                    return FALSE;
                }
            }
        }

        try {
            $this->jenkins->launchJob($name);

            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Create all build and sync jobs and launch the builds
     *
     * @param  int   $control_id The id of the database record
     * @param  array $tables     (optional) The tables for the builds we are creating
     * @return void
     */
    public function createAllJobs(PartnerIntegration $database, $tables = [])
    {
        $team = app('environment')->getTeam();

        if (empty($tables) && $database->integration->use_tables === TRUE) {
            $tables = $database->tables->pluck('name')->toArray();
        }

        $this->createDatabaseFolders($database);

        $success = $this->createAllBuilds($database, $team, $tables);

        if (! $success) {
            logger()->error(
                "Could not create build jobs",
                compact('database', 'team', 'tables')
            );

            return;
        }
        $success = $this->createAllSyncs($database, $team, $tables);
        if (! $success) {
            logger()->error(
                "Could not create sync jobs",
                compact('database', 'team', 'tables')
            );

            return;
        }
    }

    /**
     * Creates the build jobs for a database
     *
     * @param  PartnerIntegration $database The database we are creating
     * @param  string             $team     The team for the jobs we are building
     * @param  array              $tables   An array of tables to create build jobs for
     * @return void
     */
    public function createAllBuilds($database, $team, $tables = [])
    {
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}");
        $this->checkJobExistsAndCreateFolderIfNot("build");

        if (! empty($tables) && is_array($tables)) {
            foreach ($tables as $table) {
                if (! $this->createOrUpdateBuildJob($database, $table)) {
                    return FALSE;
                }
            }
        } else {
            if (! $this->createOrUpdateBuildJob($database)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Creates the sync and test folders and populates them with tables
     *
     * @param  PartnerIntegration $database The database we are creating
     * @param  string             $team     The team for the jobs we are building
     * @param  array              $tables   An array of tables to create sync jobs for
     * @return bool
     */
    public function createAllSyncs(PartnerIntegration $database, $team, $tables = [])
    {
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}");
        $this->checkJobExistsAndCreateFolderIfNot("sync");

        if (! empty($tables) && is_array($tables)) {
            $this->checkJobExistsAndCreateFolderIfNot("test");
            foreach ($tables as $table) {
                if (! $this->createOrUpdateSyncJob($database, $table)) {
                    return FALSE;
                }
                if (! $this->createOrUpdateTestJob($database, $table)) {
                    return FALSE;
                }
            }
        } else {
            if (! $this->createOrUpdateSyncJob($database)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Run builds for tables specified
     *
     * @param  PartnerIntegration $database The database record
     * @param  array              $tables   (Optional) An array of tables to run build builds for
     * @return void
     */
    public function runBuilds(PartnerIntegration $database, $tables = [])
    {
        $jenkins_url = config("services.jenkins.region_url");
        $team = app('environment')->getTeam();

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}/job/build");
        if (! empty($tables) && is_array($tables)) {
            foreach ($tables as $table) {
                try {
                    $this->jenkins->launchJob($table);
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    logger()->error(
                        "Failed to launch build job for table $table in team $team for database {$database->database}",
                        compact(['control_id', 'team', 'database', 'table', 'message'])
                    );
                    throw new Exception("Failed to launch Jenkins job. See previous log entry.");
                }
            }
        } else {
            try {
                $this->jenkins->launchJob('build');
            } catch (Exception $e) {
                $message = $e->getMessage();
                logger()->error(
                    "Failed to launch build job in team $team for database {$database->database}",
                    compact(['control_id', 'team', 'database', 'message'])
                );
                throw new Exception("Failed to launch Jenkins job. See previous log entry.");
            }
        }
    }

    /**
     * Delete an integration from Jenkins.
     *
     * @param  int    $control_id The id of the PartnerIntegration being deleted
     * @param  string $team       The name of the team/domain whose ParterIntegration is being deleted
     * @return bool   Whether or not the Jenkins request was successful (NOT the status of the job)
     */
    public function deleteIntegration(PartnerIntegration $database)
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}");

        $this->jenkins->deleteJob($database->database);

        JenkinsBuild::handleIntegrationDelete($database);

        return TRUE;
    }

    /**
     * Updates or deletes tables from the warehouse
     *
     * @param  PartnerIntegration $database The database we are 
     * @param  array              $tables   The tables being edited
     * @return void
     */
    public function updateTables(PartnerIntegration $database, array $tables)
    {
        foreach ($tables as $key => $table) {
            $added = $table['added'] ?? FALSE;
            $changed = $table['changed'] ?? FALSE;
            $deleted = $table['deleted'] ?? FALSE;

            if ($added) {
                continue;
            } else if ($changed) {
                if ($table["name"] != $table["orig_name"]) {
                    $deleted_tables[] = $table["orig_name"];
                } else {
                    unset($tables[$key]);
                }
            } else if ($deleted) {
                $deleted_tables[] = $table["orig_name"];
                unset($tables[$key]);
            } else {
                unset($tables[$key]);
            }
        }

        if (! empty($deleted_tables)) {
            $this->deleteTables($database->database, $deleted_tables);
        }

        if (! empty($tables)) {
            $this->createAllJobs($database, array_column($tables, 'name'));
        }
    }

    /**
     * Deletes the jenkins jobs of the tables that are passed in
     *
     * @param  string $database_name The database for the tables we are deleting
     * @param  array  $tables        The tables we deleting
     * @return void
     */
    public function deleteTables($database_name, $tables)
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database_name}/job/test");

        foreach ($tables as $table) {
            $this->jenkins->deleteJob($table);
        }

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database_name}/job/sync");

        foreach ($tables as $table) {
            $this->jenkins->deleteJob($table);
        }

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database_name}/job/build");

        foreach ($tables as $table) {
            $this->jenkins->deleteJob($table);
        }
    }

    /**
     * Updates schedule of an existing Jenkins job
     *
     * @param  PartnerIntegration $database   The database record
     * @param  mixed              $schedule   The details of the schedule being assigned
     * @param  mixed              $table_name (optional) The name of the table
     * @return void
     */
    public function updateSchedule(PartnerIntegration $database, $schedule, $table_name = '')
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        if ($database->integration->use_tables === TRUE) {
            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}");
            $team_exists = $this->checkJobExistsAndCreateFolderIfNot($database->database);
            if (! $team_exists) {
                $this->createAllJobs($database);
            }
        }

        $schedule_str = '';

        if (! empty($schedule) && is_array($schedule) && IntegrationScheduleType::find($schedule['schedule_type_id'])->name != "Manually") {
            $schedule_str = $this->buildScheduleString($schedule);
        }

        if ($database->integration->use_tables === TRUE) {
            if (! empty($table_name)) {
                $this->createOrUpdateTestJob($database, $table_name, $schedule_str);
            } else {
                foreach ($database->tables as $table) {
                    $this->createOrUpdateTestJob($database, $table, $schedule_str);
                }
            }
        } else {
            $this->createOrUpdateSyncJob($database, 'sync', $schedule_str);
        }
    }

    /**
     * Creates the Jenkins job for the publisher
     *
     * @param  Project $project        The project we are creating this for
     * @param  string  $publisher_name Then name of the publisher
     * @param  string  $schema         The schema for the table
     * @param  string  $table          The name of the table we are publishing
     * @param  int     $schedule_id    The id of the publishing schedule record
     * @param  object  $schedule       The schedule of the job
     * @return bool
     */
    public function createOrUpdatePublishJob($project, $publisher_name, $schema, $table, $schedule_id, $schedule)
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");
        $database = $project->primary_database->database;

        if (! empty($database)) {
            $publisher_name = preg_replace('/\W/', '_', strtolower($publisher_name));

            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}");
            $this->checkJobExistsAndCreateFolderIfNot($database);

            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}");
            $this->checkJobExistsAndCreateFolderIfNot('publish');

            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/publish");
            $this->checkJobExistsAndCreateFolderIfNot($publisher_name);

            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/publish/job/$publisher_name");
            $this->checkJobExistsAndCreateFolderIfNot($schema);

            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/publish/job/$publisher_name/job/{$schema}");
            $this->checkJobExistsAndCreateFolderIfNot($table);

            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database}/job/publish/job/$publisher_name/job/{$schema}/job/{$table}");

            $schedule_str = "";
            $hour = $schedule->hour;
            $week_day = $schedule?->week_day ?? NULL;
            $month_day = $schedule?->month_day ?? NULL;
            $month = $schedule?->month ?? NULL;
            if ($schedule->frequency == "daily") {
                $schedule_str = "H {$hour} * * *";
            } elseif ($schedule->frequency == "weekly") {
                $schedule_str = "H {$hour} * * {$week_day}";
            } elseif ($schedule->frequency == "monthly") {
                $schedule_str = "H {$hour} {$month_day} * *";
            } elseif ($schedule->frequency == "annually") {
                $schedule_str = "H {$hour} {$month_day} {$month} *";
            }

            $application_path = rtrim(base_path(), '/');
            $command_string = "php {$application_path}/artisan publish:schedule {$schedule_id}";
            $publish_xml = $this->buildCommandConfig($command_string, "", NULL);
            if ($this->createOrUpdateJob("publish_{$schedule_id}", $publish_xml) === FALSE) {
                return FALSE;
            }

            $command_string = "php {$application_path}/artisan publish:test {$schedule_id}";
            $test_xml = $this->buildCommandConfig($command_string, $schedule_str, "publish_{$schedule_id}");
            if ($this->createOrUpdateJob("publish_{$schedule_id}_test", $test_xml) === FALSE) {
                return FALSE;
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Create Jenkins job to monitor Server disk usage
     *
     * @return bool TRUE if created, otherwise FALSE
     */
    public function createCheckDiskUsageJob()
    {
        $created = FALSE;
        $team = $this->environment->getTeamName();
        $jenkins_url = $this->environment->getJenkinsUrl();
        if ($jenkins_url) {
            $this->setJenkins("$jenkins_url/job/Integrations/job/$team/job/__functions");
            $exists = $this->checkJobExists("checkDiskUsage");
            if (! $exists) {
                $this->setJenkins("$jenkins_url");
                $this->checkJobExistsAndCreateFolderIfNot("Integrations");

                $this->setJenkins("$jenkins_url/job/Integrations");
                $this->checkJobExistsAndCreateFolderIfNot($team);

                $this->setJenkins("$jenkins_url/job/Integrations/job/$team");
                $this->checkJobExistsAndCreateFolderIfNot("__functions");

                $command = "php /var/www/$team/index.php Services ServerUsageCheck checkDiskUsage";
                $schedule = "H * * * *";
                $this->setJenkins("$jenkins_url/job/Integrations/job/$team/job/__functions");
                $created = $this->createCommandJob("checkDiskUsage", $command, $schedule);
                if (! $created) {
                    logger()->error("Error creating checkDiskUsage job: Jenkins API failure");
                }
            }
        } else {
            logger()->error("Error creating checkDiskUsage job: Environment variable JENKIN_URL is empty");
        }

        return $created;
    }

    /**
     * Deletes the publish job for a table in
     *
     * @param  PartnerIntegration $database The of the partner integration
     * @param  string             $schema   The schema of the table
     * @param  string             $table    The table we are checking
     * @return void
     */
    public function removePublishJobForDatabase($database, $schema, $table)
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");
    
        $publishers = ProjectPublishingSchedule::database($database)
            ->where('table_name', $table)
            ->where('schema_name', $schema)
            ->get();

        foreach ($publishers as $publisher) {
            $publisher_name = preg_replace('/\W/', '_', strtolower($publisher->destination->name));
            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}/job/publish/job/{$publisher_name}/job/{$schema}");

            try {
                $this->jenkins->deleteJob($table);
            } catch (Exception $e) {
                logger()->error(
                    "Jenkins publish job failed to delete. Message: " . $e->getMessage(),
                    [
                        'control_id' => $database->id,
                        'table'      => $table,
                        'schema'     => $schema
                    ]
                );
            }
        }
    }

    public function removePublishJobForProject(Project $project, string $schema = "", string $table = "")
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $publishers = ProjectPublishingSchedule::where('project_id', $project->id);

        if (! empty($schema)) {
            $publishers->where('table_name', $table)
                ->where('schema_name', $schema);
        }
            
        $publishers = $publishers->get();

        foreach ($publishers as $publisher) {
            $publisher_name = preg_replace('/\W/', '_', strtolower($publisher->destination->name));
            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$project->primary_database->database}/job/publish/job/{$publisher_name}/job/{$publisher->schema_name}/job/{$publisher->table_name}");

            try {
                $this->jenkins->deleteJob("publish_{$publisher->id}");
                $this->jenkins->deleteJob("publish_{$publisher->id}_test");
            } catch (Exception $e) {
                logger()->error(
                    "Jenkins publish job failed to delete. Message: " . $e->getMessage(),
                    [
                        'control_id' => $project->primary_database->id,
                        'project_id' => $project->id,
                        'table'      => $publisher->table_name,
                        'schema'     => $publisher->schema_name
                    ]
                );
            }
        }
    }

    public function removePublishProjectFolder(Project $project)
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $destinations = PublishingDestination::get();

        foreach ($destinations as $destination) {
            $destination_name = preg_replace('/\W/', '_', strtolower($destination->name));
            $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$project->primary_database->database}/job/publish/job/{$destination_name}");

            try {
                $this->jenkins->deleteJob($project->name);
            } catch (Exception $e) {
                // No logging because there will be multiple times this fails
            }
        }
    }

    /**
     * Remove publisher job from jenkins
     *
     * @param  ProjectPublishingSchedule $publisher The id of the publisher
     * @param  Project                   $project   This is the partner integration id
     * @param  string                    $schema    The schema this publisher is for
     * @param  string                    $table     The table this publisher is for
     * @return bool                      TRUE if publish job is deleted, otherwise FALSE
     */
    public function deletePublisherJob(ProjectPublishingSchedule $publisher, Project $project, string $schema, string $table)
    {
        $publisher_name = preg_replace('/\W/', '_', strtolower($publisher->destination->name));
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins("{$jenkins_url}/job/Integrations/job/{$team}/job/{$project->primary_database->database}/job/publish/job/{$publisher_name}/job/{$schema}/job/{$table}");

        $check_job = $this->jenkins->getJob("publish_{$publisher->id}");
        $check_test_job = $this->jenkins->getJob("publish_{$publisher->id}_test");
        if ($check_job && $check_test_job) {
            try {
                $this->jenkins->deleteJob("publish_{$publisher->id}_test");
                $this->jenkins->deleteJob("publish_{$publisher->id}");
            } catch (Exception $e) {
                logger()->error(
                    "Jenkins publish job failed to delete. Message: " . $e->getMessage(),
                    [
                        'publisher_id' => $publisher->id,
                        'control_id'   => $project->primary_database->id,
                        'table_name'   => $table,
                        'schema'       => $schema
                    ]
                );

                return FALSE;
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Start a refresh view build
     *
     * @param  PartnerIntegration $database    This is the partner integration id
     * @param  string             $view_schema This is the view schema
     * @param  string             $view_name   This is the view name to refresh
     * @return bool               TRUE if build is started, otherwise FALSE
     */
    public function runRefreshView($database, $view_schema, $view_name)
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins("$jenkins_url/job/Integrations/job/$team/job/$database->database/job/publish/job/views/job/$view_schema");

        $check_job = $this->jenkins->getJob($view_name);
        if ($check_job) {
            $launched = $this->jenkins->launchJob($view_name);

            return $launched;
        }

        $view_schedule = ViewSchedule::where('control_id', $database->id)
            ->where('view_schema', $view_schema)
            ->where('view_name', $view_name)
            ->first();

        if ($view_schedule) {
            $frequency = $view_schedule->frequency;
            $schedule = $view_schedule->schedule;
        } else {
            $frequency = "";
            $schedule = "";
        }

        $created = $this->createOrUpdateRefreshViewJob($database->id, $view_schema, $view_name, $frequency, $schedule);
        if ($created) {
            $launched = $this->jenkins->launchJob($view_name);
            if ($launched) {
                logger()->info(
                    "Jenkins job to refresh $view_schema.$view_name was missing but was created and launched.",
                    func_get_args()
                );

                return TRUE;
            }

            logger()->error(
                "Jenkins job to refresh $view_schema.$view_name was missing and was created but failed to launch.",
                func_get_args()
            );
        } else {
            logger()->error(
                "Jenkins job to refresh $view_schema.$view_name was missing and could not be created.",
                func_get_args()
            );
        }

        return FALSE;
    }

    /**
     * Start a refresh view build
     *
     * @param  int    $control_id  This is the partner integration id
     * @param  string $view_schema This is the view schema
     * @param  string $view_name   This is the view name to delete
     * @return bool   TRUE if refresh job is started, otherwise FALSE
     */
    public function deleteRefreshViewJob($database_id, $view_schema, $view_name)
    {
        $database = PartnerIntegration::find($database_id);
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins("$jenkins_url/job/Integrations/job/$team/job/$database->database/job/publish/job/views/job/$view_schema");

        $check_job = $this->jenkins->getJob($view_name);
        if ($check_job) {
            $result = $this->jenkins->deleteJob($view_name);

            return $result;
        }

        return FALSE;
    }

    /**
     * This function create a refresh view job inside Jenkins
     *
     * @param  int    $database         This is the partner integration id
     * @param  string $view_schema      This is the view schema
     * @param  string $view_name        This is the view name which needs to refresh job
     * @param  string $frequency        this is the schedule frequency like daily, weekly, monthly etc
     * @param  array  $schedule         The schedule of the job
     * @param  int    $after_build_view The command to after after the build ends
     * @return bool   Returns TRUE if job is created, otherwise FALSE
     */
    public function createOrUpdateRefreshViewJob($database_id, $view_schema, $view_name, $frequency = "", $schedule = "", $after_build_view = NULL)
    {
        // Called inside function to make getting different databases easier
        $database = PartnerIntegration::find($database_id);
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins("$jenkins_url/job/Integrations/job/$team");
        $this->checkJobExistsAndCreateFolderIfNot($database->database);

        $this->setJenkins("$jenkins_url/job/Integrations/job/$team");
        $this->checkJobExistsAndCreateFolderIfNot($database->database);

        $this->setJenkins("$jenkins_url/job/Integrations/job/$team/job/$database->database");
        $this->checkJobExistsAndCreateFolderIfNot('publish');

        $this->setJenkins("$jenkins_url/job/Integrations/job/$team/job/$database->database/job/publish");
        $this->checkJobExistsAndCreateFolderIfNot('views');

        $this->setJenkins("$jenkins_url/job/Integrations/job/$team/job/$database->database/job/publish/job/views");
        $this->checkJobExistsAndCreateFolderIfNot($view_schema);

        $this->setJenkins("$jenkins_url/job/Integrations/job/$team/job/$database->database/job/publish/job/views/job/$view_schema");

        $application_path = rtrim(base_path(), '/');

        $schedule_str = "";
        if (! empty($frequency)) {
            if (is_array($schedule)) {
                $schedule = (object) $schedule;
            }

            if ($frequency == "daily") {
                $schedule_str = "H {$schedule->hour} * * *";
            } else if ($frequency == "weekly") {
                $schedule_str = "H {$schedule->hour} * * {$schedule->week_day}";
            } else if ($frequency == "monthly") {
                $schedule_str = "H {$schedule->hour} {$schedule->month_day} * *";
            } else if ($frequency == "annually") {
                $schedule_str = "H {$schedule->hour} {$schedule->month_day} {$schedule->month} *";
            }
        }

        $command_string = "php {$application_path}/artisan view:refresh {$database_id} {$view_schema} {$view_name}";
        // Check If Job Already Exist
        $check_job = $this->jenkins->getJob($view_name);
        $job_exists = FALSE;
        if ($check_job) {
            $job_exists = TRUE;
        }

        if ($schedule_str) {
            if ($job_exists) {
                $result = $this->updateCommandJob($view_name, $command_string, $schedule_str, $after_build_view);
            } else {
                $result = $this->createCommandJob($view_name, $command_string, $schedule_str, $after_build_view);
            }
        } else {
            if ($job_exists) {
                $result = $this->updateCommandJob($view_name, $command_string, NULL, $after_build_view);
            } else {
                $result = $this->createCommandJob($view_name, $command_string, NULL, $after_build_view);
            }
        }

        return $result;
    }

    /**
     * Build command string for running syncs, builds, and tests
     *
     * @param  string $command_type Type of command. Possible values are sync, build, and test
     * @param  int    $control_id   Partner integraion ID that is being ran
     * @param  string $table_name   (optional) Name of table to build or sync (not valid for test commands)
     * @return void
     */
    public function buildCommandString($command_type, $control_id, $table_name = '')
    {
        $application_path = rtrim(base_path(), '/');
        $command = "php {$application_path}/artisan connector:run {$command_type} {$control_id}";
        if (! empty($table_name) && ($command_type == 'sync' || $command_type == 'build')) {
            $command .= " --table={$table_name}";
        }

        return $command;
    }

    /**
     * Build a Jenkins schedule string from an array
     *
     * @param  array  $schedule Array containing schedule details
     * @return string
     */
    public function buildScheduleString($schedule)
    {
        $schedule_str = '';

        if ($schedule['name'] == 'Every Hour') {
            $schedule_str = "H * * * *";
        } elseif ($schedule['name'] == 'Every 15 minutes') {
            $schedule_str = "H/15 * * * *";
        } elseif (is_array($schedule["properties"])) {
            $day_of_week = '*';
            $day_of_month = '*';
            $hour = '*';

            $week_letters_to_numbers = [
                "m" => 1,
                "t" => 2,
                "w" => 3,
                "h" => 4,
                "f" => 5,
                "s" => 6,
                "u" => 7
            ];

            foreach ($schedule["properties"] as $property) {
                if ($property['name'] == "Hour" && (! empty($property['value']) || $property['value'] == "0")) {
                    $hour = $property['value'];
                } elseif ($property['name'] == "Day of Week" && ! empty($property['value'])) {
                    $day_of_week = $week_letters_to_numbers[strtolower($property['value'])];
                } elseif ($property['name'] == "Day of Month" && ! empty($property['value'])) {
                    if ($property['value'] == "last") {
                        $property['value'] = "28";
                    }

                    $day_of_month = $property['value'];
                }
            }

            $schedule_str = "H {$hour} {$day_of_month} * {$day_of_week}";
        } else {
            logger()->error('Cannot build schedule string due to invalid schedule');
        }

        return $schedule_str;
    }

    /**
     * Create Jenkins folder structure for database
     *
     * @return void
     */
    public function createDatabaseFolders(PartnerIntegration $database)
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");

        $this->setJenkins($jenkins_url);
        $this->checkJobExistsAndCreateFolderIfNot("Integrations");

        $base_jenkins_url = "{$jenkins_url}/job/Integrations/";
        $this->setJenkins($base_jenkins_url);
        $this->checkJobExistsAndCreateFolderIfNot($team);

        $base_jenkins_url = "{$jenkins_url}/job/Integrations/job/$team";
        $this->setJenkins($base_jenkins_url);
        $this->checkJobExistsAndCreateFolderIfNot($database->database);
    }

    /**
     * Create or update the build job for a database and an optional table
     *
     * @param  PartnerIntegration $database   The name of database used by the build
     * @param  string             $table_name (optional) The name of table that is used by the build
     * @return bool               TRUE if job is created or updated, otherwise FALSE
     */
    public function createOrUpdateBuildJob(PartnerIntegration $database, $table_name = '')
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");
        $base_jenkins_url = "{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}";
        $this->setJenkins($base_jenkins_url . "/job/build");
        $command = $this->buildCommandString('build', $database->id, $table_name);

        $post_build_job = "../sync/{$table_name}";
        if (empty($table_name)) {
            $table_name = 'build';
            $post_build_job = "../sync/sync";
        }

        $xml = $this->buildCommandConfig($command, '', $post_build_job, TRUE);
        $success = $this->createOrUpdateJob($table_name, $xml);

        if (! $success) {
            logger()->error(
                "Failed to create build job for table $table_name in database {$database->database} for team $team",
                [
                    "control_id" => $database->id,
                    "table_name" => $table_name
                ]
            );
        }

        return $success;
    }

    /**
     * Create or update the sync job for a database and an optional table
     *
     * @param  PartnerIntegration $database     The name of database used by the sync
     * @param  string             $table_name   (optional) The name of table that is used by the sync
     * @param  string             $schedule_str (optional) The Jenkins schedule string for job
     * @return bool               TRUE if job is created or updated, otherwise FALSE
     */
    public function createOrUpdateSyncJob(PartnerIntegration $database, $table_name = '', $schedule_str = '')
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");
        $base_jenkins_url = "{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}";
        $this->setJenkins($base_jenkins_url . "/job/sync");
        $command = $this->buildCommandString('sync', $database->id, $table_name);

        if (empty($table_name)) {
            $table_name = 'sync';
        }

        $xml = $this->buildCommandConfig($command, $schedule_str, NULL, TRUE);
        $success = $this->createOrUpdateJob($table_name, $xml);

        if (! $success) {
            logger()->error(
                "Failed to create sync job for table $table_name in database {$database->database} for team $team",
                [
                    "control_id" => $database->id,
                    "table_name" => $table_name
                ]
            );
        }

        return $success;
    }

    /**
     * Create or update the test job for a database and table
     *
     * @param  PartnerIntegration $database     The name of database used by the test
     * @param  string             $table_name   The name of table that is synced after the test
     * @param  string             $schedule_str The Jenkins schedule string for job
     * @return bool               TRUE if job is created or updated, otherwise FALSE
     */
    public function createOrUpdateTestJob(PartnerIntegration $database, $table_name, $schedule_str = '')
    {
        $team = app('environment')->getTeam();
        $jenkins_url = config("services.jenkins.region_url");
        $base_jenkins_url = "{$jenkins_url}/job/Integrations/job/{$team}/job/{$database->database}";
        $this->setJenkins($base_jenkins_url . "/job/test");
        $command = $this->buildCommandString('test', $database->id);

        $post_build_job = "../sync/{$table_name}";

        $xml = $this->buildCommandConfig($command, $schedule_str, $post_build_job, NULL, TRUE);
        $success = $this->createOrUpdateJob($table_name, $xml);

        if (! $success) {
            logger()->error(
                "Failed to create test job for table $table_name in database {$database->database} for team $team",
                [
                    "control_id" => $database->id,
                    "table_name" => $table_name
                ]
            );
        }

        return $success;
    }

    // /**
    //  * Rebuild all integrations jobs for the specified partner integration ID
    //  *
    //  * @param  int  $control_id The partner integration ID to rebuild jobs for
    //  * @return void
    //  */
    // public function rebuildIntegrationJobs($control_id)
    // {
    //     $team = $this->environment->getTeamName();
    //     $jenkins_url = $this->environment->getJenkinsUrl();

    //     $partner_integration = $this->PartnerIntegrationsModel->getById($control_id);
    //     if (empty($partner_integration)) {
    //         logger()->error(
    //             "Could not find partner integration ID $control_id"
    //         );

    //         return;
    //     }

    //     if ($partner_integration['integration_id'] == 0 || $partner_integration["class_name"] == 'Basic') {
    //         return;
    //     }

    //     $database = $partner_integration["database"];
    //     $this->createDatabaseFolders($database);

    //     $base_jenkins_url = "{$jenkins_url}/job/Integrations/job/$team/job/$database";

    //     $this->setJenkins($base_jenkins_url);
    //     $this->checkJobExistsAndCreateFolderIfNot("build");
    //     $this->checkJobExistsAndCreateFolderIfNot("sync");

    //     if ($partner_integration["use_tables"] == "t") {
    //         $this->checkJobExistsAndCreateFolderIfNot("test");

    //         $tables = $this->PartnerIntegrationTablesModel->getByControlId($control_id);
    //         foreach ($tables as $table) {
    //             $id = $table["id"];
    //             $table_name = $table["name"];
    //             $table_schedule = $this->PartnerIntegrationTableSchedulesModel->getPartnerIntegrationTableSchedule($id);
    //             if (empty($table_schedule)) {
    //                 logger()->error(
    //                     "Schedule not found for table $table_name in database $database for team $team",
    //                     [
    //                         "control_id" => $control_id,
    //                         "table_name" => $table_name
    //                     ]
    //                 );
    //                 continue;
    //             }

    //             $schedule_str = "";
    //             if ($table_schedule["name"] !== "Manually") {
    //                 $schedule_str = $this->buildScheduleString($table_schedule);
    //             }

    //             $this->createOrUpdateBuildJob($control_id, $database, $table_name);
    //             $this->createOrUpdateSyncJob($control_id, $database, $table_name);
    //             $this->createOrUpdateTestJob($control_id, $database, $table_name, $schedule_str);
    //         }
    //     } else {
    //         $schedule = $this->PartnerIntegrationSchedulesModel->getPartnerIntegrationSchedule($control_id);

    //         if (empty($schedule)) {
    //             logger()->error(
    //                 "A schedule was not found for a non-table sync in database $database for team $team",
    //                 [
    //                     "control_id" => $control_id
    //                 ]
    //             );
    //         }

    //         $schedule_str = "";
    //         if ($schedule["name"] !== "Manually") {
    //             $schedule_str = $this->buildScheduleString($schedule);
    //         }

    //         $this->createOrUpdateBuildJob($control_id, $database);
    //         $this->createOrUpdateSyncJob($control_id, $database, '', $schedule_str);
    //     }
    // }
}