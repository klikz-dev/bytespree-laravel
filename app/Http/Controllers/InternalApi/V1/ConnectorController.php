<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use App\Classes\Connector;
use App\Models\Integration;
use App\Models\PartnerIntegration;
use App\Models\Server;
use Illuminate\Http\Request;
use Exception;

class ConnectorController extends Controller
{
    public function list()
    {
        return response()->success(
            Integration::orderBy('name', 'asc')->get()->makeHidden(['logo'])
        );
    }

    public function show(Integration $connector)
    {
        $table_settings = [];
        $settings = [];

        foreach ($connector->settings as $setting) {
            $setting->properties = json_decode($setting->properties);
            $setting->is_private = filter_var($setting->is_private, FILTER_VALIDATE_BOOLEAN) === TRUE;
            $setting->is_secure = filter_var($setting->is_secure, FILTER_VALIDATE_BOOLEAN) === TRUE;
            $setting->is_required = filter_var($setting->is_required, FILTER_VALIDATE_BOOLEAN) === TRUE;
            $setting->added = FALSE;
            $setting->changed = FALSE;
            $setting->deleted = FALSE;

            if ($setting->data_type === 'boolean') {
                $setting->default_value = filter_var($setting->default_value, FILTER_VALIDATE_BOOLEAN) === TRUE;
            }

            $setting = $setting->toArray();

            $setting['id'] = 0;

            if ($setting['setting_type'] == 'table') {
                $table_settings[] = $setting;
            } else {
                $settings[] = $setting;
            }
        }

        $connector->is_active = filter_var($connector->is_active, FILTER_VALIDATE_BOOLEAN) === TRUE;
        $connector->use_tables = filter_var($connector->use_tables, FILTER_VALIDATE_BOOLEAN) === TRUE;
        $connector->use_hooks = filter_var($connector->use_hooks, FILTER_VALIDATE_BOOLEAN) === TRUE;
        $connector->fully_replace_tables = filter_var($connector->fully_replace_tables, FILTER_VALIDATE_BOOLEAN) === TRUE;

        $connector = $connector->toArray();

        $connector['server_id'] = NULL;
        $connector['table_settings'] = $table_settings;
        $connector['settings'] = $settings;
        $connector['server_id'] = NULL;

        $default_server = Server::where('is_default', TRUE)->first();

        if ($default_server) {
            $connector['server_id'] = $default_server->id;
        } else {
            $connector['server_id'] = '';
        }

        return response()->success($connector);
    }

    /**
     * Get a list of tables from our connector along with any settings that may have changed because of the request.
     */
    public function tables(Request $request, Integration $connector)
    {
        if ($request->has('settings') && is_array($request->settings)) {
            $settings = $request->settings;
            $keyed_settings = collect($request->settings)
                ->mapWithKeys(fn($setting) => [$setting['name'] => $setting['value']])
                ->toArray();
        } else {
            $database = PartnerIntegration::find($request->database_id);
            $settings = $database->settings
                ->map(function ($setting) {
                    $setting->name = $setting->setting->name;

                    return $setting;
                });
            $keyed_settings = collect($database->settings)
                ->mapWithKeys(fn($setting) => [$setting->setting->name => $setting->value])
                ->toArray();
        }

        if (! empty($connector->client_id)) {
            $keyed_settings['client_id'] = $connector->client_id;
        }

        if (! empty($connector->client_secret)) {
            $keyed_settings['client_secret'] = $connector->client_secret;
        }

        $caller = new Connector($connector, new PartnerIntegration());
        
        $resp = $caller->metadata('getTables', $keyed_settings);
        
        $tables = $resp->tables;

        if (! is_array($tables)) {
            $tables = [];
        }

        // If we got settings back via the connector, let's make sure we update our settings array with them, and return them in the response
        if (property_exists($resp, 'settings')) {
            $returned_settings = $resp->settings;

            if (is_object($returned_settings)) {
                $returned_settings = (array) $returned_settings;
            }

            if (is_array($returned_settings)) {
                foreach ($settings as $i => $setting) {
                    foreach ($returned_settings as $setting_key => $setting_value) {
                        logger()->info('if ' . $setting['name'] . ' == ' . $setting_key . ' : ' . $setting_value);
                        if ($setting['name'] == $setting_key) {
                            $settings[$i]['value'] = $setting_value;
                        }
                    }
                }
            }
        }

        $mapped_tables = array_map(function ($table) {
            return [
                'table_name' => $table,
                'used'       => FALSE
            ];
        }, $tables);

        return response()->success(
            ['tables' => $mapped_tables, 'settings' => $settings]
        );
    }

    /**
     * Handle a metadata request from the connector install page or update modal.
     */
    public function metadata(Request $request, Integration $connector)
    {
        $method_name = $request->input('method_name');
        $index = $request->input('index');
        $settings = [];

        if (! empty($request->input('table'))) {
            $settings['table'] = $request->input('table');
        }

        if (! empty($request->input('settings'))) {
            $orig_settings = $request->input('settings');

            if (! empty($orig_settings)) {
                foreach ($orig_settings as $value) {
                    $settings[$value['name']] = $value['value'];
                }
            }
        } elseif (! empty($request->input('control_id'))) {
            $pi = PartnerIntegration::find($request->input('control_id'));
            
            foreach ($pi->getKeyValueSettings() as $key => $value) {
                $settings[$key] = $value;
            }
        } else {
            return response()->error('No control id or settings sent in', compact('index'));
        }

        if (empty($connector->client_id) === FALSE) {
            $settings['client_id'] = $connector->client_id;
        }

        if (empty($connector->client_secret) === FALSE) {
            $settings['client_secret'] = $connector->client_secret;
        }

        $caller = new Connector($connector, new PartnerIntegration());
        
        try {
            $response = $caller->metadata($method_name, $settings);

            if ($response) {
                return response()->success(
                    ['index' => $index, ...(array) $response]
                );
            }

            return response()->error('Method returned false', compact('index'));
        } catch (Exception $e) {
            return response()->error('Method failed', compact('index'));
        }
    }
}
