<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SchemaController extends Controller
{
    public function index()
    {
        $this->setCrumbs(
            'admin',
            [
                [
                   "title"     => "Schema Builder",
                    "location" => "/admin/schemas"
                ]
            ]
        );

        return view('schemas');
    }
}
