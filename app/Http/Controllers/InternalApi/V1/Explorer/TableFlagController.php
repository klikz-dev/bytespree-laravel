<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\Postmark;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectColumnFlag;
use App\Models\User;
use Auth;
use App\Attributes\Can;
use App\Models\Explorer\ProjectColumnComment;

class TableFlagController extends Controller
{
    #[Can(permission: 'flag_read', product: 'studio', id: 'project.id')]
    public function list(Request $request, Project $project, string $schema, string $table)
    {
        return response()->success(
            ProjectColumnFlag::where('project_id', $project->id)
                ->where('schema_name', $schema)
                ->where('table_name', $table)
                ->get()
                ->mapWithKeys(function ($flag) use ($table) {
                    return [$table . '_' . $flag->column_name => $flag];
                })
        );
    }

    #[Can(permission: 'flag_write', product: 'studio', id: 'project.id')]
    public function store(Request $request, Project $project, string $schema, string $table)
    {
        $request->validateWithErrors([
            'column' => 'required|string',
            'schema' => 'required|string',
            'table'  => 'required|string',
            'user'   => 'required|string',
        ]);

        // Verify that user is not emailing themselves after setting a flag
        if ($request->user . 'test' != Auth::user()->user_handle) {
            $user = User::where('user_handle', $request->user)->first();
            if ($user) {
                $this->sendEmailForAssignedFlags($project, $table, $request->column, $user, $request->comment_text);
            }
        }

        ProjectColumnFlag::create([
            'project_id'    => $project->id,
            'schema_name'   => $schema,
            'table_name'    => $table,
            'column_name'   => $request->column,
            'assigned_user' => $request->user,
            'flag_reason'   => $request->comment_text,
            'user_id'       => Auth::user()->user_handle,
        ]);

        if ($project->is_complete) {
            $project->sendCompletedEmail("Flag", "added to", $request->table, $request->column, Auth::user()->name, $request->comment_text);
        }

        return response()->success([], 'Flag added');
    }

    #[Can(permission: 'flag_write', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project, string $schema, string $table, ProjectColumnFlag $flag)
    {
        $flag->delete();

        ProjectColumnComment::create([
            'project_id'   => $project->id,
            'user_id'      => Auth::user()->user_handle,
            'table_name'   => $table,
            'schema_name'  => $schema,
            'column_name'  => $request->column,
            'comment_text' => 'Column flag removed.'
        ]);

        if ($project->is_complete) {
            $project->sendCompletedEmail("Flag", "removed", $request->table, $request->column, Auth::user()->name);
        }

        return response()->empty();
    }

    public function destroyAllForColumn(Request $request, Project $project, string $schema, string $table)
    {
        ProjectColumnFlag::where('project_id', $project->id)
            ->where('schema_name', $schema)
            ->where('table_name', $table)
            ->where('column_name', $request->column)
            ->delete();

        ProjectColumnComment::create([
            'project_id'   => $project->id,
            'user_id'      => Auth::user()->user_handle,
            'table_name'   => $table,
            'schema_name'  => $schema,
            'column_name'  => $request->column,
            'comment_text' => 'Column flag removed.'
        ]);

        if ($project->is_complete) {
            $project->sendCompletedEmail("Flag", "removed", $request->table, $request->column, Auth::user()->name);
        }

        return response()->empty();
    }

    /**
     * Send an email to the user when a flag is assigned to them
     *
     * @param  Project $project     The project
     * @param  string  $table_name  Table the flag is on
     * @param  string  $column_name Column the flag is on
     * @param  User    $user        The user assigned to the flag
     * @param  string  $comment     The comment for the flag
     * @return void
     */
    public function sendEmailForAssignedFlags(Project $project, string $table_name, string $column_name, User $user, string $comment = NULL)
    {
        if (! $project->primary_database) {
            return;
        }

        $user_name = $user->first_name . " " . $user->last_name;

        $data = [
            "project"   => $project->display_name,
            "database"  => $project->primary_database->database,
            "table"     => $table_name,
            "column"    => $column_name,
            "user"      => Auth::user()->name,
            "user_name" => $user_name,
            "comment"   => $comment
        ];

        Postmark::send($user->email, "flag-notification", $data);
    }
}
