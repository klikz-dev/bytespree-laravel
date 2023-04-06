<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class MicrosoftSqlServerController extends Controller
{
    public function index()
    {
        return view('mssql');
    }
}
