<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Server;
use App\Models\ServerProviderConfiguration;
use App\Classes\Database\Connection;
use Exception;
use App\Classes\Postmark;
use App\Models\User;
use Auth;
use App\Classes\ServerProviders\DigitalOcean;
use App\Models\SavedData;

class ServerController extends Controller
{
    public function list()
    {
        $from_utc = [
            "time"   => ltrim(substr(date('O'), 1, 2), "0"),
            "symbol" => substr(date('O'), 0, 1)
        ];

        $days_array = [
            "sunday" => [
                "prev" => "saturday",
                "next" => "monday"
            ],
            "monday" => [
                "prev" => "sunday",
                "next" => "tuesday"
            ],
            "tuesday" => [
                "prev" => "monday",
                "next" => "wednesday"
            ],
            "wednesday" => [
                "prev" => "tuesday",
                "next" => "thursday"
            ],
            "thursday" => [
                "prev" => "wednesday",
                "next" => "friday"
            ],
            "friday" => [
                "prev" => "thurday",
                "next" => "saturday"
            ],
            "saturday" => [
                "prev" => "friday",
                "next" => "sunday"
            ]
        ];

        $dmi_ips = collect(app('orchestration')->getAllowedIPs())->pluck('ip');

        $servers = Server::with('groups')->get()->map(function ($server) use ($from_utc, $days_array ) {
            if (! isset($server->status)) {
                $server->status = "";
            }

            if (! empty($server->start_time)) {
                $start_time = ltrim(substr($server->start_time, 0, 2), "0");
                $end_time = ltrim(substr($server->end_time, 0, 2), "0");

                if (empty($start_time)) {
                    $start_time = 0;
                }

                if (empty($end_time)) {
                    $end_time = 0;
                }

                if (empty($from_utc['time'])) {
                    $from_utc['time'] = 0;
                }

                if ($from_utc['symbol'] == '+') {
                    $start_time_eval = $start_time + $from_utc["time"];
                    $end_time_eval = $end_time + $from_utc["time"];
                } elseif ($from_utc['symbol'] == '-') {
                    $start_time_eval = $start_time - $from_utc["time"];
                    $end_time_eval = $end_time - $from_utc["time"];
                }

                if ($start_time_eval <= 0) {
                    $server->shown_start_day = $days_array[$server->start_day]["prev"];
                } elseif ($start_time_eval >= 24) {
                    $server->shown_start_day = $days_array[$server->start_day]["next"];
                } else {
                    $server->shown_start_day = $server->start_day;
                }

                if ($end_time_eval <= 0) {
                    $server->shown_end_day = $days_array[$server->end_day]["prev"];
                } elseif ($end_time_eval >= 24) {
                    $server->shown_end_day = $days_array[$server->end_day]["next"];
                } else {
                    $server->shown_end_day = $server->end_day;
                }
            }

            $groups = [];
            foreach ($server->groups as $group) {
                $ips = $group->ips->pluck('ip')->implode(', ');
                $groups[] = ['id' => $group->id, 'ips' => $ips, 'notes' => $group->notes];
            }

            unset($server->groups);

            $server->groups = $groups;

            return $server;
        });

        return response()->success($servers);
    }

    public function configurations()
    {
        return response()->success(
            ServerProviderConfiguration::all()
        );
    }

