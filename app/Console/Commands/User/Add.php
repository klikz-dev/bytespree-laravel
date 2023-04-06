<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class Add extends Command
{
    protected $signature = 'user:add
                           {user_handle : The handle to assign to the user}
                           {--admin : Whether or not the user should be an admin}';

    protected $description = 'Add a user to Bytespree';

    public function handle()
    {
        $user_handle = $this->argument('user_handle');
        $is_admin = $this->option('admin');

        User::create([
            'user_handle' => $user_handle,
            'is_admin'    => $is_admin,
        ]);

        return 0;
    }
}