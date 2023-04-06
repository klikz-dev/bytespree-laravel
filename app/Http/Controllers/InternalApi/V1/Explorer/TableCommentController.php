<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\Postmark;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectColumnComment;
use App\Models\User;
use Auth;
use App\Attributes\Can;

class TableCommentController extends Controller
{
    #[Can(permission: 'comment_read', product: 'studio', id: 'project.id')]
    public function list(Request $request, Project $project, string $schema, string $table)
    {
        $comments = ProjectColumnComment::where('project_id', $project->id)
            ->where('schema_name', $schema)
            ->where('table_name', $table)
            ->get();

        $formatted_comments = [];

        foreach ($comments as $comment) {
            $formatted_comments[$table . '_' . $comment->column_name][] = $comment;
        }

        return response()->success($formatted_comments);
    }

    #[Can(permission: 'comment_write', product: 'studio', id: 'project.id')]
    public function store(Request $request, Project $project, string $schema, string $table)
    {
        $override = filter_var($request->override, FILTER_VALIDATE_BOOLEAN);
     
        $request->validateWithErrors([
            'column'       => 'required|string',
            'comment_text' => 'required|string',
        ]);

        $users_mentioned = User::parseUsersMentioned($request->comment_text);
        $invalid_handles = [];
        foreach ($users_mentioned as $handle => $user) {
            if (! $user) {
                $invalid_handles[] = $handle;
            }
        }

        if (! $override && count($invalid_handles) > 0) {
            $word = (count($invalid_handles) == 1) ? 'handle' : 'handles';

            return response()->error("Invalid user {$word} found", compact('users_mentioned', 'invalid_handles'));
        }

        $comment = ProjectColumnComment::create([
            'project_id'   => $project->id,
            'schema_name'  => $schema,
            'table_name'   => $table,
            'column_name'  => $request->column,
            'comment_text' => $request->comment_text,
            'user_id'      => $request->user()->user_handle,
        ]);

        if ($project->is_complete) {
            $check = $project->sendCompletedEmail("Comment", "added to", $table, $request->column, Auth::user()->name, $request->comment_text);
        }

        foreach ($users_mentioned as $handle => $user) {
            if ($user) {
                if (empty($user->email)) {
                    $users_mentioned[$handle]->valid = FALSE;
                    $users_mentioned[$handle]->sent = FALSE;
                } else {
                    $data = [
                        "receiving_full_name" => $user->name,
                        "sending_full_name"   => Auth::user()->name,
                        "table"               => $table,
                        "column"              => $request->column,
                        "comment"             => $request->comment_text,
                        "project"             => $project->display_name,
                        "type"                => 'comment'
                    ];

                    $users_mentioned[$handle]->valid = TRUE;
                    $users_mentioned[$handle]->sent = Postmark::send($user->email, "comment-mentions", $data);
                }
            }
        }

        $send_failures = [];
        foreach ($users_mentioned as $handle => $user) {
            if ($user && ! $user->sent && ! $override) {
                $send_failures[] = $user->name;
            }
        }

        if (count($send_failures) > 0) {
            $phrase = (count($send_failures) == 1) ? 'a mentioned user was' : 'some mentioned users were';

            return response()->error("Comment added, but {$phrase} not contacted", compact('users_mentioned', 'send_failures'));
        }

        return response()->success([], 'Comment added');
    }

    #[Can(permission: 'comment_write', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project, string $schema, string $table, ProjectColumnComment $comment)
    {
    }
}