    public function create(Request $request)
    {
        $type = $request->type;
        $name = $request->name;

        // We'll want to do this string sanitation on the front-end too
        $name = strtolower($name); // Lowercase
        $name = str_replace(' ', '', $name); // Remove spaces
        $name = preg_replace("/[^A-Za-z0-9 ]/", '', $name); // Strip non-alpha-numeric characters

        if (! $type || ! $name) {
            return response()->error("You must provide all required arguments.");
        }

        if ($type == "do") {
            if (empty($request->server_provider_configuration_id)) {
                return response()->error("You must provide all required arguments.");
            }

            try {
                $provider = new DigitalOcean();
                $provider->canCreateDatabase($name);
            } catch (Exception $e) {
                return response()->error($e->getMessage());
            }

            $problematic_ips = [];
            $groups = [];
            foreach ($request->groups as $group) {
                $ips = explode(',', $group['ips']);
                $dupe_check = [];

                foreach ($ips as $key => $ip) {
                    $ip = trim($ip);

                    if (array_search($ip, $dupe_check)) {
                        unset($ips[$key]);
                        continue;
                    } else if (! app('networking')->validateIp($ip, TRUE) || $ip == '') {
                        $problematic_ips[] = $ip;
                    }

                    $ips[$key] = $ip;
                    $dupe_check[] = $ip;
                }

                $group['ips'] = array_values($ips);
                $groups[] = $group;
            }

            if (count($problematic_ips) > 0) {
                return response()->error('One or more of the IP addresses supplied are invalid.');
            }

            $saved_data = SavedData::create([
                'data' => [
                    'name'                             => $name,
                    'region'                           => 'nyc1', // Default to nyc1, for now
                    'server_provider_configuration_id' => $request->server_provider_configuration_id,
                    'groups'                           => $groups,
                    'is_default'                       => $request->is_default === TRUE,
                ],
                'controller' => self::class,
            ]);

            $result = app('jenkins')->launchFunction(
                'addServer',
                [
                    'TEAM'    => app('environment')->getTeam(),
                    'DATA_ID' => $saved_data->guid
                ]
            );

            return response()->success($result, 'Added Sever');
        }

        $request->validateWithErrors([
            'name'             => 'required',
            'hostname'         => 'required',
            'username'         => 'required',
            'password'         => 'required',
            'port'             => 'required',
            'driver'           => 'required',
            'default_database' => 'required',
        ]);

        $start_day = $request->input('start_day', NULL);
        $end_day = $request->input('end_day', NULL);
        $start_time = $request->input('start_time', NULL);
        $end_time = $request->input('end_time', NULL);

        try {
            $connection = Connection::external(
                hostname: $request->hostname,
                username: $request->username,
                password: $request->password,
                port: $request->port,
                database: $request->default_database,
                driver: $request->driver,
                schema: 'public'
            );
        } catch (Exception $e) {
            return response()->error("A connection could not be established. Please check that server details are valid.");
        }

        $server = Server::create([
            'name'             => $request->name,
            'hostname'         => $request->hostname,
            'username'         => $request->username,
            'password'         => $request->password,
            'port'             => $request->port,
            'driver'           => $request->driver,
            'default_database' => $request->default_database,
            'start_day'        => $start_day,
            'end_day'          => $end_day,
            'start_time'       => $start_time,
            'end_time'         => $end_time,
            'provider_guid'    => 3010, // this was hardcoded in bytespree?!
        ]);

        if ($request->has('is_default') && $request->is_default === TRUE) {
            $server->updateDefault(TRUE);
        }

        return response()->success($server, 'Added Sever');
    }

