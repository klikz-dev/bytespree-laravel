<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminOnlyMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\StudioMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('', 'AuthController@handle')->name('index');

Route::get('auth/logout', 'AuthController@logout')->name('auth.logout');

Route::prefix('data-lake')->name('data-lake.')->middleware(PermissionMiddleware::class)->group(function () {
    Route::get('', 'DataLakeController@index')->name('index');
    Route::get('create', 'DataLake\DatabaseManagerController@create')->name('database-manager.create');
    Route::get('compare', 'DataLake\CompareController@index')->name('compare');
    Route::get('compare/csv', 'DataLake\CompareController@csv')->name('compare.csv');
    
    Route::prefix('database-manager/{database}')->name('database-manager.')->group(function () {
        Route::get('', 'DataLake\DatabaseManagerController@index')->name('');
        Route::get('attachments/{attachment_id}', 'DataLake\DatabaseManagerController@attachment')->name('attachment');
        Route::get('map', 'DataLake\MapController@index')->name('map');
        Route::get('logs/{build}/download', 'DataLake\LogController@download')->name('logs.download');
    });
});

Route::prefix('admin')->name('admin.')->middleware(AdminOnlyMiddleware::class)->group(function () {
    Route::get('', fn() => redirect()->route('admin.users.index'));

    Route::get('users', 'Admin\UserController@index')->name('users.index');
    Route::get('connectors', 'Admin\ConnectorController@index')->name('conenctors.index');
    Route::get('connectors/{connector_id}/orchestration-logo', 'Admin\ConnectorController@orchestrationLogo')->name('connectors.orchestration-logo');
    Route::get('schemas', 'Admin\SchemaController@index')->name('schemas.index');
    Route::get('schemas/{schema_id}/tables', 'Admin\SchemaTableController@index')->name('schemas.tables');
    Route::get('schemas/{schema_id}/modules', 'Admin\SchemaModuleController@index')->name('schemas.modules');
    Route::get('schemas/table/{table_id}/columns', 'Admin\SchemaColumnController@index')->name('schemas.columns');
    Route::get('tags', 'Admin\TagController@index')->name('tags.index');
    Route::get('system-notifications', 'Admin\SystemNotificationController@index')->name('system-notifications.index');
    Route::get('servers', 'Admin\ServerController@index')->name('servers.index');
    Route::get('roles', 'Admin\RoleController@index')->name('roles.index');
    Route::get('sftp-sites', 'Admin\SftpController@index')->name('sftp.index');
    Route::get('mssql-servers', 'Admin\MicrosoftSqlServerController@index')->name('mssql.index');
});

Route::get('jobs', 'JobController@index')->name('jobs.index');

Route::name('studio.')->prefix('studio')->middleware([StudioMiddleware::class, PermissionMiddleware::class])->group(function() {
    Route::get('', 'Explorer\IndexController@index')->name('index');
    Route::get('projects/{project}', 'Explorer\ProjectController@show')->name('projects.show');
    Route::get('projects/{project}/attachments/{attachment}', 'Explorer\ProjectController@attachment')->name('projects.attachment');
    Route::get('projects/{project}/download-mappings', 'Explorer\ProjectController@downloadMappings')->name('projects.download-mappings');
    Route::get('projects/{project}/tables/{schema}/{table}', 'Explorer\TableController@show')->name('projects.tables.show');
    Route::get('projects/{project}/tables/{schema}/{table}/attachment/{attachment}', 'Explorer\TableController@attachment')->name('projects.tables.attachment');
    Route::get('projects/{project}/tables/{schema}/{table}/mssql/map/{saved_data:guid}', 'Explorer\TableController@mssql')->name('projects.tables.mssql');
    Route::get('projects/{project}/tables/{schema}/{table}/map', 'Explorer\MapController@show')->name('projects.tables.map.show');
    Route::get('projects/{project}/tables/{schema}/{table}/map/download', 'Explorer\MapController@download')->name('projects.tables.map.download');
});

Route::prefix('OAuth')->name('oauth.')->group(function() {
    Route::post('sendOAuth', 'OauthController@send')->name('send');
    Route::get('getOAuth/{code}/{guid}', 'OauthController@get')->name('get');
});

Route::get('connectors/{connector}/logo', 'ConnectorController@logo')->name('connectors.logo');

// todo remove later
Route::post('webhook-test', function (Request $request) {
    return response()->success($request->all());
});
