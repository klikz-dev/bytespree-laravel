<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Permission;
use App\Models\Product;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = Product::all();

        $studio_id = $products->where('name', 'studio')->first()->id;
        $datalake_id = $products->where('name', 'datalake')->first()->id;

        $permissions = [
            ['name' => 'comment_read', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'comment_write', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'delete', 'type' => 'role', 'product_id' => $datalake_id],
            ['name' => 'export_data', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'flag_read', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'flag_write', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'grant_sql_access', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'link_read', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'link_write', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'manage_schema', 'type' => 'role', 'product_id' => $datalake_id],
            ['name' => 'manage_settings', 'type' => 'role', 'product_id' => $datalake_id],
            ['name' => 'map_read', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'map_write', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'project_grant', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'project_manage', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'project_remove', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'read_attach', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'refresh_materialized_view', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'run', 'type' => 'role', 'product_id' => $datalake_id],
            ['name' => 'studio_access', 'type' => 'user', 'product_id' => $studio_id],
            ['name' => 'studio_create', 'type' => 'user', 'product_id' => $studio_id],
            ['name' => 'table_read', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'table_write', 'type' => 'role', 'product_id' => $studio_id],
            ['name' => 'tag_write', 'type' => 'role', 'product_id' => $datalake_id],
            ['name' => 'view_logs', 'type' => 'role', 'product_id' => $datalake_id],
            ['name' => 'datalake_access', 'type' => 'user', 'product_id' => $datalake_id],
            ['name' => 'datalake_create', 'type' => 'user', 'product_id' => $datalake_id],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                $permission,
                ['is_deleted' => false]
            );
        }
    }
}
