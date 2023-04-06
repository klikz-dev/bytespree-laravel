<?php

namespace App\Classes\Publishers;

use App\Models\User;
use App\Models\PartnerIntegrationForeignDatabase;
use App\Models\NotificationChannelSubscription;
use App\Models\Manager\JenkinsBuild;
use App\Models\Explorer\Project;
use App\Models\Explorer\PublisherLog;
use App\Models\Explorer\ProjectColumnComment;
use App\Classes\Database\Connection;
use App\Classes\Postmark;
use Exception;
use DateTime;
use DateTimeZone;
use Illuminate\Database\QueryException;

class Run
{
    /**
     * @var int The build_number from Jenkins' ENV vars. 0 if not found (ran from command line)
     */
    public $build_number = 0;

    // Let me know if you think this won't work
    /**
     * @var mixed The publisher we are using
     */
    protected $current_publisher = NULL;

    /**
     * @var Project The project this publisher is for
     */
    protected $project = NULL;

    /**
     * @var array The data sent to our notifications
     */
    protected $notification_data = [];

    public function __construct()
    {
        // Get our Jenkins build number, if available. If ran manually via command line, build_number should be 0.
        $this->build_number = config('services.jenkins.build_id');

        if (! $this->build_number) {
            $this->build_number = 0;
        }
    }

    // gonna try to make schedule and one time publishers use same method
    /**
     * Runs the jenkins monitor to log this jenkins build
     *
     * @param  int  $build_id The id of the jenkins builds record
     * @return void
     */
    public function runMonitor($build_id, $publisher_id = 0)
    {
        $application_path = rtrim(config('app.path'), "/");
        exec("JENKINS_BUILD_ID=dontKillMe BUILD_ID=dontKillMe nohup php $application_path/artisan jenkins:monitor $build_id $publisher_id >/dev/null 2>&1 &");
    }

    /**
     * Trigger a build failure
     *
     * @param  int    $build_id                  The id of the jenkins builds record
     * @param  mixed  $message                   The error message and reason for failure
     * @param  object $data                      The data retrieved from the saved data/publishing schedule model
     * @param  bool   $override_publisher_notify If TRUE, override the publisher's notification settings
     * @return void
     */
    public function triggerBuildFailure(int $build_id, mixed $message, object $data, bool $override_publisher_notify = FALSE)
    {
        JenkinsBuild::find($build_id)->update(['message' => $message]);
        $this->createComment($data, "{$data->destination->name} publish for $data->schema_name.$data->table_name has failed");
        $this->createLog($data);
        $this->notifyRollbarOfFailure($build_id, $message, $data);

        $this->populateNotificationData($data);

        $this->notification_data['message'] = $message ?? NULL;

        NotificationChannelSubscription::deploy('publisher.failure', $this->notification_data);

        $this->notify(FALSE, $data, $message, $override_publisher_notify);

        throw new Exception($message);
    }

    /**
     * Creates a publisher log based on whats passed in
     *
     * @param  object $data The data retrieved from the saved data/publishing schedule model
     * @return void
     */
    public function createLog(object $data)
    {
        if ($data->create_log) {
            $user = User::handle($data->username);

            $log_data = [
                "project_id"                     => $data->project_id,
                "destination_id"                 => $data->destination->id,
                "publisher_id"                   => $data->publisher_id ?? NULL,
                "project_publishing_schedule_id" => $data->id,
                "jenkins_build_id"               => $data->jenkins_build_id,
                "user_id"                        => $user->id ?? NULL,
                "type"                           => $data->method,
                "publishing_started"             => $data->start_time->format(DateTime::ATOM),
                "publishing_finished"            => $this->getCurrentTime()->format(DateTime::ATOM)
            ];
            PublisherLog::create($log_data);
        }
    }

    public function createComment(object $data, string $message)
    {
        ProjectColumnComment::create([
            'project_id'   => $data->project_id,
            'user_id'      => $data->username,
            'table_name'   => $data->table_name,
            'schema_name'  => $data->schema_name,
            'column_name'  => '',
            'comment_text' => $message
        ]);
    }

