<?php

namespace App\Http\Controllers;

use App\Attributes\Can;
use App\Models\Server;

class DataLakeController extends Controller
{
    #[Can(permission: 'datalake_access', product: 'datalake')]
    public function index()
    {
        $this->setCrumbs(
            'datalake',
            [
                [
                    "title"    => "Data Lake",
                    "location" => "/data-lake"
                ]
            ]
        );

        $flashError = session()->get('flash_error_message');

        $default_server_id = Server::where('is_default', TRUE)->first()->id ?? "''";

        $system_timezone = date('T');

        $system_time_offset = date('Z');

        return view('data-lake', compact('flashError', 'system_timezone', 'system_time_offset', 'default_server_id'));
    }
}
