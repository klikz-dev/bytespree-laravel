<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Classes\IntegrationJenkins;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\Manager\JenkinsBuild;
use App\Models\Manager\JenkinsBuildOutput;
use Auth;
use App\Classes\Postmark;
use App\Attributes\Can;

class LogController extends Controller
{
    #[Can(permission: 'view_logs', product: 'datalake', id: 'database.id')]
    public function list(Request $request, PartnerIntegration $database)
    {
        $query = $database->logs();

        if ($request->filled('table')) {
            $query->where('job_name', $request->table);
        }

        if ($request->filled('status')) {
            $query->where('result', $request->status);
        } else {
            $query->whereIn('result', ['SUCCESS', 'FAILURE', 'ABORTED']);
        }

        if ($request->filled('type')) {
            $query->where('parameters->3', $request->type);
        }
        
        $logs = $query->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        return response()->success([
            'logs'   => $logs,
            'tables' => $database->tables,
        ]);
    }

    public function show(Request $request, PartnerIntegration $database, JenkinsBuild $build)
    {
        $output = JenkinsBuildOutput::where('build_id', $build->id)->first();

        if ($output) {
            $text = $output->console_text;
            $got_all = TRUE;
        } else {
            $got_all = FALSE;

            $database = PartnerIntegration::find($build->parameters[4]);

            $text = app(IntegrationJenkins::class)->getConsoleLogTextByBuildId(
                $database->database,
                app('environment')->getTeam(),
                $build->job_name,
                $build->jenkins_build_id
            );

            if (empty($text)) {
                return response()->error('Log could not be retrieved');
            }
        }

        if ($request->has('email')) {
            $data = [
                "table"     => $build->job_name,
                "timestamp" => $build->build_timestamp_formatted,
                "text"      => $text,
                "got_all"   => $got_all,
                "username"  => Auth::user()->name
            ];

            if (Postmark::send(Auth::user()->email, "integration-log", $data)) {
                return response()->success();
            }

            return response()->error("Failed to email integration log");
        }

        if ($got_all) {
            return response()->success(array_merge($output->toArray(), ['got_all' => TRUE]));
        }

        return response()->success($text);
    }
}