    /**
     * Runs the publish/test methods when publishing
     *
     * @param  int    $build_id The id of the jenkins builds record
     * @param  object $data     The data retrieved from the saved data/publishing schedule model
     * @param  string $method   The method we are calling
     * @return void
     */
    public function run(int $build_id, object $data, string $method)
    {
        // This should only create a log when there is a schedule attached to the run
        // because this method handles both one time and scheduled publishes
        $data->create_log = empty($data->id) ? FALSE : TRUE;
        $class_name = empty($data->destination->class_name) ? "" : $data->destination->class_name;
        $data->start_time = $this->getCurrentTime();
        $data->method = $method;
        $data->jenkins_build_id = $build_id;

        if (empty($class_name)) {
            $this->triggerBuildFailure($build_id, "Publisher class name was empty", $data);
        }

        $this->project = Project::find($data->project_id);

        if (empty($this->project)) {
            throw new Exception("Project not found");
        }

        $this->notification_data = [
            'project_id'     => $data->project_id,
            'project'        => $this->project->name,
            'schema_name'    => $data->schema_name,
            'table_name'     => $data->table_name,
            'publisher_id'   => $data->id,
            'publisher_name' => $data->destination->name,
            'destination_id' => $data->destination->id,
            'start_time'     => $data->start_time->format(DateTime::ATOM),
            'end_time'       => NULL,
            'username'       => $data->username,
        ];

        // Only calls when the test method is being called or a one time publish to prevent duplicates
        if ($method == "test" || empty($data->id)) {
            $this->createComment($data, "{$data->destination->name} publish for {$data->schema_name}.{$data->table_name} has started");
        }

        try {
            $class = "App\\Classes\\Publishers\\$class_name";
            $this->current_publisher = new $class();
        } catch (Exception $e) {
            $this->triggerBuildFailure($build_id, "Publisher class $class_name was not available", $data);
        }

        if (! $this->isSafeToPublish($build_id, $data, $data->table_name, $data->schema_name)) {
            return;
        }

        $this->current_publisher->setParameters(
            $this->project,
            $data->username,
            $data->table_name,
            $data->schema_name,
            (object) $data->destination_options
        );

        try {
            if (method_exists($this->current_publisher, 'beforePublish')) {
                $this->current_publisher->beforePublish();
            }

            if ($method == 'test') {
                $this->createLog($data);

                return;
            }

            if (! method_exists($this->current_publisher, 'chunk')) {
                throw new Exception("The required chunk callback was not found in the {$class_name} publisher class.");
            }

            $this->current_publisher->publish();
        } catch (Exception $e) {
            logger()->error('Publishing SQL Error', [
                'project_id'  => $data->project_id,
                'table_name'  => $data->table_name,
                'schema_name' => $data->schema_name,
                'error'       => $e->getMessage()
            ]);

            // Replace SQL exception messagse in emails/notifications with a generic message.
            if ($e instanceof QueryException) {
                $this->current_publisher->error_message = 'An error occurred in the underlying query.';
            } else {
                $this->current_publisher->error_message = $e->getMessage();
            }

            $this->current_publisher->abortPublishing();
        }

        if (! $this->current_publisher->abort_publishing) {
            $this->createComment($data, "{$data->destination->name} publish for $data->schema_name.$data->table_name has finished successfully");

            $data->rows_published = $this->current_publisher->rows_published ?? 0;

            $this->notification_data['rows_published'] = $data->rows_published;
            $this->populateNotificationData($data);

            NotificationChannelSubscription::deploy('publisher.success', $this->notification_data);

            $this->notify(TRUE, $data, NULL);

            $this->createLog($data);
            if (method_exists($this->current_publisher, 'onSuccess')) {
                $this->current_publisher->onSuccess();
            }
        } else {
            if (method_exists($this->current_publisher, 'onError')) {
                $this->current_publisher->onError();
            }

            $this->triggerBuildFailure($build_id, $this->current_publisher->error_message, $data);

            exit(1);
        }
    }

