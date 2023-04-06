<?php

namespace Tests\Feature\Http\Controllers\Explorer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = TRUE;
    
    public function test_studio_enabled_with_user_without_studio_access()
    {
        $this->actingAs($this->createUserWithPermissions())
            ->get('/studio')
            ->assertStatus(403);
    }

    public function test_studio_enabled_with_user_with_studio_access()
    {
        $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/studio')
            ->assertStatus(200);
    }

    public function test_studio_disabled_with_external_user()
    {
        Product::where('name', 'studio')->update(['is_enabled' => FALSE]);

        $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/studio')
            ->assertStatus(403);
    }

    public function test_studio_disabled_with_internal_user()
    {
        Product::where('name', 'studio')->update(['is_enabled' => FALSE]);

        $this->actingAs($this->createUserWithPermissions(['studio_access'], is_admin: TRUE, is_internal: TRUE))
            ->get('/studio')
            ->assertStatus(200);
    }

    public function test_studio_disabled_with_internal_user_without_studio_access()
    {
        Product::where('name', 'studio')->update(['is_enabled' => FALSE]);

        $this->actingAs($this->createUserWithPermissions([], is_admin: FALSE, is_internal: TRUE))
            ->get('/studio')
            ->assertStatus(403);
    }
}
