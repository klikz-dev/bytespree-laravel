<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes anyone can get to. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "public" middleware group.
|
*/
Route::get('heartbeat', function() {
    return view('heartbeat');
});