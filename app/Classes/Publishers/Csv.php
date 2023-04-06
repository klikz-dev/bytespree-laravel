<?php

namespace App\Classes\Publishers;

use App\Models\User;
use App\Models\Explorer\ProjectAttachment;
use App\Models\Explorer\ProjectHyperlink;
use App\Classes\Postmark;
use Exception;

class Csv extends Publisher
{
    public $columns = [];
    public $options = NULL;
    public $publisher_name = 'CSV'; // What's shown in alerts/notifications
    public $use_hooks = TRUE; // Make sure when publishing, the caller knows we're utilizing callbacks.
    public $rows_published = 0;
    public $notify_users = FALSE; // Should BP_Publish notify users of success or failure?

    private $hyperlink;
    private $file_path;
    private $file_pointer;
    private $table = '';

    /**
     * Callback that is called when the columns from our publish job are available to us
     *
     * @param  array $columns An array of columns that are returned by the query
     * @return void
     */
    public function retrievedColumns(array $columns)
    {
        fputcsv($this->file_pointer, $this->filterUsedColumns($columns));
    }

    /**
     * Callback for when we retrieve a chunk of data from our outer SQL cursor
     *
     * @param  array $data A multidimensional array of SQL returned data
     * @return void
     */
    public function chunk(array $data)
    {
        $limit = empty($this->destination_options->limit) ? NULL : (int) $this->destination_options->limit;

        foreach ($data as $row) {
            if (empty($limit) || $this->rows_published < $limit) {
                ++$this->rows_published;
                fputcsv($this->file_pointer, (array) $row);
            } else {
                break;
            }
        }
    }

    /**
     * Callback to be executed before our publish job runs. Set our out file path and create our file pointer for writing to it.
     *
     * @return void
     */
    public function beforePublish()
    {
        $this->rows_published = 0;

        $this->table = $this->destination_options->query['table'];

        $this->file_path = $this->getFilePath();

        // If we're not ensuring unique filenames with timestamps, delete the existing attachment & hyperlink that would conflict with this new one.
        if (! $this->destination_options->append_timestamp) {
            $former_attachment = ProjectAttachment::where('project_id', $this->project->id)
                ->where('path', $this->file_path)
                ->first();

            if (! empty($former_attachment)) {
                $former_attachment->delete();

                $former_hyperlink = ProjectHyperlink::where('project_id', $this->project->id)
                    ->where('name', basename($this->file_path))
                    ->where('description', 'Table Export')
                    ->first();

                if (! empty($former_hyperlink)) {
                    $former_hyperlink->delete();
                }
            }
        }

        $this->file_pointer = fopen($this->file_path, 'w');

        if (! $this->file_pointer) {
            throw new Exception('The CSV file could not be created.');
        }
    }

    /**
     * After publish callback
     *
     * @return void
     */
    public function onSuccess()
    {
        fclose($this->file_pointer);

        $attachment = ProjectAttachment::create([
            "project_id" => $this->project->id,
            "user_id"    => $this->username,
            "path"       => $this->file_path,
            "file_name"  => basename($this->file_path)
        ]);

        $this->hyperlink = rtrim(config('app.url'), '/') . "/studio/projects/{$this->project->id}/attachments/{$attachment->id}";

        ProjectHyperlink::create([
            "project_id"  => $this->project->id,
            "user_id"     => $this->username,
            "url"         => $this->hyperlink,
            "name"        => $attachment->file_name,
            "description" => 'Table Export',
            "type"        => 'file'
        ]);

        // Two layers of filters because the template needs it this way
        $filters = $this->cleanFilters($this->destination_options->query['filters'] ?? []);

        if (! empty($filters)) {
            $filters = compact('filters');
        }

        $data = [
            "filters"         => $filters,
            "hyper_link_text" => $this->hyperlink,
            "message"         => $this->destination_options->message,
            "table"           => $this->source_table,
            "schema"          => $this->source_schema
        ];

        $notified_handles = array_column($this->destination_options->users, 'user_handle');
        $emails = User::whereIn('user_handle', $notified_handles)->pluck('email')->toArray();

        Postmark::send($emails, "publish-success", $data);
    }

    /**
     * Our error callback
     *
     * @return void
     */
    public function onError()
    {
        fclose($this->file_pointer);

        $this->notifyUsersByEmail(
            $this->destination_options->users,
            "Publishing {$this->source_schema}.{$this->source_table} to CSV Failed",
            "Publishing of {$this->source_schema}.{$this->source_table} to CSV has failed in the {$this->project['name']} project.",
            $this->error_message,
            FALSE
        );
    }

    /**
     * Filter out unused columns and only return the used columns' titles (names/aliases)
     *
     * @param  array $columns A multidimensional array of columns
     * @return array The filtered columns' titles
     */
    protected function filterUsedColumns(array $columns)
    {
        $used_columns = array_filter($columns, function ($column) {
            return filter_var($column["checked"], FILTER_VALIDATE_BOOLEAN) === TRUE;
        });

        return array_map(function ($column) {
            return empty($column['alias']) ? $column['target_column_name'] : $column['alias'];
        }, $used_columns);
    }

    /**
     * Get our output file's path
     *
     * @return string Absolute path of the file we should write to
     */
    protected function getFilePath()
    {
        $filename = "db_{$this->table}.csv";

        if ($this->destination_options->append_timestamp) {
            $filename = date('Ymdhis') . "_" . $filename;
        }

        return rtrim(config('app.attach_directory'), '/') . "/" . $filename;
    }

    /**
     * Clean our filters into a usable array
     *
     * @param  array $incoming_filters An array of query filters to be cleaned. Ported from BP_SchemaExport
     * @return array Cleaned filters
     */
    protected function cleanFilters($incoming_filters)
    {
        if (! is_array($incoming_filters) || empty($incoming_filters)) {
            return [];
        }

        $filters = [];

        foreach ($incoming_filters as $key => $filter) {
            $filter = (object) $filter;
            if (is_object($filter->value)) {
                if (isset($filter->value->info)) {
                    if ($filter->value->type == "column") {
                        $filter->value = "(SELECT " . $filter->value->info->column . " FROM " . $filter->value->info->table . ")";
                    } elseif ($filter->value->type == "interval") {
                        if (is_object($filter->value->info)) {
                            $filter->value->info->low_val = json_decode($filter->value->info->low_val);
                            $filter->value->info->high_val = json_decode($filter->value->info->high_val);
                            $filter->value = "now() " . $filter->value->info->low_val->direction . " (" . $filter->value->info->low_val->time . " " . $filter->value->info->low_val->type . ") and now() " . $filter->value->info->high_val->direction . " (" . $filter->value->info->high_val->time . " " . $filter->value->info->high_val->type . ")";
                        } else {
                            $filter->value->info = json_decode($filter->value->info);
                            $filter->value = "now() " . $filter->value->info->direction . " (" . $filter->value->info->time . " " . $filter->value->info->type . ")";
                        }
                    } elseif ($filter->value->type == "manual") {
                        $filter->value = $filter->value->info->low_val . " and " . $filter->value->info->high_val;
                    } else {
                        $filter->value = json_encode($filter->value->info);
                    }
                } else {
                    $filter->value = json_encode($filter->value);
                }

                $filters[$key] = $filter;
            } else {
                $filters[$key] = $filter;
            }
        }

        return $filters;
    }
}