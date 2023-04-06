<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminOnlyMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\StudioMiddleware;
use App\Http\Middleware\AddHeaders;

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

Route::prefix('v1/me')->name('internal-api.me.')->group(function () {
    Route::get('', 'V1\MeController@me')->name('me');
    Route::get('teams', 'V1\MeController@teams')->name('teams');
    Route::get('stats', 'V1\MeController@stats')->name('stats');
    Route::put('', 'V1\MeController@update')->name('update');
    Route::put('join', 'V1\MeController@join')->name('join');    
    Route::get('permissions', 'V1\MeController@permissions')->name('permissions');
});

Route::prefix('v1/broadcasting')->name('internal-api.broadcasting.')->group(function () {
    Route::post('auth', 'V1\BroadcastingController@auth')->name('auth');
});

Route::prefix('v1/admin')->name('internal-api.admin.')->middleware(AdminOnlyMiddleware::class . ':true')->group(function () {
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('', 'V1\Admin\UserController@index')->name('index');
        Route::get('{user}/permissions', 'V1\Admin\UserController@permissions')->name('permissions');
        Route::put('{user}/permissions', 'V1\Admin\UserController@updatePermissions')->name('update-permissions');
        Route::get('{user}/projects', 'V1\Admin\UserController@projects')->name('projects');
        Route::post('invite', 'V1\Admin\UserController@invite')->name('invite');
        Route::delete('invite', 'V1\Admin\UserController@destroyInvite')->name('destroyInvite');
        Route::delete('{user}', 'V1\Admin\UserController@destroy')->name('destroy');
        Route::put('{user}', 'V1\Admin\UserController@update')->name('update');
    });

    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('', 'V1\Admin\TagController@list')->name('list');
        Route::post('', 'V1\Admin\TagController@create')->name('create');
        Route::put('{id}', 'V1\Admin\TagController@update')->name('update');
        Route::delete('{id}', 'V1\Admin\TagController@destroy')->name('destroy');
    });

    Route::prefix('sftp')->name('sftp.')->group(function () {
        Route::get('', 'V1\Admin\SftpController@list')->name('list');
        Route::post('', 'V1\Admin\SftpController@create')->name('create');
        Route::put('{id}', 'V1\Admin\SftpController@update')->name('update');
        Route::delete('{id}', 'V1\Admin\SftpController@destroy')->name('destroy');
    });

    Route::prefix('mssql')->name('mssql.')->group(function () {
        Route::get('', 'V1\Admin\MicrosoftSqlServerController@list')->name('list');
        Route::get('{server}/databases', 'V1\Admin\MicrosoftSqlServerController@databases')->name('databases');
        Route::get('{server}/databases/{database}/tables', 'V1\Admin\MicrosoftSqlServerController@tables')->name('tables');
        Route::post('', 'V1\Admin\MicrosoftSqlServerController@create')->name('create');
        Route::put('{id}', 'V1\Admin\MicrosoftSqlServerController@update')->name('update');
        Route::delete('{id}', 'V1\Admin\MicrosoftSqlServerController@destroy')->name('destroy');
    });

    Route::prefix('servers')->name('servers.')->group(function () {
        Route::get('', 'V1\Admin\ServerController@list')->name('list');
        Route::get('configurations', 'V1\Admin\ServerController@configurations')->name('configurations');
        Route::post('', 'V1\Admin\ServerController@create')->name('create');
        Route::put('{server}', 'V1\Admin\ServerController@update')->name('update');
        Route::delete('{server}', 'V1\Admin\ServerController@destroy')->name('destroy');
    });

    Route::prefix('schemas')->name('schema.')->group(function () {
        Route::get('', 'V1\Admin\SchemaController@list')->name('list');
        Route::get('{id}', 'V1\Admin\SchemaController@get')->name('get');
        Route::post('', 'V1\Admin\SchemaController@create')->name('create');
        Route::put('{id}', 'V1\Admin\SchemaController@update')->name('update');
        Route::put('clone/{id}', 'V1\Admin\SchemaController@clone')->name('clone');
        Route::put('resync/{id}', 'V1\Admin\SchemaController@resync')->name('resync');
        Route::delete('{id}', 'V1\Admin\SchemaController@destroy')->name('destroy');
    });

    Route::prefix('schema-tables')->name('schema-tables.')->group(function () {
        Route::get('{schema_id}', 'V1\Admin\SchemaTableController@list')->name('list');
        Route::post('', 'V1\Admin\SchemaTableController@create')->name('create');
        Route::put('{id}', 'V1\Admin\SchemaTableController@update')->name('update');
        Route::delete('{id}', 'V1\Admin\SchemaTableController@destroy')->name('destroy');
    });

    Route::prefix('schema-modules')->name('schema-modules.')->group(function () {
        Route::get('{schema_id}', 'V1\Admin\SchemaModuleController@list')->name('list');
        Route::put('{schema_id}', 'V1\Admin\SchemaModuleController@update')->name('update');
    });

    Route::prefix('schema-columns')->name('schema-columns.')->group(function () {
        Route::get('{table_id}', 'V1\Admin\SchemaColumnController@list')->name('list');
        Route::post('', 'V1\Admin\SchemaColumnController@create')->name('create');
        Route::put('{id}', 'V1\Admin\SchemaColumnController@update')->name('update');
        Route::delete('{id}', 'V1\Admin\SchemaColumnController@destroy')->name('destroy');
    });

    Route::name('system-notifications.')->prefix('system-notifications')->group(function () {
        Route::name('subscriptions.')->prefix('subscriptions')->group(function () {
            Route::get('', 'V1\SystemNotificationController@list')->name('list');
            Route::post('', 'V1\SystemNotificationController@store')->name('store');
            Route::put('{subscription}', 'V1\SystemNotificationController@update')->name('update');
            Route::delete('{subscription}', 'V1\SystemNotificationController@destroy')->name('destroy');
            Route::get('{subscription}', 'V1\SystemNotificationController@show')->name('show');
        });
    
        Route::get('types', 'V1\SystemNotificationController@types')->name('types');
        Route::get('channels', 'V1\SystemNotificationController@channels')->name('channels');
    });

    Route::name('connectors.')->prefix('connectors')->group(function () {
        Route::get('available', 'V1\Admin\ConnectorController@available')->name('available');
        Route::delete('{connector}', 'V1\Admin\ConnectorController@destroy')->name('destroy');
        Route::get('{connector}', 'V1\Admin\ConnectorController@show')->name('show');
        Route::put('{connector_id}', 'V1\Admin\ConnectorController@update')->name('update');
        Route::post('', 'V1\Admin\ConnectorController@store')->name('store');
    });
});

