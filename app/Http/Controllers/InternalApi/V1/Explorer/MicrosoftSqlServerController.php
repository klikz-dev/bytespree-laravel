<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\Project;
use App\Models\Explorer\MicrosoftSqlServer;
use App\Attributes\Can;
use App\Classes\Mssql;
use DB;

class MicrosoftSqlServerController extends Controller
{
    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function list()
    {
        $servers = array_map(function ($server) {
            if (! empty($server['data']['password'])) {
                unset($server['data']['password']);
            }

            return $server;
        }, MicrosoftSqlServer::get()->toArray());

        return response()->success($servers);
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function databases(Project $project, MicrosoftSqlServer $server)
    {
        $server->data = (object) $server->data;
        $config = [
            'host'     => $server->data->hostname,
            'port'     => $server->data->port,
            'username' => $server->data->username,
            'password' => $server->data->password
        ];

        $mssql = new Mssql($config);

        return response()->success($mssql->getDatabases());
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function tables(Project $project, MicrosoftSqlServer $server, string $database)
    {
        $server->data = (object) $server->data;
        $config = [
            'host'     => $server->data->hostname,
            'port'     => $server->data->port,
            'username' => $server->data->username,
            'password' => $server->data->password,
            'database' => $database
        ];

        $mssql = new Mssql($config);

        return response()->success($mssql->getTables());
    }
}
