<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class TagController extends Controller
{
    public function index()
    {
        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "Tags",
                    "location" => "/admin/tags"
                ]
            ]
        );

        return view('tags');
    }
}
