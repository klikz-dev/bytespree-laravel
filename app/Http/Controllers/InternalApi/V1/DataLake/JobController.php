<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Classes\IntegrationJenkins;
use App\Http\Controllers\Controller;
use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationTable;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function list(PartnerIntegration $database)
    {
        $jobs = PartnerIntegrationTable::getByDatabaseIdWithBuildStatus($database->id)
            ->map(function ($job) {
                $job->is_active = filter_var($job->is_active, FILTER_VALIDATE_BOOLEAN) === TRUE;
                $job->is_running = filter_var($job->is_running, FILTER_VALIDATE_BOOLEAN) === TRUE;
                $job->is_pending = FALSE;

                return $job;
            });

        return response()->success($jobs);
    }

    public function run(Request $request, PartnerIntegration $database)
    {
        $job_name = $request->job;

        // $check_perms = $this->checkPerms("run", $control_id, 'warehouse');
        // if (! $check_perms) {
        //     return;
        // }

        if (! $database->integration->use_tables) {
            $job_name = 'sync';
        }

        if (app(IntegrationJenkins::class)->runIntegration($job_name, $database)) {
            return response()->success([], "Sync started");
        }
        
        return response()->error("Sync already running");
    }
}
