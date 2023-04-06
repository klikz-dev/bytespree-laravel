<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectSavedQuery;
use App\Attributes\Can;

class SavedQueryController extends Controller
{
    #[Can(permission: 'table_read', product: 'studio', id: 'project.id')]
    public function list(Request $request, Project $project)
    {
        return response()->success($project->saved_queries);
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project, ProjectSavedQuery $saved_query)
    {
        $saved_query->delete();

        return response()->empty();
    }

    #[Can(permission: 'table_write', product: 'studio', id: 'project.id')]
    public function store(Request $request, Project $project, string $schema, string $table)
    {
        $request->validateWithErrors(['name' => 'required', 'query' => 'required', 'description' => 'required']);

        if (! preg_match("/^[a-z][a-z0-9_]*$/", $request->name)) {
            return response()->error("Unable to create saved query. Name must contain only letters, numbers, and underscores and must start with a letter.");
        }
        
        if (ProjectSavedQuery::where(['project_id' => $project->id, 'name' => $request->name])->exists()) {
            return response()->error("Unable to create saved query. Name must be unique.");
        }

        ProjectSavedQuery::create([
            'project_id'    => $project->id,
            'user_id'       => $request->user()->user_handle,
            'name'          => $request->name,
            'description'   => $request->description,
            'query'         => $request->input('query'),
            'source_table'  => $table,
            'source_schema' => $schema
        ]);

        return response()->success(message: 'Saved query has been created');
    }

    #[Can(permission: 'table_write', product: 'studio', id: 'project.id')]
    public function update(Request $request, Project $project, string $schema, string $table, ProjectSavedQuery $saved_query)
    {
        $request->validateWithErrors(['name' => 'required', 'query' => 'required', 'description' => 'required']);

        if (! preg_match("/^[a-z][a-z0-9_]*$/", $request->name)) {
            return response()->error("Unable to create saved query. Name must contain only letters, numbers, and underscores and must start with a letter.");
        }
        
        if (ProjectSavedQuery::where(['project_id' => $project->id, 'name' => $request->name])->where('id', '!=', $saved_query->id)->exists()) {
            return response()->error("Unable to create saved query. Name must be unique.");
        }

        $saved_query->update([
            'name'        => $request->name,
            'description' => $request->description,
            'query'       => $request->input('query'),
        ]);
        
        return response()->success(message: 'Saved query has been updated');
    }
}
