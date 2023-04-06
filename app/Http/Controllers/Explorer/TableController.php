<?php

namespace App\Http\Controllers\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\Database\Table;
use App\Models\Explorer\Project;
use App\Models\SavedData;
use App\Models\UserActivityLog;
use App\Models\Explorer\ProjectColumnAttachment;
use App\Models\Explorer\ProjectSavedQuery;
use App\Models\Explorer\ProjectPublishingSchedule;
use Illuminate\Support\Facades\Storage;
use App\Attributes\Can;

class TableController extends Controller
{
    #[Can(permission: 'studio_access', product: 'studio', id: 'project.id')]
    public function show(Request $request, Project $project, string $schema, string $table)
    {
        if (empty($schema) || empty($table)) {
            return redirect("/studio/projects/{$project->id}");
        }

        if ($request->has('column')) {
            $sent_column = urldecode($request->column);
        } else {
            $sent_column = '';
        }

        if ($request->has('saved_query_id')) {
            $saved_query = ProjectSavedQuery::find($request->saved_query_id)->toArray();
        } else {
            $saved_query = ["id" => "-1"];
        }

        if ($request->has("publisher_id")) {
            $publisher = ProjectPublishingSchedule::find($request->publisher_id)->toArray();
        } else {
            $publisher = ["id" => "-1"];
        }

        UserActivityLog::create([
            "user_handle" => $request->user()->user_handle,
            "project_id"  => $project->id,
            "schema_name" => $schema,
            "table_name"  => $table
        ]);

        $this->setCrumbs(
            'studio',
            [
                [
                    "title"    => $project->name,
                    "location" => "/Project/index/" . $project->id
                ],
                [
                    "title"    => $table,
                    "location" => "/studio/projects/{$project->id}/tables/{$schema}/{$table}"
                ]
            ]
        );

        $completed = FALSE; // $this->isComplete($project_id);

        $flashError = session()->get('flash_error_message');

        $vars = [
            "control_id"            => $project->id,
            "project_name"          => $project->name,
            "schema"                => $schema,
            "sent_column"           => str_replace('"', '\"', $sent_column),
            "destination_schema_id" => $project->destination_schema_id,
            "table"                 => $table,
            "flashError"            => $flashError,
            "completed"             => $completed,
            "max_size"              => ini_get('upload_max_filesize'),
            "saved_query"           => json_encode($saved_query),
            "publisher"             => json_encode($publisher),
            "table_exists"          => Table::exists($project->primary_database, $schema, $table),
            "file_upload_url"       => config('services.file_upload.url'),
        ];

        return view("tables", $vars);
    }

    public function attachment(Request $request, Project $project, string $schema, string $table, ProjectColumnAttachment $attachment)
    {
        if (! Storage::disk('attachments')->exists($attachment->file_name)) {
            abort(404);
        }

        return Storage::disk('attachments')->download($attachment->file_name);
    }

    #[Can(permission: 'studio_access', product: 'studio', id: 'project.id')]
    public function mssql(Request $request, Project $project, string $schema, string $table, SavedData $saved_data)
    {
        if (! empty($saved_data->data->id) && $saved_data->data->id != -1) {
            $url = sprintf('/studio/projects/%s/tables/%s/%s?publisher_id=%s', $project->id, $schema, $table, $saved_data->data->id);
        } else {
            $url = sprintf('/studio/projects/%s/tables/%s/%s', $project->id, $schema, $table);
        }

        return view('mssql_map', [
            'project_id'   => $project->id,
            'guid'         => $saved_data->guid,
            'schema'       => $schema,
            'table'        => $table,
            'callback_url' => $url
        ]);
    }
}
