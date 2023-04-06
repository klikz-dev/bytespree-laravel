<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use App\Models\Server;

class ServerController extends Controller
{
    public function list()
    {
        $servers = Server::get();

        $servers = array_map(function ($server) {
            $server['status'] = 'online'; // todo

            return $server;
        }, $servers->toArray());

        return response()->success(
            $servers
        );
    }
}
