<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Explorer\Project;
use App\Attributes\Can;

class UserController extends Controller
{
    public function list(Request $request, Project $project)
    {
        return response()->success(User::getDashboardData('studio', $project->id));
    }

    #[Can(permission: 'project_grant', product: 'studio', id: 'project.id')]
    public function manage(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'user_roles' => 'required'
        ]);

        foreach ($request->user_roles as $user_role) {
            $user_role = (object) $user_role;
            if ($user_role->action == 'delete') {
                UserRole::where('product_child_id', $project->id)
                    ->where('role_id', $user_role->orig_role_id)
                    ->where('user_id', $user_role->user_id)
                    ->delete();
            } else {
                UserRole::updateOrCreate(
                    [
                        'product_child_id' => $project->id,
                        'user_id'          => $user_role->user_id,
                        'role_id'          => $user_role->orig_role_id
                    ],
                    [
                        'role_id' => $user_role->role_id
                    ]
                );
            }
        }

        return response()->success();
    }
}
