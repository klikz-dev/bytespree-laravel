<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SavedData;
use App\Models\Manager\JenkinsBuild;
use App\Classes\Publishers\Run;
use Exception;
use DB;

class PublishOnce extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:once {data_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a publish job once';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $run = new Run;
        $build = JenkinsBuild::create([
            'jenkins_build_id' => $run->build_number,
            'job_path'         => config('services.jenkins.job_name'),
            'jenkins_home'     => config('services.jenkins.jenkins_home'),
            'started_at'       => now(),
            'parameters'       => [$this->argument('data_id')]
        ]);
        $run->runMonitor($build->id);

        $data = SavedData::where('guid', $this->argument('data_id'))->first();
        if (empty($data->data)) {
            throw new Exception("Saved data not found");
        }

        $data = (object) $data->data;
        $data->destination_options = (object) $data->destination_options;
        $data->destination = (object) $data->destination;

        $run->run($build->id, (object) $data, "publish");
    }
}
