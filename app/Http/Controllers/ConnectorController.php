<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Integration;

class ConnectorController extends Controller
{
    public function logo(Integration $connector)
    {
        $seconds_to_cache = 86400; // seconds in a day
        $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
        
        $headers = [
            'Content-Type'  => 'image/png',
            'Cache-Control' => "max-age=$seconds_to_cache",
            'Expires'       => $ts,
            'Pragma'        => 'cache'
        ];

        return response()->stream(function () use ($connector) {
            echo stream_get_contents($connector->logo);
        }, 200, $headers);
    }
}
