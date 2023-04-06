<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Manager\JenkinsBuild;
use App\Models\Explorer\ProjectPublishingSchedule;
use App\Classes\Publishers\Run;
use Exception;
use DB;

class PublishSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:schedule {schedule_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes based on a scheduled job';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $run = new Run();
        $build = JenkinsBuild::create([
            'jenkins_build_id' => $run->build_number,
            'job_path'         => config('services.jenkins.job_name'),
            'jenkins_home'     => config('services.jenkins.jenkins_home'),
            'started_at'       => now(),
            'parameters'       => [$this->argument('schedule_id')]
        ]);
        $run->runMonitor($build->id, $this->argument('schedule_id'));

        $data = ProjectPublishingSchedule::find($this->argument('schedule_id'));
        if (empty($data)) {
            throw new Exception("Schedule not found");
        }

        $run->run($build->id, $data, "publish");
    }
}
