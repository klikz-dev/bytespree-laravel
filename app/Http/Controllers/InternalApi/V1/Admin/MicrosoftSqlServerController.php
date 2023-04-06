<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\Mssql;
use App\Models\Explorer\MicrosoftSqlServer;
use DB;
use Exception;

class MicrosoftSqlServerController extends Controller
{
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

    public function create(Request $request)
    {
        $request->validate([
            'hostname' => 'required',
            'port'     => 'required',
            'username' => 'required',
            'password' => 'required'
        ]);

        if (! Mssql::test($request->hostname, $request->username, $request->password, $request->port)) {
            return response()->error("Server credentials failed to connect.");
        }

        MicrosoftSqlServer::create($request->all());

        return response()->success();
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'hostname' => 'required',
            'port'     => 'required',
            'username' => 'required'
        ]);

        $server = MicrosoftSqlServer::find($id); 

        $data = [
            "hostname" => $request->hostname,
            "username" => $request->username,
            "port"     => $request->port
        ];

        $data["password"] = empty($request->password) ? $server->data['password'] : $request->password;

        if (! Mssql::test($request->hostname, $request->username, $request->password, $request->port)) {
            return response()->error("Server credentials failed to connect.");
        }
        
        $server->update(["data" => $data]);

        return response()->success();
    }

    public function destroy(int $id)
    {
        MicrosoftSqlServer::find($id)->delete();

        return response()->empty();
    }

    public function testServer(string $hostname, string $username, string $password, string $port)
    {
        $config = array_merge(
            config('database.connections.sqlsrv'),
            [
              'host'     => $hostname,
              'username' => $username,
              'password' => $password,
              'port'     => $port,
              'database' => '',
            ]
        );

        config(['database.connections.mssql:test_conn' => $config]);

        try {
            DB::connection('mssql:test_conn')->getPdo();

            return TRUE;
        } catch ( Exception $e) {
            return FALSE;
        }
    }

    public function databases(MicrosoftSqlServer $server)
    {
        $databases = Mssql::connect($server->data)->getDatabases();
    
        return response()->success($databases);
    }

    public function tables(MicrosoftSqlServer $server, string $database)
    {
        $tables = Mssql::connect($server->data)
            ->setDatabase($database)
            ->getTables();

        return response()->success($tables);
    }
}
