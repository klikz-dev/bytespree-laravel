<?php

namespace App\Classes;

use App\Models\{Integration, IntegrationSetting, PartnerIntegration, PartnerIntegrationSetting};
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Exception;
use SingerPhp\SingerParser;
use SingerPhp\Messages\MetaMessage;

class Connector
{
    protected $database;
    protected $integration;

    public function __construct(Integration $integration, PartnerIntegration $database)
    {
        $this->integration = $integration;
        $this->database = $database;
    }
    
    /**
     * Install connector for current team
     *
     * @param  int         $connector_id The ID of the connector in Orchestration
     * @return Integration
     * @throws Exception
     */
    public function install($connector_id)
    {
        $connector = app('orchestration')->getConnector($connector_id);
        if (empty($connector)) {
            throw new Exception('Connector to install not found', 404);
        }

        $connector = (object) $connector;

        $class_name = str_replace(' ', '', $connector->name);

        // We need to check to see if we'll be replacing an existing connector
        // or if we'll be creating a new one
        $already_installed = Integration::where('name', $connector->name)->first();

        if ($already_installed) {
            // this shouldn't happen
            throw new Exception('Connector is already installed', 400);
        }

        $created_connector = Integration::create([
            'name'                   => $connector->name,
            'description'            => $connector->description,
            'instructions'           => $connector->instructions,
            'known_limitations'      => $connector->known_limitations ?? [],
            'use_tables'             => filter_var($connector->use_tables ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
            'use_hooks'              => filter_var($connector->use_hooks ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
            'class_name'             => $class_name,
            'fully_replace_tables'   => filter_var($connector->full_replace ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
            'is_oauth'               => filter_var($connector->is_oauth ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
            'oauth_url'              => $connector->oauth_url ?? NULL,
            'is_unified_application' => filter_var($connector->is_unified_application ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
            'client_id'              => $connector->client_id,
            'client_secret'          => $connector->client_secret,
            'version'                => $connector->version,
            'logo'                   => base64_decode($connector->logo)
        ]);

        /*
        TODO: Figure out connector migrations
        if (! empty($migrations)) {
            $this->runMigrations($created_connector->id, $migrations);
        }
        */

        if (empty($connector->settings)) {
            // Connector with no settings
            return $created_connector;
        }

        foreach ($connector->settings as $setting) {
            if (is_array($setting)) {
                $setting = (object) $setting;
            }

            if (empty($setting->is_private)) {
                $setting->is_private = FALSE;
            }

            IntegrationSetting::create([
                'integration_id'   => $created_connector->id,
                'name'             => $setting->name,
                'friendly_name'    => $setting->friendly_name,
                'ordinal_position' => $setting->ordinal_position ?? 0,
                'data_type'        => $setting->data_type ?? NULL,
                'default_value'    => $setting->default_value ?? NULL,
                'description'      => $setting->description,
                'is_secure'        => filter_var($setting->is_secure ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                'is_required'      => filter_var($setting->is_required ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                'is_private'       => filter_var($setting->is_private ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                'setting_type'     => $setting->setting_type,
                'properties'       => $setting->properties ?? NULL,
                'options'          => $setting->options ?? NULL,
                'required_if'      => $setting->required_if ?? NULL,
                'visible_if'       => $setting->visible_if ?? NULL,
            ]);
        }

        return $created_connector;
    }
    
    /**
     * Update connector for current team
     *
     * @param  int         $connector_id The ID of the connector in Orchestration
     * @return Integration
     * @throws Exception
     */
    public function update($connector_id)
    {
        $connector = (object) app('orchestration')->getConnector($connector_id);
        if (empty($connector)) {
            throw new Exception('Connector to install not found', 404);
        }

        $existing_connector = Integration::where('name', $connector->name)->first();
        if (empty($existing_connector)) {
            throw new Exception('Connector to update not found', 404);
        }

        $existing_connector
            ->update([
                'name'                   => $connector->name,
                'description'            => $connector->description,
                'instructions'           => $connector->instructions,
                'known_limitations'      => $connector->known_limitations,
                'use_tables'             => filter_var($connector->use_tables ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                'use_hooks'              => filter_var($connector->use_hooks ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                'fully_replace_tables'   => filter_var($connector->full_replace ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                'is_oauth'               => filter_var($connector->is_oauth ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                'oauth_url'              => $connector->oauth_url ?? NULL,
                'is_unified_application' => filter_var($connector->is_unified_application ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                'client_id'              => $connector->client_id ?? NULL,
                'client_secret'          => $connector->client_secret ?? NULL,
                'version'                => $connector->version,
                'logo'                   => base64_decode($connector->logo)
            ]);

        if (empty($connector->settings)) {
            foreach ($existing_connector->settings as $setting) {
                $setting->delete();
            }

            // Connector with no settings
            return $existing_connector;
        }

        $current_settings = [];
        foreach ($existing_connector->settings as $setting) {
            $current_settings[] = strtolower($setting->name);
        }

        $new_settings = [];
        foreach ($connector->settings as $setting) {
            $new_settings[] = $setting['name'];
        }

        foreach ($current_settings as $setting) {
            if (! in_array($setting, $new_settings)) {
                $integration_setting = IntegrationSetting::where('integration_id', $existing_connector->id)
                    ->where('name', $setting)
                    ->first();
                    
                if (empty($integration_setting)) {
                    continue;
                }

                PartnerIntegrationSetting::where('integration_setting_id', $integration_setting->id)
                    ->delete();

                $integration_setting->delete();
            }
        }

        /*
        TODO: Figure out connector migrations
        if (! empty($migrations)) {
            $this->runMigrations($created_connector->id, $migrations);
        }
        */
    
        foreach ($connector->settings as $setting) {
            $setting = (object) $setting;
            $data_type = $setting->data_type ?? NULL;
            $default_value = $setting->default_value ?? NULL;

            $new_setting = IntegrationSetting::updateOrCreate(
                [
                    'integration_id' => $existing_connector->id,
                    'name'           => $setting->name
                ],
                [
                    'integration_id'   => $existing_connector->id,
                    'name'             => $setting->name,
                    'friendly_name'    => $setting->friendly_name,
                    'ordinal_position' => $setting->ordinal_position ?? 0,
                    'data_type'        => $data_type,
                    'default_value'    => $default_value,
                    'description'      => $setting->description,
                    'is_secure'        => filter_var($setting->is_secure ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                    'is_required'      => filter_var($setting->is_required ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                    'is_private'       => filter_var($setting->is_private ?? FALSE, FILTER_VALIDATE_BOOLEAN) === TRUE,
                    'setting_type'     => $setting->setting_type,
                    'properties'       => $setting->properties ?? NULL,
                    'options'          => $setting->options ?? NULL,
                    'required_if'      => $setting->required_if ?? NULL,
                    'visible_if'       => $setting->visible_if ?? NULL,
                ]
            );

            if (! in_array($setting->name, $current_settings)) {
                foreach ($existing_connector->databases as $database) {
                    if ($data_type === 'boolean') {
                        $value = filter_var($default_value, FILTER_VALIDATE_BOOLEAN) === TRUE;
                    } else {
                        $value = empty($default_value) && $default_value != 0 ? NULL : $default_value;
                    }
                    
                    PartnerIntegrationSetting::create([
                        'partner_integration_id' => $database->id,
                        'integration_setting_id' => $new_setting->id,
                        'value'                  => $value
                    ]);
                }
            }
        }
    
        return $existing_connector;
    }

    /**
     * Make a metadata call to the connector
     * 
     * @param string $method   The name of the method to call
     * @param array  $settings An array of settings to be passed to the connector via the --input option
     */
    public function metadata(string $method, array $args = [])
    {
        $connector_tap = config('app.connector_path') . "/taps/{$this->integration->safe_name}/{$this->integration->version}/tap.php";

        logger()->debug("Calling metadata method {$method} on connector {$this->integration->safe_name} version {$this->integration->version}");

        $parameters = [
            'php',
            $connector_tap,
            '--metadata',
            "--method={$method}",
            "--input=" . json_encode((object) $args)
        ];

        logger()->debug("Parameters used: " . implode(' ', $parameters));

        $process = new Process($parameters);

        $process->setWorkingDirectory(dirname($connector_tap));

        try {
            $process->mustRun();

            $output = $process->getOutput();
        } catch (ProcessFailedException $exception) {
            throw new Exception($exception->getMessage());
        }

        $message = app(SingerParser::class)->parseMessage($output);

        if ($message instanceof MetaMessage) {
            return $message->metadata;
        }
        
        throw new Exception("Unexpected response from connector.");
    }

    /**
     * Run the test method for the connector, passing in any args necessary for its run
     * 
     * @param  array $args An array of args to be passed to the connector via the --input option
     * @return bool  TRUE if the test was successful, FALSE otherwise
     */
    public function test(array $args = []): bool
    {
        try {
            $response = $this->metadata('test', $args);

            if (is_object($response)) {
                if (property_exists($response, 'test_result')) {
                    return $response->test_result === TRUE;
                }
            }

            return FALSE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Get our refresh token, or try to
     * 
     * @param  array $data The data to be passed to the connector
     * @return mixed
     */
    public function getRefreshToken(array $data = [])
    {
        $response = $this->metaData('getRefreshToken', $data);
        
        if (is_object($response)) {
            if (property_exists($response, 'refresh_token')) {
                if (is_object($response->refresh_token)) {
                    return (array) $response->refresh_token;
                }

                return $response->refresh_token;
            }
        }

        return FALSE;
    }
}