Route::prefix('v1/data-lakes')->name('internal-api.data-lake.')->middleware([PermissionMiddleware::class])->group(function () {
    Route::get('', 'V1\DataLake\DataLakeController@list')->name('list');
    Route::post('', 'V1\DataLake\DataLakeController@create')->name('create');
    Route::post('compare', 'V1\DataLake\CompareController@compare')->name('compare');
    Route::get('compare/{database}/tables', 'V1\DataLake\CompareController@tables')->name('compare.tables');

    Route::get('{database}', 'V1\DataLake\DataLakeController@show')->name('show');
    Route::delete('{database}', 'V1\DataLake\DataLakeController@destroy')->name('destroy');
    Route::get('{database}/dependencies', 'V1\DataLake\DataLakeController@dependencies')->name('dependencies');
    Route::post('{database}/request-access', 'V1\DataLake\DataLakeController@requestAccess')->name('request-access');
    Route::put('{database}/version', 'V1\DataLake\DataLakeController@version')->name('version');

    Route::get('{database}/jobs', 'V1\DataLake\JobController@list')->name('jobs.list');
    Route::post('{database}/jobs/run', 'V1\DataLake\JobController@run')->name('jobs.run');

    Route::get('{database}/sql-user', 'V1\DataLake\SqlUserController@show')->name('sql-user');
    Route::get('{database}/sql-user/create', 'V1\DataLake\SqlUserController@create')->name('sql-user.create');
    Route::get('{database}/sql-user/certificate', 'V1\DataLake\SqlUserController@certificate')->name('sql-user.certificate');
    Route::delete('{database}/sql-user', 'V1\DataLake\SqlUserController@destroy')->name('sql-user.destroy');
    Route::post('{database}/sql-user', 'V1\DataLake\SqlUserController@store')->name('sql-user.store');
    Route::put('{database}/sql-user', 'V1\DataLake\SqlUserController@update')->name('sql-user.update');

    Route::post('{database}/tags', 'V1\DataLake\TagController@store')->name('tags.store');
    Route::delete('{database}/tags', 'V1\DataLake\TagController@destroy')->name('tags.destroy');

    Route::get('{database}/callbacks', 'V1\DataLake\DataLakeController@callbacks')->name('callbacks');
    Route::post('{database}/callbacks', 'V1\DataLake\CallbackController@store')->name('callbacks.store');
    Route::put('{database}/callbacks', 'V1\DataLake\CallbackController@update')->name('callbacks.update');
    Route::delete('{database}/callbacks', 'V1\DataLake\CallbackController@destroy')->name('callbacks.destroy');

    Route::get('{database}/schedule', 'V1\DataLake\DataLakeController@schedule')->name('schedule');
    Route::put('{database}/schedule', 'V1\DataLake\ScheduleController@update')->name('schedule.update');
    Route::get('{database}/schedule/{schedule}/values', 'V1\DataLake\ScheduleController@values')->name('schedule.values');

    Route::post('{database}/settings/test', 'V1\DataLake\SettingController@test')->name('settings.test');
    Route::put('{database}/settings', 'V1\DataLake\SettingController@update')->name('settings.update');
    Route::get('{database}/settings/{setting}', 'V1\DataLake\SettingController@show')->name('settings.show');

    Route::post('{database}/convert-to-basic', 'V1\DataLake\ConversionController@convertToBasic')->name('convert-to-basic');

    // todo name this better, maybe? todo: work on this when connectors are up and running.
    Route::put('{database}/saveTables', 'V1\DataLake\DataLakeController@saveTables')->name('saveTables');

    Route::prefix('{database}/logs')->name('logs.')->group(function () {
        Route::get('', 'V1\DataLake\LogController@list')->name('list');
        Route::get('{build}', 'V1\DataLake\LogController@show')->name('show');
    });

    Route::prefix('{database}/tables')->name('tables.')->group(function () {
        Route::get('', 'V1\DataLake\TableController@list')->name('list');
        Route::get('{table_schema}/{table_name}/details', 'V1\DataLake\TableController@details')->name('details');
        Route::get('{table_schema}/{table_name}/dependencies', 'V1\DataLake\TableController@dependencies')->name('dependencies');
        Route::get('{table_schema}/{table_name}/check-dependencies', 'V1\DataLake\TableController@checkDependencies')->name('check-dependencies');
        Route::get('{table_id}/logs', 'V1\DataLake\TableController@logs')->name('logs');
        Route::get('logs/{log_id}', 'V1\DataLake\TableController@logDetails')->name('logDetails');
        Route::post('compare-columns', 'V1\DataLake\TableController@compareColumns')->name('compareColumns');
        Route::post('', 'V1\DataLake\TableController@build')->name('build');
        Route::post('index', 'V1\DataLake\TableController@createIndex')->name('createIndex');
        Route::put('', 'V1\DataLake\TableController@import')->name('import');
        Route::delete('{table_schema}/{table_name}/{ignore_errors}', 'V1\DataLake\TableController@drop')->name('drop');
        Route::delete('index/{index_name}', 'V1\DataLake\TableController@dropIndex')->name('dropIndex');
    });

    Route::prefix('{database}/views')->name('views.')->group(function () {
        Route::get('', 'V1\DataLake\ViewController@list')->name('list');
        Route::post('', 'V1\DataLake\ViewController@create')->name('create');
        Route::post('rebuild', 'V1\DataLake\ViewController@rebuild')->name('rebuild');
        Route::put('', 'V1\DataLake\ViewController@update')->name('update');
        Route::put('refresh', 'V1\DataLake\ViewController@refresh')->name('refresh');
        Route::delete('{view}', 'V1\DataLake\ViewController@destroy')->name('destroy');
    });

    Route::prefix('{database}/foreign-databases')->name('foreign-databases.')->group(function () {
        Route::get('tables', 'V1\DataLake\ForeignDatabaseController@tables')->name('tables');
        Route::get('unused', 'V1\DataLake\ForeignDatabaseController@unused')->name('list');
        Route::post('', 'V1\DataLake\ForeignDatabaseController@create')->name('create');
        Route::put('refresh', 'V1\DataLake\ForeignDatabaseController@refresh')->name('refresh');
    });

    Route::name('schedules')->prefix('schedules')->group(function () {
        Route::get('types', 'V1\ScheduleController@types')->name('types');
        Route::get('{schedule}/properties', 'V1\ScheduleController@properties')->name('properties');
    });
});