    public function update(Request $request, Server $server)
    {
        $type = $request->type;

        $name = strtolower($request->name); // Lowercase
        $name = str_replace(' ', '', $name); // Remove spaces
        $name = preg_replace("/[^A-Za-z0-9 ]/", '', $name); // Strip non-alpha-numeric characters

        if ($type == "do") {
            if (empty($request->server_provider_configuration_id)) {
                return response()->error("You must provide all required arguments.");
            }

            $configuration = ServerProviderConfiguration::find($request->server_provider_configuration_id);

            if (empty($configuration)) {
                return response()->error("Invalid server provider configuration.");
            }

            $problematic_ips = [];
            $groups = [];
            foreach ($request->groups as $group) {
                $ips = explode(',', $group['ips']);
                $dupe_check = [];

                foreach ($ips as $key => $ip) {
                    $ip = trim($ip);

                    if (array_search($ip, $dupe_check)) {
                        unset($ips[$key]);
                        continue;
                    } else if (! app('networking')->validateIp($ip, TRUE) || $ip == '') {
                        $problematic_ips[] = $ip;
                    }

                    $ips[$key] = $ip;
                    $dupe_check[] = $ip;
                }

                $group['ips'] = array_values($ips);
                $groups[] = $group;
            }

            if (count($problematic_ips) > 0) {
                return response()->error('One or more of the IP addresses supplied are invalid.');
            }

            $class_name = str_replace(' ', '', $server->configuration->provider->name);
            $provider = app('App\\Classes\\ServerProviders\\' . $class_name);

            $result = $provider->update(
                $server,
                $configuration,
                $name,
                $groups
            );
            
            if (! $result) {
                return response()->error("An error occurred while updating the server.");
            }

            if ($request->has('is_default')) {
                $server->updateDefault($request->is_default === TRUE);
            }

            return response()->success([], 'Edited Server');
        }

        $request->validateWithErrors([
            'name'             => 'required',
            'hostname'         => 'required',
            'username'         => 'required',
            'port'             => 'required',
            'driver'           => 'required',
            'default_database' => 'required',
        ]);

        $start_day = $request->input('start_day', NULL);
        $end_day = $request->input('end_day', NULL);
        $start_time = $request->input('start_time', NULL);
        $end_time = $request->input('end_time', NULL);

        try {
            $connection = Connection::external(
                hostname: $request->hostname,
                username: $request->username,
                password: $request->password ?? $server->password,
                port: $request->port,
                database: $request->default_database,
                driver: $request->driver,
                schema: 'public'
            );
        } catch (Exception $e) {
            return response()->error("A connection could not be established. Please check that server details are valid.");
        }

        $name = strtolower($request->name); // Lowercase
        $name = str_replace(' ', '', $name); // Remove spaces
        $name = preg_replace("/[^A-Za-z0-9 ]/", '', $name); // Strip non-alpha-numeric characters

        $server->update([
            'name'             => $name,
            'hostname'         => $request->hostname,
            'username'         => $request->username,
            'port'             => $request->port,
            'driver'           => $request->driver,
            'default_database' => $request->default_database,
            'start_day'        => $start_day,
            'end_day'          => $end_day,
            'start_time'       => $start_time,
            'end_time'         => $end_time,
            'provider_guid'    => 3010
        ]);

        if ($request->has('password') && ! empty($request->password)) {
            $server->update(['password' => $request->password]);
        }

        $server->save();

        if ($request->has('is_default')) {
            $server->updateDefault($request->is_default === TRUE);
        }

        return response()->success($server, 'Edited Server');
    }

    public function destroy(Server $server)
    {
        if ($server->provider_guid != '3010') {
            if (! $server->deleteProviderServer()) {
                return response()->error("Provider server not deleted");
            }
        }

        // todo add support for when we can create studio projects w/attachments and what not.
        // $result = $this->BP_ServersModel->deleteServer($id);
        // $this->_sendAjax("ok", "Deleted Server", $result);

        // $partner_integrations = $this->PartnerIntegrationsModel->getAll();

        // if ($partner_integrations) {
        //     foreach ($partner_integrations as $partner_integration) {
        //         if ($partner_integration['server_id'] == $server['id']) {
        //             $all_projects = $this->BP_ProjectsModel->getProjectsByPartnerIntegrationId($partner_integration['id']);

        //             foreach ($all_projects as $project) {
        //                 $project_attachments = $this->BP_ProjectAttachmentsModel->getAttachments($project['id']);
        //                 foreach ($project_attachments as $attachment) {
        //                     $this->BP_ProjectAttachmentsModel->deleteAttachment($attachment['id']);
        //                 }

        //                 $column_attachments = $this->BP_ProjectColumnAttachmentsModel->getAttachments($project['id']);
        //                 foreach ($column_attachments as $column_attachment) {
        //                     $this->BP_ProjectColumnAttachmentsModel->deleteAttachment($column_attachment['id']);
        //                 }

        //                 $this->BP_ProjectsModel->deleteProjectAndRecordsAttached($project['id']);
        //             }
        //             $this->PartnerIntegrationsModel->delete($partner_integration['id']);
        //         }
        //     }
        // }

        $server->delete();
        
        // todo move this admin notification to somewhere reusable. it feels dirty to do it this way in the controller.
        $email_string = User::where('is_admin', TRUE)->pluck('email')->implode(',');
        
        $data = [
            "user"   => Auth::user()->name,
            "server" => $server->name,
            "team"   => app('environment')->getTeam()
        ];

        Postmark::send($email_string, "deleted-server", $data);

        return response()->success($server, 'Deleted Server');
    }
}
