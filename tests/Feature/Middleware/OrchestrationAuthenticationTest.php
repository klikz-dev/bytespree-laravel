<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Session;
use App\Models\User;

class OrchestrationAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Orchestration class so no API calls are made @todo: Mock this more flexibly
        app()->singleton('orchestration', function () {
            return new class {
                public function getUser(string $user_handle)
                {
                    return [
                        'dfa_preference'  => 'mobile',
                        'first_name'      => 'John',
                        'is_admin'        => FALSE,
                        'last_name'       => 'Doe',
                        'mobile_number'   => NULL,
                        'sso_provider_id' => NULL,
                        'team'            => 'testing',
                        'team_preference' => 'testing',
                        'teams'           => [
                            [
                                'domain' => 'testing',
                                'id'     => '1',
                            ]
                        ]
                    ];
                }
            };
        });
    }

    public function test_orchestration_redirect_if_not_logged_in()
    {
        $this->get('/')
            ->assertRedirect(config('orchestration.url') . '/auth/login');
    }

    public function test_invalid_session_format_redirects_to_orchestration()
    {
        $this->get('/?session=invalid_session')
            ->assertRedirect(config('orchestration.url') . '/auth/login');
    }

    public function test_invalid_session_redirects_to_orchestration()
    {
        $this->get('/?session=123')
            ->assertRedirect(config('orchestration.url') . '/auth/login');
    }

    public function test_non_existent_user_with_valid_session()
    {
        Session::create([
            'session' => '123',
            'user_id' => -1,
        ]);

        $this->get('/?session=123')
            ->assertRedirect(config('orchestration.url') . '/auth/login');
    }

    public function test_valid_session()
    {
        $user = User::factory()->create();

        $session = Session::create([
            'session'    => '123',
            'user_id'    => $user->id,
            'created_at' => now()->addMinutes(50000)
        ]);

        $this->get('/?session=' . $session->session)
            ->assertRedirect('/data-lake');

        $this->assertAuthenticated();
    }
}
