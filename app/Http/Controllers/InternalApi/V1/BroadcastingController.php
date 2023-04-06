<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pusher\Pusher;

class BroadcastingController extends Controller
{
    public function auth(Request $request)
    {
        $pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            [
                'cluster' => config('broadcasting.connections.pusher.cluster'),
            ],
        );

        if (! $request->has('channel')) {
            return $pusher->authenticateUser($request->socket_id, ['id' => $request->user()->id]);
        }

        $pusher->authorizeChannel($request->channel, $request->socket_id);
    }
}
