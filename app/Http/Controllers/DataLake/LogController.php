<?php

namespace App\Http\Controllers\DataLake;

use App\Http\Controllers\Controller;
use App\Models\Manager\JenkinsBuild;
use App\Models\Manager\JenkinsBuildOutput;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;

class LogController extends Controller
{
    /**
     * Download a log file via the browser
     */
    public function download(Request $request, int $id, JenkinsBuild $build)
    {
        $output = JenkinsBuildOutput::where('build_id', $build->id)->first();

        if ($output) {
            $text = $output->console_text;
        } else {
            $database = PartnerIntegration::find($build->parameters[4]);

            $text = app(IntegrationJenkins::class)->getConsoleLogTextByBuildId(
                $database->database,
                app('environment')->getTeam(),
                $build->job_name,
                $build->jenkins_build_id
            );

            if (empty($text)) {
                session()->flash('flash_error_message', 'File download has failed.');

                return redirect('/data-lake');
            }
        }

        return response()->streamDownload(function () use ($text) {
            echo $text;
        }, $build->job_name . '_' . date("YmdHis") . '.txt');
    }
}
