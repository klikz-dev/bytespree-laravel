<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('user-{id}', function ($user, $id) {
    return session()->get('orchestration_id') == $id;
});



Broadcast::channel('team-{team}', function ($user, $team) {
    return TRUE;
});
