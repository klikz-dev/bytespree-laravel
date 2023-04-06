<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationSetting;
use App\Models\SavedData;
use Illuminate\Http\Request;
use App\Classes\Connector;
use Exception;

class OauthController extends Controller
{
    /**
     * Send an OAuth request to the connector. Store the state, all that jazz.
     */
    public function send(Request $request)
    {
        if ($request->from === 'dataLake') {
            $partner_integration = PartnerIntegration::find($request->integration_id);
            $integration = $partner_integration->integration;
        } elseif ($request->from === 'database') {
            $integration = Integration::find($request->integration_id);
        }

        $call = $request->all();

        $call['team'] = app('environment')->getTeam();

        $saved_data = SavedData::create([
            'data'       => $request->all(),
            'controller' => $request->from
        ]);

        $settings = $request->settings;
        $url = $request->url;

        $state = app('environment')->getTeam() . '_' . $saved_data->guid;
        $url = str_replace("{{state}}", $state, $url);

        if ($integration->is_unified_application === TRUE) {
            $url = str_replace("{{client_id}}", $integration->client_id, $url);
            $url = str_replace("{{client_secret}}", $integration->client_secret, $url);
        }

        $url = str_replace('{{orchestration_url}}', urlencode(rtrim(config('orchestration.url'), '/')), $url);

        $regex = '/(\{\{)((?<variable>(\w+))|(?<expression>((\w|\|\|)+)))(\}\})/m';

        $url = preg_replace_callback($regex, function ($match) use ($settings) {
            if (! empty($match['expression'])) {
                $vars = explode("||", $match['expression']);
                foreach ($vars as $var) {
                    if (array_key_exists($var, $settings) && ! empty($settings[$var])) {
                        return $settings[$var];
                    }
                }

                return '';
            } elseif (array_key_exists($match['variable'], $settings)) {
                return $settings[$match['variable']];
            }

            return '';
        }, $url);

        return response()->success(
            data: [
                'url'   => $url,
                'state' => $saved_data->guid,
            ],
            message: "OAuth record created"
        );
    }

    public function get(Request $request, string $code, string $guid)
    {
        $data = SavedData::where('guid', $guid)->first();
        
        if (! $data) {
            return response()->error("An error has occurred in the oauth process");
        }

        $from = ucfirst($data["controller"]);

        if ($code != 'false') {
            $code = base64_decode($code);
            $data = (array) $data->data;
            $method = "process" . $from;
            $data = $this->$method($data, $code);
            $vue_state = $data['vue_state'];
        } else {
            // Warehouse sends different data then Database and needs to be handled differently upon failure
            $vue_state = empty($data->data['vue_state']) ? $data->data : $data->data['vue_state'];
        }

        return response()->success($vue_state, 'Oauth record retrieved');
    }

    public function processDatabase($data, $code)
    {
        $integration = Integration::find($data['integration_id']);

        if ($integration->is_unified_application) {
            $data["settings"]["client_id"] = $integration->client_id;
            $data["settings"]["client_secret"] = $integration->client_secret;
        }

        $data["settings"]["access_token"] = $code;
        $data["settings"]["oauth_callback"] = rtrim(config('orchestration.url'), '/') . '/OAuth/callback';
        $integration_details = $data["vue_state"]["integration_details"];

        $connector = new Connector($integration, new PartnerIntegration());

        try {
            $refresh_token = $connector->getRefreshToken($data["settings"]);
        } catch (Exception $e) {
            $refresh_token = [
                "error"             => "500",
                "error_description" => "Failed to connect Bytespree with " . $integration->name
            ];
        }

        if (! empty($refresh_token["error"])) {
            $data["vue_state"]["error_message"] = $refresh_token["error"] . ": " . $refresh_token["error_description"];

            return $data;
        }

        $data['settings']['refresh_token'] = $refresh_token['refresh_token'];
        $data['settings']['access_token'] = $refresh_token['access_token'] ?? '';

        $settings = $integration_details["settings"];

        foreach ($settings as $key => $value) {
            if ($value["name"] == "refresh_token") {
                $settings[$key]["value"] = $data["settings"]["refresh_token"];
            }

            if ($value['name'] == 'access_token' && ! empty($data['settings']['access_token'])) {
                $settings[$key]['value'] = $data['settings']['access_token'];
            }
        }

        $integration_details["settings"] = $settings;
        $data["vue_state"]["integration_details"] = $integration_details;

        return $data;
    }

    public function processDataLake($data, $code)
    {
        $database = PartnerIntegration::find($data['integration_id']);

        if ($database->integration->is_unified_application) {
            $data["settings"]["client_id"] = $database->integration->client_id;
            $data["settings"]["client_secret"] = $database->integration->client_secret;
        }

        $data["settings"]["access_token"] = $code;
        $data["settings"]["oauth_callback"] = rtrim(config('orchestration.url'), '/') . '/OAuth/callback';
        $selectedDatabase = $data["vue_state"]["selectedDatabase"];

        $connector = new Connector($database->integration, new PartnerIntegration());

        try {
            $refresh_token = $connector->getRefreshToken($data["settings"]);
        } catch (Exception $e) {
            $refresh_token = [
                "error"             => "500",
                "error_description" => "Failed to connect Bytespree with " . $database->integration->name
            ];
        }

        if (! empty($refresh_token["error"])) {
            $data["vue_state"]["error_message"] = $refresh_token["error"] . ": " . $refresh_token["error_description"];

            return $data;
        }

        $data['settings']['refresh_token'] = $refresh_token['refresh_token'];
        $data['settings']['access_token'] = $refresh_token['access_token'] ?? '';

        $settings = $selectedDatabase["settings"];

        foreach ($settings as $key => $value) {
            $changed = $settings[$key]["changed"] ?? FALSE;

            if ($value["name"] == "refresh_token") {
                PartnerIntegrationSetting::find($value["id"])->update(['value' => $data['settings']['refresh_token']]);
            } else if ($changed) {
                PartnerIntegrationSetting::find($value["id"])->update(['value' => $value["value"]]);
            }
        }

        $selectedDatabase["settings"] = $settings;
        $data["vue_state"]["selectedDatabase"] = $selectedDatabase;

        return $data;
    }
}
