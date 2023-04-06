<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function index()
    {
        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "Role Management",
                    "location" => "/admin/roles"
                ]
            ]
        );

        return view('roles');
    }
}
