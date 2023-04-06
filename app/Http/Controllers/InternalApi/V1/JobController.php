<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Manager\JenkinsBuild;
use Exception;

class JobController extends Controller
{
    public function list()
    {
        $jobs = JenkinsBuild::with('database')->unfinished()->get()->map(function ($job) {
            $job_path_info = explode('/', $job->job_path);

            return (object) [
                'connector'  => $job->database?->integration?->name,
                'database'   => $job->database?->database,
                'id'         => $job->id,
                'table_name' => array_pop($job_path_info),
                'started_at' => $job->started_at,
                'job_type'   => $job->job_type,
            ];
        });

        return response()->success($jobs);
    }

    public function output(Request $request, JenkinsBuild $job)
    {
        $job_path_arr = explode('/', $job->job_path);

        $path = $job->jenkins_home . "/jobs/";
        
        foreach ($job_path_arr as $index => $value) {
            if ($index + 1 == count($job_path_arr)) {
                $job_name = $value;
                $path .= $value;
            } else {
                $path .= $value . "/jobs/";
            }
        }

        $path .= "/builds/" . $job->jenkins_build_id;

        try {
            $log = file_get_contents($path . "/log");
            $log = strstr($log, "[$job_name]");
            $log = preg_replace('/^.+\n/', '', $log);
        } catch (Exception $e) {
            return response()->error('Job log could not be loaded.', [], 500);
        }

        return response()->success($log);
    }

    public function stop(Request $request, JenkinsBuild $job)
    {
        // This is to be revisited later, if we add back this functionality. The icon to call this method is commented out in the view.

        return response()->success();

        // $build = $this->DW_JenkinsBuilds->getById($build_id);

        // $job_path_arr = explode('/', $build["job_path"]);
        // foreach ($job_path_arr as $index => $value) {
        //     if ($index + 1 == count($job_path_arr)) {
        //         $job_name = $value;
        //         $build_url .= $value;
        //     } else {
        //         $build_url .= $value . "/job/";
        //     }
        // }
        // $build_url .= "/" . $build["jenkins_build_id"];

        // try {
        //     $executers = $this->JenkinsModel->getExecuters();

        //     foreach ($executers as $executer) {
        //         if ($executer->getNumber() == 0) {
        //             continue;
        //         }

        //         if (strpos($executer->getBuildUrl(), $build_url)) {
        //             $executer->stop();
        //             // Sleeps to let jenkins have time to set the files
        //             sleep(5);
        //             $this->load->controller("Services/DW_JenkinsBuild", NULL, "DW_JenkinsBuild");
        //             $this->DW_JenkinsBuild->monitor($build_id, TRUE);

        //             return $this->_sendAjax("ok", "Job been aborted");
        //         }
        //     }
        // } catch (Exception $e) {
        //     $this->logging->error(
        //         'Failed to abort the job.',
        //         [
        //             'build_id'  => $build_id,
        //             'build_url' => $build_url
        //         ]
        //     );
        //     $this->_sendAjax("error", "Unable to stop job", [], 500);

        //     return;
        // }

        // $this->_sendAjax("error", "This job has already finished", [], 500);
    }
}
