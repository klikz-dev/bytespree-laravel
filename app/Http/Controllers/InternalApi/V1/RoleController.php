<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\UserRole;

class RoleController extends Controller
{
    public function list(Request $request)
    {
        $roles_query = Role::with('product');

        if ($request->has('product_id')) {
            $roles_query->where('product_id', $request->product_id);
        }

        $roles_query->where('role_name', '!=', 'Team Admin');

        return response()->success($roles_query->get());
    }

    public function permissions()
    {
        $roles = Role::with('product')
            ->where('role_name', '!=', 'Team Admin')
            ->get()
            ->map(function ($role) {
                $role->permissions = Role::permissions($role->id);

                return $role;
            });
        
        $studio_roles = $roles->filter(function ($role) {
            if ($role->product->name == "studio") {
                return $role;
            }
        });

        $data_lake_roles = $roles->filter(function ($role) {
            if ($role->product->name == "datalake") {
                return $role;
            }
        });

        return response()->success([
            "studio_roles"    => $studio_roles, 
            "data_lake_roles" => $data_lake_roles
        ]);
    }

    public function create(Request $request)
    {
        $request->validateWithErrors([
            'product_id' => 'required',
            'role_name'  => 'required'
        ]);

        Role::create($request->all());

        return response()->success();
    }

    public function update(Request $request, int $id)
    {
        $request->validateWithErrors([
            'role_name'   => 'required',
            'permissions' => 'required'
        ]);

        Role::find($id)->update(["role_name" => $request->role_name]);
        RolePermission::where('role_id', $id)->delete();

        foreach ($request->permissions as $permission) {
            if ($permission['has_permission']) {
                RolePermission::create([
                    'role_id'       => $id,
                    'permission_id' => $permission['id'],
                ]);
            }
        }

        return response()->success();
    }

    public function destroy(int $id)
    {
        $role = Role::with('product')->find($id);
        $include = $role->product->name == 'studio' ? 'project' : 'database';
        $role_users = UserRole::with($include)
            ->where('role_id', $id)
            ->whereHas($include)
            ->get()
            ->map(function ($role_perm) use ($include) {
                return ["user" => $role_perm->user, "product_child" => $role_perm->$include];
            });

        if ($role_users->count() > 0) {
            $roles_without = Role::where('product_id', $role->product->id)
                ->where('role_name', '!=', 'Team Admin')
                ->where('id', '!=', $id)
                ->get();

            if (empty($roles_without)) {
                return response()->error("Cannot delete this roll. Role is in use and is the only one.");
            }

            $data = [
                'role_id'       => $id,
                'role_name'     => $role->role_name,
                'roles'         => $roles_without,
                'product_name'  => $role->product->name,
                'user_metadata' => $role_users,
                'user_count'    => $role_users->count()
            ];

            return response()->success($data);
        }

        Role::find($id)->delete();

        return response()->success();
    }

    public function move(Request $request, int $id)
    {
        $request->validateWithErrors([
            'new_roles' => 'required'
        ]);

        foreach ($request->new_roles as $new_role) {
            UserRole::where('role_id', $id)
                ->where('user_id', $new_role['user_id'])
                ->where('product_child_id', $new_role['product_child_id'])
                ->update(['role_id' => $new_role['role_id']]);
        }

        Role::find($id)->delete();

        return response()->success(message: 'Users moved successfully and role deleted');
    }
}