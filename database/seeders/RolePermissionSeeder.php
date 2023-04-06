<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_permission_matrix = [
            'Team Admin' => [
              // None at the moment  
            ],
            'Project Admin' => [
                'project_grant',
                'project_manage',
                'project_remove',
                'table_read',
                'table_write',
                'map_read',
                'map_write',
                'comment_read',
                'comment_write',
                'flag_read',
                'flag_write',
                'link_read',
                'link_write',
                'export_data',
                'read_attach',
                'refresh_materialized_view',
            ],
            'Collaborator' => [
                'table_write',
                'map_read',
                'map_write',
                'comment_read',
                'comment_write',
                'flag_read',
                'flag_write',
                'link_read',
                'link_write',
                'read_attach',
                'refresh_materialized_view',
            ],
            'Observer' => [
                'table_read',
                'map_read',
                'comment_read',
                'flag_read',
                'link_read',
            ],
            'Database Admin' => [
                'manage_settings',
                'grant_sql_access',
                'delete',
                'view_logs',
                'manage_schema',
                'run',
                'tag_write',
            ],
            'Database Observer' => [
                'view_logs',
            ],
        ];

        $roles = Role::all();

        foreach ($role_permission_matrix as $role_name => $permission_names) {
            $role = $roles->where('role_name', $role_name)->first();
            if ($role) {
                foreach ($permission_names as $permission_name) {
                    $role->grantPermission($permission_name);
                }
            }
        }
    }
}
