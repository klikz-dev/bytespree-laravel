<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Server;
use App\Classes\Database\Connection;
use DB;
use Exception;

class MonitorController extends Controller
{
    public function servers()
    {
        $status = 200;
        $server_statuses = [];
        $servers = Server::whereNotNull('server_provider_configuration_id')->get();
        $query = <<<SQL
            SELECT * FROM "information_schema"."tables"
            SQL;

        try {
            DB::select($query);
            $server_statuses['application'] = TRUE;
        } catch (Exception $e) {
            $server_statuses['application'] = FALSE;
            $status = 500;
        }

        foreach ($servers as $server) {
            try {
                $connection = Connection::connectServer($server);
                $connection->select($query);
                $server_statuses[$server->name] = TRUE;
            } catch (Exception $e) {
                $server_statuses[$server->name] = FALSE;
                $status = 500;
            }
        }

        return response()->json($server_statuses, $status);
    }

    public function databases()
    {
        $status = 204;
        $database_statuses = [];
        $query = <<<SQL
            SELECT * 
            FROM __bytespree.v_dw_jenkins_builds__latest_results_by_database
            WHERE result_code >= 30; 
            SQL;

        $database_results = collect(DB::select($query));

        if ($database_results->count() > 0) {
            $status = 500;
            foreach ($database_results as $result) {
                $database_statuses[] = [
                    'database_id' => $result->database_id,
                    'tables'      => $result->failed_jobs
                ];
            }
        }

        return response()->json($database_statuses, $status);
    }
}