Route::name('internal-api.v1.notifications.')->prefix('v1/notifications')->group(function () {
    Route::get('', 'V1\NotificationController@index')->name('index');
    Route::get('read', 'V1\NotificationController@read')->name('read');
    Route::get('dismiss', 'V1\NotificationController@dismiss')->name('dismiss');
});

Route::name('internal-api.v1.products')->prefix('v1/products')->group(function () {
    Route::get('', 'V1\ProductController@list')->name('list');
});

Route::name('internal-api.v1.jenkins')->prefix('v1/jenkins')->group(function () {
    Route::get('check/{job_name}', 'V1\JenkinsController@check')->name('check');
    Route::post('launch', 'V1\JenkinsController@launch')->name('launch');
});

Route::name('internal-api.v1.uploads')->prefix('v1/uploads')->group(function () {
    Route::get('{token}', 'V1\UploadController@get')->name('get');
    Route::get('{file_name}/columns/{has_columns}/{delimiter}', 'V1\UploadController@columns')->name('columns');
    Route::post('', 'V1\UploadController@create')->name('create');
});

Route::name('internal-api.v1.roles')->prefix('v1/roles')->group(function () {
    Route::get('', 'V1\RoleController@list')->name('list');
    Route::get('permissions', 'V1\RoleController@permissions')->name('permissions');
    Route::post('', 'V1\RoleController@create')->name('create');
    Route::put('{id}', 'V1\RoleController@update')->name('update');
    Route::delete('{id}', 'V1\RoleController@destroy')->name('destroy');
    Route::delete('move/{id}', 'V1\RoleController@move')->name('move');
});

