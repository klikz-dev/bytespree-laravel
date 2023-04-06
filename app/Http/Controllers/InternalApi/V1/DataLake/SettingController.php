<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationSetting;
use App\Attributes\Can;
use App\Classes\Connector;

class SettingController extends Controller
{
    // todo: These are integration settings. We're not using them at the moment due to the intended migration to the new connectors
    public function test(Request $request, PartnerIntegration $database)
    {
        if ($database->integration_id == 0 || empty($database->integration_id)) {
            return response()->success([], "Empty database");
        }

        $integration = $database->integration;
        $settings = [];
        $data = $request->toArray();

        if (! empty($data) && is_array($data)) {
            $settings = collect($data)->mapWithKeys(fn($item) => [$item['name'] => $item['value']])->toArray();
        }

        foreach ($integration->settings as $setting) {
            if ($setting->is_required && $setting->setting_type != 'table' && $setting->is_private !== TRUE) {
                if (! array_key_exists($setting->name, $settings) || empty($settings[$setting->name])) {
                    return response()->error('You have not entered all of the Connector Settings! You may need to reauthorize Bytespree.');
                }
            }
        }

        if (! empty($integration->client_id)) {
            $settings['client_id'] = $integration->client_id;
        }

        if (! empty($integration->client_secret)) {
            $settings['client_secret'] = $integration->client_secret;
        }

        // Test with the connector...
        $caller = new Connector($integration, $database);

        if (! $caller->test($settings)) {
            response()->error(message: "The credentials you provided are invalid. Please update the credentials.", data: [], status_code: 400);

            return response()->error("The credentials you provided are invalid. Please update the credentials before creating the database.");
        }

        return response()->success([], "The settings provided are valid");
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function update(Request $request, PartnerIntegration $database)
    {
        $database->update([
            'notificants' => $request->notificants,
            'retry_syncs' => $request->retry_syncs,
        ]);

        $database->server->update([
            'alert_threshold' => $request->alert_threshold
        ]);

        $settings = $request->settings;

        foreach ($settings as $setting) {
            $values = [
                'partner_integration_id' => $database->id,
                'integration_setting_id' => $setting['integration_setting_id'],
                'value'                  => $setting['value'],
            ];

            PartnerIntegrationSetting::updateOrCreate(
                ['id' => $setting['id']],
                $values,
            );
        }

        return response()->success([], 'Settings have been saved');
    }

    public function list(Request $request, PartnerIntegration $database)
    {
    }

    public function show(Request $request, PartnerIntegration $database, PartnerIntegrationSetting $setting)
    {
        return response()->success(['value' => $setting->value]);
    }
}
