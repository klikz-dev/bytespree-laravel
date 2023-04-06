<?php

namespace App\Console\Commands\Server;

use Illuminate\Console\Command;
use App\Models\SavedData;
use App\Models\ServerProviderConfiguration;
use App\Models\User;
use App\Classes\Postmark;
use App\Classes\ServerProviders\DigitalOcean;

class Add extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:add {data_id? : The ID of the saved data to use for building the server} {--id= : The ID of the server configuration to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This adds a serve to Digital Ocean and Bytespree.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $data_id = $this->argument('data_id');

        if (empty($data_id)) {
            return $this->addServerById();
        }

        $saved_data = SavedData::where('guid', $data_id)->first();

        if (! $saved_data) {
            $this->error('No saved data found with that ID');

            return 1;
        }

        $provider_configuration = ServerProviderConfiguration::whereHas('provider')
            ->with('provider')
            ->where('id', $saved_data->data['server_provider_configuration_id'])
            ->first();

        if (empty($provider_configuration)) {
            $this->error('No provider configuration found with that ID');

            return 1;
        }

        $class = str_replace(' ', '', $provider_configuration->provider->name);
        $cls = app('App\\Classes\\ServerProviders\\' . $class);

        $server = $cls->create(
            $saved_data->data['region'],
            $saved_data->data['name'],
            $provider_configuration,
            $saved_data->data['groups']
        );

        if ($server) {
            $subject = $provider_configuration->provider->name . " Server Created";
            $hostname = $server->hostname;
            $failed = FALSE;

            if (array_key_exists('is_default', $saved_data->data) && $saved_data->data['is_default'] === TRUE) {
                $server->updateDefault(TRUE);
            }
        } else {
            $hostname = "";
            $subject = "Creating the {$provider_configuration->provider->name} Server Failed";
            $failed = TRUE;
        }

        $email_addresses = User::isAdmin()->pluck('email')->toArray();

        $data = [
            "failed"   => $failed,
            "name"     => $saved_data['name'],
            "hostname" => $hostname,
            "subject"  => $subject
        ];

        Postmark::send($email_addresses, "digital-ocean-created", $data);
        
        if ($failed) {
            return 1;
        }

        return 0;
    }

    public function addServerById()
    {
        $id = (int) ((empty($this->option('id'))) ? '0' : $this->option('id'));

        if ($id === 0) {
            $this->error('Invalid configuration ID provided');

            return 1;
        }

        $configuration = (object) app('orchestration')->getServerProviderConfigurationById($id);

        // Perform a TEMPORARY local look up, until we fully migrate away from Bytespree storing server provider configurations
        $server_configuration = ServerProviderConfiguration::whereHas('provider')
            ->with('provider')
            ->where('slug', $configuration->slug)
            ->where('nodes', $configuration->nodes)
            ->first();

        if (empty($server_configuration)) {
            logger()->error(
                'Server provider configuration not found.',
                [
                    'slug'  => $configuration->slug,
                    'nodes' => $configuration->nodes
                ]
            );

            return 1;
        }

        app(DigitalOcean::class)->create(
            'nyc1', // TODO: Doesn't matter at this point in time, but we should probably make this configurable
            app('environment')->getTeam() . '-default', // Default the "name" as {team name}-default
            $server_configuration,
            [], // No default configuration
            FALSE // Don't send out emails
        );

        return 0;
    }
}
