<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectTableNote;
use App\Models\Explorer\ProjectTableNoteHistory;
use App\Attributes\Can;
use DateTime;

class TableNoteController extends Controller
{
    #[Can(permission: 'comment_read', product: 'studio', id: 'project.id')]
    public function list(Request $request, Project $project, string $schema, string $table)
    {
        $notes = $project->notes
            ->where('schema', $schema)
            ->where('table', $table)
            ->map(function ($note) {
                $note->profile_picture = app('environment')->getGravatar($note->user->email ?? "");
    
                return $note;
            });

        return response()->success($notes);
    }

    #[Can(permission: 'comment_write', product: 'studio', id: 'project.id')]
    public function store(Request $request, Project $project, string $schema, string $table)
    {
        $request->validateWithErrors([
            'note' => 'required'
        ]);

        $note = ProjectTableNote::create([
            "project_id" => $project->id,
            "schema"     => $schema,
            "table"      => $table,
            "note"       => $request->note,
            "user_id"    => auth()->user()->user_handle
        ]);
        
        $note->note_id = $note->id;
        $note->action = 'added';
        unset($note->id);
        ProjectTableNoteHistory::create($note->toArray());

        return response()->success([], 'Table note added.');
    }

    #[Can(permission: 'comment_write', product: 'studio', id: 'project.id')]
    public function update(Request $request, Project $project, string $schema, string $table, ProjectTableNote $note)
    {
        $request->validateWithErrors([
            'note' => 'required'
        ]);

        $note->update([
            "note"    => $request->note,
            "user_id" => auth()->user()->user_handle
        ]);
        
        $note->note_id = $note->id;
        $note->action = 'modified';
        unset($note->id);
        ProjectTableNoteHistory::create($note->toArray());

        return response()->success([], 'Table note updated.');
    }

    #[Can(permission: 'comment_write', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project, string $schema, string $table, ProjectTableNote $note)
    {
        $note->delete();
        $note->note_id = $note->id;
        $note->action = 'deleted';
        unset($note->id);
        ProjectTableNoteHistory::create($note->toArray());

        return response()->success([], 'Table note deleted.');
    }
}
