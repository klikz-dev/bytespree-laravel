<?php

namespace App\Console\Commands\Jenkins;

use Illuminate\Console\Command;
use App\Models\Manager\JenkinsBuild;
use App\Models\Manager\JenkinsBuildOutput;
use App\Models\Explorer\ProjectPublishingSchedule;
use DB;

class Monitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jenkins:monitor {build_id} {publisher_id?} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a monitor to check if a jenkins job has finished';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        logger()->info("Monitoring jenkins build " . $this->argument('build_id'));

        $build = JenkinsBuild::find($this->argument('build_id'));
        $data = JenkinsBuild::monitorData($build->jenkins_build_id, $build->jenkins_home, $build->job_path, FALSE);

        $log = file_get_contents($data->path . "/log");
        JenkinsBuild::find($build->id)
            ->update([
                "job_name"           => $data->job_name,
                "result"             => $data->result,
                "build_timestamp"    => $data->timestamp,
                "estimated_duration" => $data->duration,
                "finished_at"        => DB::raw("now()")
            ]);

        JenkinsBuildOutput::create([
            "build_id"      => $build->id,
            "console_text"  => $log,
            "is_compressed" => FALSE
        ]);

        if (! empty($this->argument('publisher_id'))) {
            ProjectPublishingSchedule::find($this->argument('publisher_id'))
                ->update([
                    "status"   => $data->result,
                    "last_ran" => date("Y-m-d H:i:s", $data->timestamp / 1000)
                ]);
        }

        // todo websockets
        // $this->counts->getJobCounts();
    }
}
