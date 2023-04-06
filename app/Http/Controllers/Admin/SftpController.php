<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SftpController extends Controller
{
    public function index()
    {
        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "SFTP Sites",
                    "location" => "/admin/sftp-sites"
                ]
            ]
        );

        return view('sftp_sites');
    }
}
