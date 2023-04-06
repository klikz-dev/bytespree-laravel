<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Permission;
use App\Models\Server;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use WithFaker;

    public function createUserWithPermissions(array $permissions = [], bool $is_admin = FALSE, bool $is_internal = FALSE) : User
    {
        $prepopulated = [
            'is_admin' => $is_admin,
        ];

        if ($is_internal) {
            $prepopulated['email'] = preg_replace('/@example\..*/', '@rkdgroup.com', $this->faker->unique()->safeEmail);
        }

        $user = User::factory($prepopulated)->create();

        foreach ($permissions as $permission) {
            $permission = Permission::where('name', $permission)->first();
            $user->addPermission($permission->id);
        }

        return $user;
    }

    public function createLocalServer()
    {
        return Server::create([
            'name'             => 'local',
            'hostname'         => '127.0.0.1',
            'username'         => config('database.connections.pgsql.username'),
            'password'         => config('database.connections.pgsql.password'),
            'port'             => config('database.connections.pgsql.port'),
            'default_database' => config('database.connections.pgsql.database'),
            'driver'           => 'postgre',
            'provider_guid'    => 3010,
            'is_default'       => TRUE,
        ]);
    }
}
