<?php

namespace Tests\Feature\Http\Controllers\InternalApi\V1;

use App\Models\Manager\JenkinsBuild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class JobControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $seed = TRUE;

    public function test_with_no_jobs()
    {
        $user = User::factory()->create();
        
        $this->actingAs(User::factory()->create()->first())
            ->get('/internal-api/v1/jobs')
            ->assertStatus(200)
            ->assertJson([]);
    }

    public function test_with_five_finished_jobs()
    {
        for ($i = 0; $i < 5; ++$i) {
            $jobs[] = JenkinsBuild::factory()->create([
                'result'     => NULL,
                'parameters' => [
                    '/var/www/dev/some_command',
                    'Services',
                    'Run',
                    $this->faker->randomElement(['run', 'test', 'sync']),
                    '1',
                ],
            ]);
        }

        $this->actingAs(User::factory()->create()->first())
            ->get('/internal-api/v1/jobs')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_with_finished_and_unfinished_jobs()
    {
        for ($i = 0; $i < 10; ++$i) {
            $jobs[] = JenkinsBuild::factory()->create([
                'result'     => NULL,
                'parameters' => [
                    '/var/www/dev/some_command',
                    'Services',
                    'Run',
                    $this->faker->randomElement(['run', 'test', 'sync']),
                    '1',
                ],
            ]);
        }

        for ($i = 0; $i < 5; ++$i) {
            $jobs[] = JenkinsBuild::factory()->create([
                'result'     => $this->faker->randomElement(['SUCCESS', 'FAILURE', 'ABORTED']),
                'parameters' => [
                    '/var/www/dev/some_command',
                    'Services',
                    'Run',
                    $this->faker->randomElement(['run', 'test', 'sync']),
                    '1',
                ],
            ]);
        }

        $this->actingAs(User::factory()->create()->first())
            ->get('/internal-api/v1/jobs')
            ->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }
}
