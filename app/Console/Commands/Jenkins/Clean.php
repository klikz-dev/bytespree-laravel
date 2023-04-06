<?php

namespace App\Console\Commands\Jenkins;

use Illuminate\Console\Command;
use App\Models\Manager\JenkinsBuild;
use App\Models\Manager\JenkinsBuildOutput;
use DB;
use Exception;

class Clean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jenkins:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search Jenkins build table and resolve any unfinished Jenkins builds that are complete';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return $this->cleanBuilds();
    }

    public function cleanBuilds()
    {
        $unfinished_builds = JenkinsBuild::unfinished()->get();

        foreach ($unfinished_builds as $unfinished_build) {
            $this->monitor($unfinished_build, TRUE);
        }
        
        // TODO websockets

        return 0;
    }

    /**
     * Monitors the jenkins build until it ends
     *
     * @param  JenkinsBuild $build        The unfinished build
     * @param  bool         $ignore_sleep If the monitor data retrys or not
     * @return void
     */
    public function monitor($build, $ignore_sleep = FALSE)
    {
        $data = JenkinsBuild::monitorData(
            $build->jenkins_build_id,
            $build->jenkins_home,
            $build->job_path,
            $ignore_sleep
        );

        JenkinsBuild::find($build->id)
            ->update([
                "job_name"           => $data->job_name,
                "result"             => $data->result,
                "build_timestamp"    => $data->timestamp,
                "estimated_duration" => $data->duration,
                "finished_at"        => DB::raw("now()")
            ]);

        try {
            $log = file_get_contents($data->path . "/log");

            JenkinsBuildOutput::create([
                "build_id"      => $build->id,
                "console_text"  => $log,
                "is_compressed" => FALSE
            ]);
        } catch (Exception $e) {
            logger()->info(
                "No output was found for $build->id",
                $build->toArray()
            );
        }

        // TODO websockets
    }
}