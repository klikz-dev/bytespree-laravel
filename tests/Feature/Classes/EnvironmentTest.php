<?php

namespace Tests\Feature\Classes;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EnvironmentTest extends TestCase
{
    public function test_get_team()
    {
        config(['app.url' => 'https://foobar.bytespree.com']);

        $this->assertEquals(
            'foobar',
            app('environment')->getTeam()
        );

        config(['app.url' => 'https://dev.local.bytespree.com']);

        $this->assertEquals(
            'dev',
            app('environment')->getTeam()
        );
    }
}
