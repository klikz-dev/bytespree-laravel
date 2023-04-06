<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AdminOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users()
    {
        $user = User::factory(['is_admin' => FALSE])->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));
    
        $response->assertStatus(403);
    }

    public function testadmin_users()
    {
        $user = User::factory(['is_admin' => TRUE])->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));
    
        $response->assertStatus(200);
    }

    public function test_non_admin_users_internal_api()
    {
        $user = User::factory(['is_admin' => FALSE])->create();

        $response = $this->actingAs($user)->get(route('internal-api.admin.users.index'));
    
        $response->assertStatus(403)
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_admin_users_internal_api()
    {
        $user = User::factory(['is_admin' => TRUE])->create();

        $response = $this->actingAs($user)->get(route('internal-api.admin.users.index'));
    
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }
}