Route::name('internal-api.v1.permissions')->prefix('v1/permissions')->group(function () {
    Route::get('', 'V1\PermissionController@list')->name('list');
});

Route::name('internal-api.v1.team')->prefix('v1/team')->group(function () {
    Route::get('', 'V1\TeamController@index')->name('index');
});

Route::prefix('v1/explorer')->name('internal-api.explorer.')->group(function () {
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('', 'V1\Explorer\ProjectController@list')->name('list');
    });

    Route::prefix('modules')->name('modules.')->group(function () {
        Route::get('', 'V1\Explorer\MappingModuleController@list')->name('list');
    });
});

Route::get('v1/crumbs', 'V1\CrumbController')->middleware(AddHeaders::class)->name('internal-api.v1.crumbs');

Route::name('internal-api.v1.servers')->prefix('v1/servers')->group(function () {
    Route::get('', 'V1\ServerController@list')->name('types');
});

Route::name('internal-api.v1.tags')->prefix('v1/tags')->group(function () {
    Route::get('', 'V1\TagController@list')->name('list');
});

Route::name('internal-api.v1.connectors')->prefix('v1/connectors')->group(function () {
    Route::get('', 'V1\ConnectorController@list')->name('list');
    Route::get('{connector}', 'V1\ConnectorController@show')->name('show');
    Route::post('{connector}/tables', 'V1\ConnectorController@tables')->name('tables');
    Route::post('{connector}/metadata', 'V1\ConnectorController@metadata')->name('metadata');
});

Route::name('internal-api.v1.jobs')->prefix('v1/jobs')->group(function () {
    Route::get('', 'V1\JobController@list')->name('list');
    Route::get('{job}/output', 'V1\JobController@output')->name('output');
    Route::get('{job}/stop', 'V1\JobController@stop')->name('stop');
});

