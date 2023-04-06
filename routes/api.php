<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1/monitor')->name('v1.monitor')->group(function () {
    Route::get('servers', 'V1\MonitorController@servers')->name('servers');
    Route::get('databases', 'V1\MonitorController@databases')->name('databases');
});
