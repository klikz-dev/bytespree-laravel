<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ServerController extends Controller
{
    public function index()
    {
        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "Server Management",
                    "location" => "/admin/servers"
                ]
            ]
        );
        
        return view('servers');
    }
}
