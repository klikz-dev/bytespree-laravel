<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class Invite extends Command
{
    protected $signature = 'user:invite
                           {invitees : List of email addresses to invite, separated by commas}
                           {user_handle : The handle of the user sending the invitations}';

    protected $description = 'Invite a user to Bytespree';

    public function handle()
    {
        $invitees = $this->argument('invitees');
        $user_handle = $this->argument('user_handle');
        
        if (empty($invitees)) {
            $this->error("No invitees provided");

            return 1;
        }

        $emails = explode(',', $invitees);

        $invites = [];
        foreach ($emails as $email) {
            if (User::where('email', $email)->exists()) {
                $this->error("User with email {$email} already exists and will not be invited");
                continue;
            }

            $user = User::create([
                "user_handle" => $email,
                "email"       => $email,
                "is_pending"  => TRUE,
                "is_admin"    => FALSE
            ]);

            $invites[] = [
                "id"     => $user->id,
                "invite" => $email
            ];
        }

        $result = app('orchestration')->sendInvitation($invites, $user_handle, 'email');

        if (! $result) {
            $this->error("Failed to send invites to $invitees");

            return 1;
        }

        return 0;
    }
}