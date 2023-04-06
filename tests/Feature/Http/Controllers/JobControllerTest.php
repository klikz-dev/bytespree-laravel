<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class JobControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_jobs_view()
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/jobs')
            ->assertStatus(200);
    }
}
