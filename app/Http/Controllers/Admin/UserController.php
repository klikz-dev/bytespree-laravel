<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "Users",
                    "location" => "/users"
                ]
            ]
        );
        
        return view('users');
    }
}
