<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AdminInternalApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected $seed = TRUE;

    public function getAdminUser() : User
    {
        return User::factory(['is_admin' => TRUE])->create();
    }

    public function getUser() : User
    {
        return User::factory(['is_admin' => FALSE])->create();
    }
}