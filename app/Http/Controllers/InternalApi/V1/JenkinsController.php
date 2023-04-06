<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\IntegrationJenkins;

class JenkinsController extends Controller
{
    public function __construct()
    {
        $this->integration_jenkins = new IntegrationJenkins();
    }

    public function check(string $job_name)
    {
        $status = app('jenkins')->checkFunctionStatus(app('environment')->getTeam(), $job_name);

        return response()->success($status);
    }
}
