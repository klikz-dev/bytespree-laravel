<?php

namespace App\Classes\Publishers;

use App\Models\User;
use App\Models\Explorer\Project;
use App\Classes\Database\StudioExplorer;
use App\Classes\Postmark;
use Exception;
use DB;

class Publisher
{
    protected $project;
    protected $project_id;
    protected $source_schema;
    protected $source_table;
    protected $username;

    public $destination_options;
    public $abort_publishing = FALSE;
    public $error_message = NULL;
    public $chunk_type = 'record'; 
    public $publisher_name = NULL;

    /**
     * Publish the table to our destination
     *
     * @return void
     */
    public function publish()
    {
        if (! isset($this->destination_options)) {
            throw new Exception('Destination options were not defined');
        }

        if (empty($this->destination_options->query) && $this->chunk_type != 'action') {
            throw new Exception('Query definition was not found.');
        } else if ($this->chunk_type != 'action') {
            $input = (object) $this->destination_options->query;

            $schema = isset($input->schema) ? $input->schema : '';
            $prefix = isset($input->prefix) ? $input->prefix : '';
            $table = isset($input->table) ? $input->table : '';
            $filters = isset($input->filters) ? $input->filters : [];
            $joins = isset($input->joins) ? $input->joins : [];
            $order = isset($input->order) ? $input->order : NULL;
            $limit = isset($input->limit) ? $input->limit : 0;
            $unions = isset($input->unions) ? $input->unions : [];
            $union_all = isset($input->union_all) ? filter_var($input->union_all, FILTER_VALIDATE_BOOL) : FALSE;

            $transformations = [];
            $columns = [];
            $is_grouped = FALSE;

            if (isset($input->transformations)) {
                $transformations = json_decode(json_encode($input->transformations), TRUE);
            }

            if (isset($input->columns)) {
                $columns = json_decode(json_encode($input->columns), TRUE);
            }

            if (isset($input->is_grouped)) {
                $is_grouped = filter_var($input->is_grouped, FILTER_VALIDATE_BOOLEAN);
            }

            if (method_exists($this, 'retrievedColumns')) {
                $this->retrievedColumns($columns);
            }

            $count = 0;

            $query = app(StudioExplorer::class)->getRecordsQuery(
                $this->project,
                $prefix,
                $table,
                $schema,
                $filters,
                $joins,
                $order,
                $columns,
                $is_grouped,
                $transformations,
                $unions,
                $union_all
            );
        }

        if ($this->chunk_type == 'record') {
            $cursor_name = uniqid('publisher_cursor_');
            if (is_string($query)) {
                $sql = $query;
            } else {
                $sql = $query->toSql();
            }
            $connection = app(StudioExplorer::class)->connect($this->project->primary_database);

            $cursor_sql = <<<SQL
                DECLARE $cursor_name CURSOR FOR $sql
                SQL;

            $connection->statement("BEGIN");
            $connection->statement($cursor_sql);

            while ($this->abort_publishing === FALSE) {
                $results = $connection->select("FETCH 1000 FROM $cursor_name");
    
                if (empty($results)) {
                    break;
                }
    
                $this->chunk($results);
    
                $count += count($results);
            }
        } else if ($this->chunk_type == 'sql') {
            $this->chunk($query->toSql());
        } else if ($this->chunk_type == 'action') {
            $this->chunk();
        }
    }

    /**
     * Set the parameters for our publishing run
     *
     * @param  Project $project             The id of the project
     * @param  string  $username            The username of the publisher
     * @param  string  $table_name          The table/view we are publishing
     * @param  string  $schema_name         The name of the schema for the table/view
     * @param  object  $destination_options The options used for publishing
     * @return void
     */
    public function setParameters(Project $project, string $username, string $source_table, string $source_schema, object $destination_options)
    {
        $this->project = $project;
        $this->username = $username;
        $this->source_table = $source_table;
        $this->source_schema = $source_schema;
        $this->destination_options = $destination_options;
    }

    /**
     * Notify relevant users of the publishing job. Supports string replacements for:
     *  - {project} - name of the project
     *  - {source}  - source table/view name
     *
     * @param  bool|false  $success (OPTIONAL) Whether or not to consider this a success or a failure
     * @param  string|null $subject (OPTIONAL) Subject line of the notification
     * @param  string|null $message (OPTIONAL) Body of the notification
     * @return void
     * @todo   Add the project administrators to the recipients of the notification
     * @todo   Add the destination to the default messages, e.g. "Your publishing of ___ to {DESTINATION} was successful."
     */
    public function notifyUsers($success = FALSE, $subject = NULL, $message = NULL)
    {
        if ($subject === NULL) {
            if ($success) {
                $subject = 'Publishing Successful';
            } else {
                $subject = 'Publishing Failed';
            }
        }

        if ($message === NULL) {
            if ($success) {
                $message = "The {publisher} publishing of {project} -> {source} was successful.";
            } else {
                $message = "The {publisher} publishing of {project} -> {source} failed.";
            }
        }

        $replacements = [
            '{project}'   => $this->project->display_name,
            '{source}'    => $this->source_table,
            '{publisher}' => $this->publisher_name
        ];

        foreach ($replacements as $replace => $with) {
            $message = str_replace($replace, $with, $message);
            $subject = str_replace($replace, $with, $subject);
        }

        app('orchestration')->addNotification(
            $this->username,
            app('environment')->getTeam(),
            $subject,
            $message,
            $success ? 'success' : 'danger'
        );
    }

    /**
     * Generic notify users of success or failure
     *
     * @param  array  $notified_users Array representing users to be notified (all team admin notified if $success is FALSE)
     * @param  string $subject        The subject line being sent
     * @param  string $description    Descriptive text for the email
     * @param  string $body           Body of the message, e.g. the error message or success message
     * @param  bool   $success        TRUE if publishing was successful; FALSE if not
     * @return void
     */
    public function notifyUsersByEmail(array $notified_users, string $subject, string $description, string $body, bool $success = FALSE)
    {
        $emails = [];

        $team_users = User::get();
        $notified_handles = array_column($notified_users, 'user_handle');

        // Extract our user emails
        foreach ($team_users as $user) {
            if (in_array($user->user_handle, $notified_handles)) {
                $emails[] = $user->email;
            }
        }

        // Include the team admin if $success is FALSE
        if (! $success) {
            foreach ($team_users as $user) {
                if ($user->is_admin == TRUE && ! in_array($user->email, $emails)) {
                    $emails[] = $user->email;
                }
            }
        }

        Postmark::send($emails, "generic-error", compact('subject', 'description', 'body'));
    }

    /**
     * Called by our extending class when it encounters a fatal error. Useful for triggering a db rollback or file deletion.
     * Will ultimately trigger the onError() method of the extending class.
     *
     * @return void
     */
    public function abortPublishing()
    {
        $this->abort_publishing = TRUE;
    }
}