    /**
     * Notify all admin and the user of the publisher failing, only user if publisher success
     *
     * @param  bool   $success                   Whether the publish succeeded or failed
     * @param  object $data                      The data object
     * @param  string $message                   Optional error message to send to the user
     * @param  bool   $override_publisher_notify Whether to override the publisher notify setting
     * @return void
     */
    public function notify(bool $success, object $data = NULL, string $message = NULL, $override_publisher_notify = FALSE)
    {
        // If the publisher explicitly doesn't notify users and we're not overriding that setting, return
        if (! $override_publisher_notify) {
            if (property_exists($this->current_publisher, 'notify_users') && $this->current_publisher->notify_users === FALSE) {
                return;
            }
        }

        $user = User::handle($data->username);

        $notificants = [];

        // Only send admin an email if we're dealing with a failure
        if (! $success) {
            $notificants = User::isAdmin()->get()
                ->filter(function ($admin) use ($user) {
                    return $admin->id != $user->id;
                })->toArray();
        }

        $notificants = array_merge($notificants, [$user]);

        foreach ($notificants as $user) {
            $user = (object) $user;

            // Send an in-app notification
            if ($success) {
                $subject = "Publishing {$data->table_name} Successful";
                $body = "Publishing {$data->table_name} in {$this->project->display_name} via {$data->destination->name} completed without issue.";
            } else {
                $subject = "Publishing {$data->table_name} Failed";
                $body = "While publishing {$data->table_name} in {$this->project->display_name} via {$data->destination->name}, an error was encountered.";
            }

            app('orchestration')->addNotification(
                $user->user_handle,
                app('environment')->getTeam(),
                $subject,
                $body,
                $success ? 'success' : 'danger'
            );

            $rows_published = NULL;

            if (! empty($data->rows_published)) {
                $rows_published = number_format($data->rows_published);
            }

            // Get the database name for the table, if it's foreign
            $database = PartnerIntegrationForeignDatabase::studio()
                ->where('control_id', $this->project->partner_integration_id)
                ->where('schema_name', $data->schema_name)
                ->first();
                
            $database = empty($database) ? $this->project->primary_database->database : $database->foreign_database->database;

            // Shoot out an email letin'em know
            Postmark::send(
                $user->email,
                $success ? 'publisher-succeeded' : 'publisher-failed',
                [
                    'project'        => $this->project->display_name,
                    'table'          => $data->table_name,
                    'user'           => $data->username,
                    'publisher_name' => $data->destination->name,
                    'name'           => $user->name,
                    'error_message'  => $message ?? 'Unavailable',
                    'team'           => app('environment')->getTeam(),
                    'rows_published' => $rows_published,
                    'database'       => $database
                ]
            );
        }
    }

    /**
     * Get a DateTime object w/our current time in UTC
     *
     * @return DateTime
     */
    private function getCurrentTime()
    {
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date;
    }

    /**
     * Populate relevant fields in the notification_data object
     *
     * @param object $data The data object for our publisher
     */
    public function populateNotificationData($data)
    {
        if (empty($this->current_publisher)) {
            return;
        }

        $this->notification_data['end_time'] = $this->getCurrentTime()->format(DateTime::ATOM);

        if (method_exists($this->current_publisher, 'getNotificationData')) {
            try {
                $this->notification_data['publisher_options'] = $this->current_publisher->getNotificationData($data);
            } catch (Exception $e) {
                $this->notification_data['publisher_options'] = NULL;
            }
        }
    }

    /*
     * Can the publisher be ran? Checks to see if any of its parent tables' most recent syncs have failed
     *
     * @param  mixed  $build_id   The jenkins build id
     * @param  mixed  $data       The data object
     * @param  string $table      The table name
     * @param  string $schema     The schema name
     * @return bool   True if the publisher can be ran, false otherwise
     */
    public function isSafeToPublish($build_id, $data, $table, $schema = 'public')
    {
        $parents = Connection::getAllObjectParents($this->project->primary_database, $schema, $table);
        $database_latest_syncs = $this->project->primary_database->jenkins_latest_syncs;

        $relevant_syncs = [];

        if ($schema == 'public') {
            $relevant_syncs = $database_latest_syncs->filter(function ($sync) use ($table) {
                return $sync->job_name == $table;
            });
        }

        foreach ($parents as $parent) {
            if ($parent->source_schema == 'public') { // We only care about public tables as they're the only ones that sync
                foreach ($database_latest_syncs as $sync) {
                    if ($sync->job_name == $parent->source_name) {
                        $relevant_syncs[] = $sync;
                    }
                }
            }
        }

        $errored_syncs = collect($relevant_syncs)->filter(function ($sync) {
            return $sync->result == 'FAILURE';
        });

        if (count($errored_syncs) > 0) {
            $message = "One or more database table syncs have failed, so publishing has been halted to maintain data integrity. Failed table syncs: ";
            $message .= implode(', ', array_column($errored_syncs, 'job_name'));
            $this->triggerBuildFailure($build_id, $message, $data, TRUE);

            return FALSE;
        }

        return TRUE;
    }

    /**
     * Alert Rollbar of a failed publish. Provides data used for the publish and also sync status for its partner integration.
     *
     * @param int|null $build_id Jenkins build ID (null if ran via terminal)
     * @param string   $message  Message to be written -- likely the exception message
     * @param object   $data     Includes query object and destination options
     */
    private function notifyRollbarOfFailure($build_id, $message, $data): void
    {
        $sync_data = $this->project->primary_database->jenkins_latest_syncs();

        logger()->error('Sync failure for build ' . $build_id, compact('build_id', 'message', 'data', 'sync_data'));
    }
}
