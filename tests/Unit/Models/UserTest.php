<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\UserRole;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserTest extends TestCase
{
    use RefreshDatabase;
    protected $seed = TRUE;

    public function test_disabled_product_permission_as_external_user()
    {
        $user = User::factory(['is_admin' => TRUE])->create();

        $product = Product::where('name', 'studio')->first();

        $product->update(['is_enabled' => FALSE]);

        $permission = Permission::where('name', 'studio_access')->first();

        $user->addPermission($permission->id);

        $this->assertFalse($user->hasPermissionTo('studio_access', product: 'studio'));
    }

    public function test_disabled_product_permission_as_internal_user_should_bypass_disabled_product()
    {
        $user = User::factory(['is_admin' => FALSE, 'email' => 'demo@data-management.com'])->create();

        $product = Product::where('name', 'studio')->first();

        $product->update(['is_enabled' => FALSE]);

        $permission = Permission::where('name', 'studio_access')->first();

        $user->addPermission($permission->id);

        $this->assertTrue($user->hasPermissionTo('studio_access', product: 'studio'));
    }

    public function test_simple_user_permission()
    {
        $user = User::factory(['is_admin' => FALSE])->create();

        $this->assertFalse($user->hasPermissionTo('datalake_access', product: 'datalake'));

        $permission = Permission::where('name', 'datalake_access')->first();

        $user->addPermission($permission->id);

        cache()->store('pageload')->flush();

        $this->assertTrue($user->hasPermissionTo('datalake_access', product: 'datalake'));
    }

    public function test_role_based_permission()
    {
        $user = User::factory(['is_admin' => FALSE])->create();

        $this->assertFalse($user->hasPermissionTo('view_logs', 1, 'datalake'));

        $role = Role::factory(['role_name' => 'datalake_admin_role'])->create();

        $permission = Permission::where('name', 'view_logs')->first();

        $role_permission = RolePermission::factory(['permission_id' => $permission->id, 'role_id' => $role->id])->create();

        UserRole::updateUserRoles($user->id, [
            [
                'product_child_id' => 1,
                'role_id'          => $role->id
            ]
        ]);

        cache()->store('pageload')->flush();

        $this->assertTrue($user->hasPermissionTo('view_logs', 1, 'datalake'));

        $this->assertFalse($user->hasPermissionTo('run', 1, 'datalake'));
    }

    public function test_simple_user_permission_as_admin()
    {
        $user = User::factory(['is_admin' => TRUE])->create();

        $this->assertTrue($user->hasPermissionTo('datalake_access', product: 'datalake'));
    }

    public function test_role_based_permission_as_admin()
    {
        $user = User::factory(['is_admin' => TRUE])->create();

        $this->assertTrue($user->hasPermissionTo('view_logs', 1, 'datalake'));
    }

    public function test_role_based_permission_force_response()
    {
        $this->expectException(HttpResponseException::class);

        $user = User::factory(['is_admin' => FALSE])->create();

        $this->assertTrue($user->hasPermissionTo('view_logs', 1, 'datalake', TRUE));
    }
}
