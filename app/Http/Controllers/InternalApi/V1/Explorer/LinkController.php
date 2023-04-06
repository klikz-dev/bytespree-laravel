<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectHyperlink;
use App\Attributes\Can;

class LinkController extends Controller
{
    #[Can(permission: 'link_read', product: 'studio', id: 'project.id')]
    public function list(Request $request, Project $project)
    {
        return response()->success($project->links);
    }

    #[Can(permission: 'link_write', product: 'studio', id: 'project.id')]
    public function store(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'url'         => 'required',
            'name'        => 'required',
            'description' => 'required',
            'type'        => 'required'
        ]);

        ProjectHyperlink::create([
            "project_id"  => $project->id,
            "user_id"     => auth()->user()->user_handle,
            "url"         => $request->url,
            "name"        => $request->name,
            "description" => $request->description,
            "type"        => $request->type
        ]);

        return response()->success();
    }

    #[Can(permission: 'link_write', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project, ProjectHyperlink $link)
    {
        $link->delete();

        return response()->empty();
    }
}
