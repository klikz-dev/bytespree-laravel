<?php

namespace App\Console\Commands\Connector;

use App\Classes\Connector;
use Illuminate\Console\Command;
use Exception;

class Install extends Command
{
    protected $signature = 'connector:install
                           {connector_id : The ID in Orchestration of the connector being installed}
                           {--handle= : The handle of the user installing connector}
                           {--team= : The team installing connector}';

    protected $description = 'Install a connector using ID from Orchestration.';

    public function handle()
    {
        $connector_id = (int) $this->argument('connector_id');
        $user_handle = $this->option('handle');
        $team_name = $this->option('team');

        return $this->addConnector($connector_id, $user_handle, $team_name);
    }

    protected function addConnector($connector_id, $user_handle, $team_name)
    {
        try {
            $connector = app(Connector::class)->install($connector_id);
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }

        if (! empty($user_handle) && ! empty($team_name)) {
            app('orchestration')->addNotification(
                $user_handle,
                $team_name,
                "{$connector->name} Connector is Ready",
                "The connector {$connector->name} has been installed in your Bytespree team.",
                "success"
            );
        }

        return 0;
    }
}