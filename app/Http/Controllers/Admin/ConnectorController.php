<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ConnectorController extends Controller
{
    public function index()
    {
        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "Connectors",
                    "location" => "/admin/connectors"
                ]
            ]
        );

        return view('connectors');
    }

    public function orchestrationLogo($connector_id)
    {
        $res = app('orchestration')->getConnector($connector_id);

        $seconds_to_cache = 86400; // seconds in a day
        $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";

        $headers = [
            'Content-Type'  => 'image/png',
            'Cache-Control' => "max-age=$seconds_to_cache",
            'Expires'       => $ts,
            'Pragma'        => 'cache'
        ];

        return response()->stream(function () use ($res) {
            echo base64_decode($res['logo']);
        }, 200, $headers);
    }
}
