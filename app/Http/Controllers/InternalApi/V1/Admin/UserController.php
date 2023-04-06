<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserRole;

class UserController extends Controller
{
    public function index()
    {
        return response()->success(
            [
                'users'         => User::get(),
                'deleted_users' => User::onlyTrashed()->get()
            ],
            'here are the users'
        );
    }

    public function permissions(User $user)
    {
        $user_permissions = $user->getAllUserPermissions();

        $perm_names = $user_permissions['name'];

        $permissions = [];

        $user_permissions = Permission::where('type', 'user')->get();

        foreach ($user_permissions as $permission) {
            if (is_array($perm_names)) {
                if (array_search($permission->name, $perm_names) !== FALSE) {
                    $permission->value = TRUE;
                } else {
                    $permission->value = FALSE;
                }
            } else {
                $permission->value = FALSE;
            }
            $description = str_replace('datalake', 'data lake', $permission->name);
            $permission->description = ucwords(str_replace('_', ' ', $description));
            $permissions[] = $permission;
        }

        return response()->success($permissions, '');
    }

    public function updatePermissions(Request $request, User $user)
    {
        $is_admin = filter_var($request->is_admin, FILTER_VALIDATE_BOOLEAN);
        $projects = $request->projects;
        $permissions_changed = filter_var($request->permissions_changed, FILTER_VALIDATE_BOOLEAN);
        $permissions = $request->permissions;
        $redirect_when_finished = FALSE;

        // Are we trying to remove admin access for ourself?
        if ($user->id == Auth::user()->id) {
            if ($user->is_admin && $is_admin === FALSE) {
                // Are there other admins? If not, reject the action...
                if (User::where('is_admin', TRUE)->count() < 2) {
                    return response()->error('You should designate another administrator before removing your own privileges.', [], 400);
                }
                $redirect_when_finished = TRUE;
            }
        }

        $user->setAdmin($is_admin);
        
        if (! $is_admin) {
            UserRole::updateUserRoles($user->id, $projects);

            if ($permissions_changed) {
                $user->clearPermissions();
                foreach ($permissions as $permission) {
                    if ($permission['value'] === TRUE) {
                        $user->addPermission($permission['id']);
                    }
                }
            }
        }

        if ($redirect_when_finished) {
            return $this->_sendAjax('ok', '', ['redirect' => TRUE, 'redirect_location' => '/Warehouse']);
        }

        return response()->success([], 'User successfully updated');
    }

    public function projects(User $user)
    {
        return response()->success($user->projects(), 'here are the projects');
    }

    public function destroy(User $user)
    {
        if ($user->is_pending) {
            $result = app('orchestration')->removeInvitedUser($user->id, app('environment')->getTeam());
            
            if (! is_array($result) || ! array_key_exists('status', $result) || $result['status'] !== 'ok') {
                return response()->error('Failed to rescind invitation.');
            }
            
            $user->forceDelete();
        } else {
            if ($user->id == Auth::user()->id && Auth::user()->is_admin) {
                // Are there other admins? If not, reject the action...
                if (User::where('is_admin', TRUE)->count() < 2) {
                    return response()->error('You should designate another administrator before removing yourself.', [], 400);
                }
            }
            
            $user->delete();
        }

        return response()->success([], 'User deleted');
    }

    public function invite(Request $request)
    {
        $type = $request->type == 'email' ? 'email' : NULL;

        foreach ($request->invites as $identifier) {
            $user = User::create([
                'user_handle' => $identifier,
                'email'       => $identifier,
                'is_pending'  => TRUE,
                'is_admin'    => FALSE,
            ]);

            $invites[] = ['id' => $user->id, 'invite' => $identifier];
        }

        $response = app('orchestration')->sendInvitation($invites, Auth::user()->user_handle, $type);

        if (! is_array($response)) {
            return response()->error('There was an issue sending the invites.');
        }

        foreach ($response["data"] as $data) {
            if ($data["status"] == 'error' && ! empty($data["id"])) {
                User::where('id', $data['id'])->forceDelete();
            }
        }

        if ($response["status"] == 'ok') {
            return response()->success($response["data"], 'Invites sent');
        }

        return response()->error('An error occurred.', $response['data']);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'send_database_job_failure_email' => 'boolean',
        ]);

        $user->update($validated);

        return response()->success($user, 'User updated.');
    }
}
