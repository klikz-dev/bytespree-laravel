<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SystemNotificationController extends Controller
{
    public function index()
    {
        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "System Notifications",
                    "location" => "/admin/system-notifications"
                ]
            ]
        );

        return view('system_notifications');
    }
}