Route::name('internal-api.v1.studio')->prefix('v1/studio')->middleware([StudioMiddleware::class, PermissionMiddleware::class])->group(function () {
    Route::get('projects', 'V1\Explorer\ProjectController@list')->name('projects.list');
    Route::post('projects', 'V1\Explorer\ProjectController@store')->name('projects.store');
    Route::post('projects/suggest-schema', 'V1\Explorer\ProjectController@suggestSchema')->name('projects.suggest-schema');

    Route::name('projects.')->prefix('projects/{project}')->group(function () {
        Route::put('', 'V1\Explorer\ProjectController@update')->name('update');
        Route::delete('', 'V1\Explorer\ProjectController@destroy')->name('destroy');

        Route::get('', 'V1\Explorer\ProjectController@show')->name('show');
        Route::get('activity', 'V1\Explorer\ProjectController@activity')->name('activity');
        Route::get('export', 'V1\Explorer\ProjectController@export')->name('export');
        Route::get('flags', 'V1\Explorer\ProjectController@flags')->name('flags');
        Route::get('details', 'V1\Explorer\ProjectController@details')->name('details');
        Route::get('roles', 'V1\Explorer\ProjectController@roles')->name('roles');
        Route::get('tables', 'V1\Explorer\ProjectController@tables')->name('tables');
        Route::get('search-columns', 'V1\Explorer\ProjectController@searchColumns')->name('search-columns');
        Route::get('mapping-modules', 'V1\Explorer\MappingModuleController@list')->name('mapping-modules');

        Route::get('saved-queries', 'V1\Explorer\SavedQueryController@list')->name('saved-queries');
        Route::delete('saved-queries/{saved_query}', 'V1\Explorer\SavedQueryController@destroy')->name('saved-queries.delete');

        Route::get('settings', 'V1\Explorer\SettingController@list')->name('settings');
        Route::put('settings', 'V1\Explorer\SettingController@update')->name('settings.update');

        Route::get('publishers', 'V1\Explorer\PublisherController@list')->name('publishers');
        Route::get('publishers/destinations', 'V1\Explorer\PublisherController@destinations')->name('publishers.destinations');
        Route::get('publishers/{publisher}/logs', 'V1\Explorer\PublisherController@logs')->name('publishers.logs');
        Route::delete('publishers/{publisher}', 'V1\Explorer\PublisherController@destroy')->name('publishers.destroy');

        Route::get('users', 'V1\Explorer\UserController@list')->name('users.list');
        Route::put('users', 'V1\Explorer\UserController@manage')->name('manage');

        Route::get('links', 'V1\Explorer\LinkController@list')->name('links.list');
        Route::post('links', 'V1\Explorer\LinkController@store')->name('links.store');
        Route::delete('links/{link}', 'V1\Explorer\LinkController@destroy')->name('links.destroy');

        Route::put('destination-schema', 'V1\Explorer\ProjectController@updateDestinationSchema')->name('updateDestinationSchema');
        Route::get('destination-tables', 'V1\Explorer\MappingController@tables')->name('destination-tables');
        Route::get('destination-tables/{schema_id}/{table_name}/columns', 'V1\Explorer\MappingController@tableColumns')->name('destination-tables.columns');

        Route::put('completed', 'V1\Explorer\ProjectController@completed')->name('completed');

        Route::name('tables.')->prefix('tables/{schema}/{table}')->group(function () {
            Route::get('', 'V1\Explorer\TableController@show')->name('show');
            Route::get('active-users', 'V1\Explorer\TableController@activeUsers')->name('active-users');
            Route::get('table-columns', 'V1\Explorer\TableController@tableColumns')->name('table-columns');
            Route::post('columns', 'V1\Explorer\TableController@columns')->name('columns');
            Route::post('longest-counts', 'V1\Explorer\TableController@longestCounts')->name('longest-counts');
            Route::post('popular-counts', 'V1\Explorer\TableController@popularCounts')->name('popular-counts');
            Route::get('stats', 'V1\Explorer\TableController@stats')->name('stats');
            Route::post('records', 'V1\Explorer\TableController@records');
            Route::post('count', 'V1\Explorer\TableController@count');
            Route::post('saved-queries', 'V1\Explorer\SavedQueryController@store')->name('saved-queries.store');
            Route::put('saved-queries/{saved_query}', 'V1\Explorer\SavedQueryController@update')->name('saved-queries.update');
            Route::get('meta', 'V1\Explorer\TableController@meta')->name('meta');

            Route::post('unions/test', 'V1\Explorer\UnionController@test')->name('unions.test');

            Route::get('comments', 'V1\Explorer\TableCommentController@list')->name('comments');
            Route::post('comments', 'V1\Explorer\TableCommentController@store')->name('comments.store');
            Route::delete('comments/{comment}', 'V1\Explorer\TableCommentController@destroy')->name('comments.destroy');

            Route::get('files', 'V1\Explorer\TableFileController@list')->name('files');
            Route::post('files', 'V1\Explorer\TableFileController@store')->name('files.store');
            Route::delete('files/{file}', 'V1\Explorer\TableFileController@destroy')->name('files.destroy');

            Route::get('flags', 'V1\Explorer\TableFlagController@list')->name('flags');
            Route::post('flags', 'V1\Explorer\TableFlagController@store')->name('flags.store');
            Route::delete('flags/{flag}', 'V1\Explorer\TableFlagController@destroy')->name('flags.destroy');
            Route::delete('flags', 'V1\Explorer\TableFlagController@destroyAllForColumn')->name('flags.destroy-all-for-column');

            Route::get('notes', 'V1\Explorer\TableNoteController@list')->name('notes');
            Route::delete('notes/{note}', 'V1\Explorer\TableNoteController@destroy')->name('notes.destroy');
            Route::post('notes', 'V1\Explorer\TableNoteController@store')->name('notes.store');
            Route::put('notes/{note}', 'V1\Explorer\TableNoteController@update')->name('notes.update');

            Route::get('mappings', 'V1\Explorer\MappingController@list')->name('mappings');
            Route::get('full-mappings', 'V1\Explorer\MappingController@fullTable')->name('full-mappings');
            Route::delete('mappings/{mapping}', 'V1\Explorer\MappingController@destroy')->name('mappings.destroy');
            Route::post('mappings', 'V1\Explorer\MappingController@store')->name('mappings.store');
            Route::put('mappings/{mapping}', 'V1\Explorer\MappingController@update')->name('mappings.update');
            Route::put('mappings/{mapping}/programming', 'V1\Explorer\MappingController@programming')->name('mappings.programming');

            Route::name('publishers.')->prefix('publishers')->group(function () {
                Route::get('check', 'V1\Explorer\PublisherController@check')->name('check');
                Route::name('mssql.')->prefix('mssql')->group(function () {
                    Route::get('details/{guid}', 'V1\Explorer\PublisherController@mssqlDetails')->name('details');
                    Route::post('', 'V1\Explorer\PublisherController@mssql');
                    Route::post('map/{guid}', 'V1\Explorer\PublisherController@mssqlMap')->name('map');
                });
                Route::post('csv', 'V1\Explorer\PublisherController@csv')->name('csv');
                Route::post('sftp', 'V1\Explorer\PublisherController@sftp')->name('sftp');
                Route::post('snapshot', 'V1\Explorer\PublisherController@snapshot')->name('snapshot');
            });
        });

        Route::name('mssql.')->prefix('mssql')->group(function () {
            Route::get('', 'V1\Explorer\MicrosoftSqlServerController@list')->name('list');
            Route::get('{server}/databases', 'V1\Explorer\MicrosoftSqlServerController@databases')->name('databases');
            Route::get('{server}/databases/{database}/tables', 'V1\Explorer\MicrosoftSqlServerController@tables')->name('tables');
        });

        Route::name('views.')->prefix('views')->group(function () {
            Route::get('{view}/history', 'V1\Explorer\ViewController@history')->name('history');
            Route::get('{view}/history/{history}', 'V1\Explorer\ViewController@historyDetail')->name('historyDetail');
            Route::post('', 'V1\Explorer\ViewController@create')->name('create');
            Route::post('restore', 'V1\Explorer\ViewController@restore')->name('restore');
            Route::delete('{view}', 'V1\Explorer\ViewController@destroy')->name('destroy');
            Route::put('{view}', 'V1\Explorer\ViewController@update')->name('update');
            Route::put('{view}/refresh', 'V1\Explorer\ViewController@refresh')->name('refresh');
            Route::put('{view}/rename', 'V1\Explorer\ViewController@rename')->name('rename');
            Route::put('{view}/switch', 'V1\Explorer\ViewController@switch')->name('switch');
        });

        Route::post('files', 'V1\Explorer\ProjectController@attach')->name('attach');

        Route::delete('snapshots/{snapshot}', 'V1\Explorer\ProjectController@deleteSnapshot')->name('snapshots.delete');

        Route::get('sql-user', 'V1\Explorer\SqlUserController@show')->name('sql-user.show');
        Route::post('sql-user', 'V1\Explorer\SqlUserController@store')->name('sql-user.store');
        Route::put('sql-user', 'V1\Explorer\SqlUserController@update')->name('sql-user.update');
        Route::delete('sql-user', 'V1\Explorer\SqlUserController@destroy')->name('sql-user.destroy');
    });
});