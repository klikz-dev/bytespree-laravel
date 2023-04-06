<?php

namespace App\Classes\ServerProviders;

use GuzzleHttp\Client;
use App\Models\Server;
use App\Models\ServerIp;
use App\Models\ServerIpGroup;
use App\Models\ServerProviderConfiguration;
use App\Models\DatabaseLog;
use App\Models\User;
use App\Classes\Postmark;
use Exception;

class DigitalOcean
{
    /**
     * The Guzzle client
     * 
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.digitalocean.com/v2/',
            'headers'  => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . config('services.digitalocean.token'),
            ],
        ]);
    }

    /**
     * Create a new server
     * 
     * @param  string                      $region
     * @param  string                      $name
     * @param  ServerProviderConfiguration $configuration
     * @param  array                       $groups
     * @return Server                      if successful, FALSE if not
     */
    public function create($region, $name, $configuration, $groups)
    {
        $created = [];

        $body = [
            "name"      => $name,
            "engine"    => "pg",
            "version"   => empty(config('services.digitalocean.pg_version')) ? "12" : config('services.digitalocean.pg_version'),
            "region"    => $region,
            "size"      => $configuration->slug,
            "num_nodes" => intval($configuration->nodes)
        ];

        try {
            $response = $this->client->request("POST", "databases", [
                'body' => json_encode($body)
            ]);
            $status_code = $response->getStatusCode();
            if ($status_code == 201) {
                $response = (string) $response->getBody();
                $result = json_decode($response);
                if (is_object($result) && $result->database->id != "unprocessable_entity") {
                    $created['server'] = TRUE;
                } else {
                    $created['server'] = FALSE;
                }
            } else {
                $result = NULL;
                $created['server'] = FALSE;
            }
        } catch (Exception $e) {
            logger()->error(
                'Error creating Bytespree server',
                [
                    'message' => $e->getMessage(),
                    'body'    => json_encode($body)
                ]
            );
            $created['server'] = FALSE;
        }

        $hostname = $result->database->connection->host;
        $hostname_private = $result->database->private_connection->host;
        $username = $result->database->connection->user;
        $password = $result->database->connection->password;
        $default_database = $result->database->connection->database;
        $port = $result->database->connection->port;
        $driver = "postgre";
        $provider_guid = $result->database->id;
        $start_day = $result->database->maintenance_window->day;
        $start_time = $result->database->maintenance_window->hour;
        $end_date = date('l H:i:s', strtotime($start_day . ' ' . $start_time . ' + 4 hours'));
        $end_date = explode(" ", $end_date);
        $end_day = strtolower($end_date[0]);
        $end_time = $end_date[1];

        $server = Server::create([
            'name'                             => $name,
            'hostname'                         => $hostname,
            'hostname_private'                 => $hostname_private,
            'username'                         => $username,
            'password'                         => $password,
            'default_database'                 => $default_database,
            'port'                             => $port,
            'driver'                           => $driver,
            'provider_guid'                    => $provider_guid,
            'server_provider_configuration_id' => $configuration->id,
            'start_day'                        => $start_day,
            'end_day'                          => $end_day,
            'start_time'                       => $start_time,
            'end_time'                         => $end_time
        ]);

        if (! $server) {
            $created['server'] = FALSE;
        }

        if ($server && ! empty($provider_guid)) {
            $created['firewall'] = $this->updateFirewallIps($server, $groups);

            if ($created['firewall'] === TRUE) {
                $created['firewall'] = $this->updateLocalizedIps($server, $groups);
            }
        }

        if (array_search(FALSE, $created) !== FALSE) {
            return FALSE;
        }
        
        return $server;
    }

    /**
     * Update the firewall rules for the server on Digital Ocean
     * 
     * @param  array $groups
     * @return bool  TRUE if successful, FALSE if not
     */
    public function updateFirewallIps(Server $server, $groups)
    {
        $rules = [];

        $dmi_ips = array_column(app('orchestration')->getAllowedIPs(), 'ip');
        
        foreach ($dmi_ips as $dmi_ip) {
            $rules[] = [
                "type"  => "ip_addr",
                "value" => $dmi_ip
            ];
        }

        foreach ($groups as $group) {
            foreach ($group['ips'] as $ip) {
                if (app('networking')->validateIp($ip, TRUE) && ! in_array($ip, array_column($rules, 'value'))) {
                    $rules[] = [
                        "type"  => "ip_addr",
                        "value" => $ip
                    ];
                }
            }
        }

        $body = compact('rules');

        try {
            $result = $this->client->request('PUT', "databases/" . $server->provider_guid . "/firewall", [
                'body' => json_encode($body)
            ]);
            if ($result->getStatusCode() == 204) {
                return TRUE;
            }
        } catch (Exception $e) {
            logger()->error(
                'Error updating Bytespree server firewall',
                [
                    'message'       => $e->getMessage(),
                    'provider_guid' => $server->provider_guid,
                    'body'          => json_encode($body),
                    'server'        => $server,
                ]
            );
        }

        return FALSE;
    }

    /**
     * Checks to see if any groups have changed
     * 
     * @param  Server $server
     * @param  array  $groups
     * @return bool   TRUE if successful, FALSE if not
     */
    public function checkForGroupChanges($server, $groups)
    {
        if ($server->groups->count() != count($groups)) {
            return TRUE;
        }

        foreach ($groups as $group) {
            if (empty($group['id'])) {
                return TRUE;
            }

            $current_group = $server->groups->find($group['id']);
            $current_ips = $current_group->ips->pluck('ip')->toArray();
            
            $ip_diff_1 = array_diff($current_ips, $group['ips']);
            $ip_diff_2 = array_diff($group['ips'], $current_ips);
            
            if (count($ip_diff_1) > 0 || count($ip_diff_2) > 0 || $current_group->notes != $group['notes']) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Update the localized IPs for the server in the Bytespree database
     * 
     * @param  Server $server
     * @param  array  $groups
     * @return bool   TRUE if successful, FALSE if not
     */
    public function updateLocalizedIps($server, $groups)
    {
        $dmi_ips = array_column(app('orchestration')->getAllowedIPs(), 'ip');
        $local_ip_entries = [];
        $old_groups = ServerIpGroup::where('server_id', $server->id)->get();

        foreach ($dmi_ips as $dmi_ip) {
            ServerIp::updateOrCreate([
                "is_dmi"    => TRUE,
                "server_id" => $server->id,
                "ip"        => $dmi_ip
            ]);
        }

        foreach ($old_groups as $old_group) {
            $exists = collect($groups)->filter(function ($group) use ($old_group) {
                $id = $group['id'] ?? 0;

                return $id == $old_group->id;
            });
            
            if ($exists->count() == 0) {
                foreach ($old_group->ips as $old_ip) {
                    $old_ip->delete();
                }

                $old_group->delete();
            }
        }

        foreach ($groups as $group) {
            if (! empty($group['id'])) {
                ServerIpGroup::find($group['id'])->update([
                    'notes' => $group['notes']
                ]);

                $db_group = ServerIpGroup::find($group['id']);
            } else {
                $db_group = ServerIpGroup::create([
                    'server_id' => $server->id,
                    'notes'     => $group['notes']
                ]);
            }

            foreach ($db_group->ips as $old_ip) {
                $exists = collect($group['ips'])->filter(function ($ip) use ($old_ip) {
                    return $ip == $old_ip->ip;
                });

                if ($exists->count() == 0) {
                    $old_ip->delete();
                }
            }

            foreach ($group['ips'] as $ip) {
                if (app('networking')->validateIp($ip, TRUE)) {
                    ServerIp::updateOrCreate([
                        "is_dmi"    => FALSE,
                        "server_id" => $server->id,
                        "group_id"  => $db_group->id,
                        "ip"        => $ip
                    ]);
                }
            }
        }

        logger()->info("After inserting local ips...");

        return TRUE;
    }

    /**
     * Update a server via a new configuration, name, and/or IPs
     * 
     * @param  Server                      $server        The server to update
     * @param  ServerProviderConfiguration $configuration The new configuration
     * @param  string                      $name          The new name
     * @param  array                       $groups        This servers group of ips
     * @return true                        on success, FALSE on failure
     */
    public function update(Server $server, ServerProviderConfiguration $new_configuration, string $name, array $groups = [])
    {
        $dmi_ips = collect(app('orchestration')->getAllowedIps())->pluck('ip')->toArray();
    
        $old_server = $server->toArray();
        $old_configuration = $server->configuration;
        $old_ips = $server->ips->filter(function ($ip) use ($dmi_ips) {
            return ! in_array($ip->ip, $dmi_ips);
        })
            ->pluck('ip')
            ->toArray();

        $ips = [];
        foreach ($groups as $group) {
            $ips = array_merge($ips, $group['ips']);
        }
    
        $updated = [
            'server'   => FALSE,
            'firewall' => FALSE,
        ];

        $errors = [
            'server'   => FALSE,
            'firewall' => FALSE,
        ];
    
        if ($new_configuration->id != $server->configuration->id) {
            $body = [
                'size'      => $new_configuration->slug,
                'num_nodes' => intval($new_configuration->nodes)
            ];
    
            try {
                $response = $this->client->request("PUT", "databases/{$server->provider_guid}/resize", [
                    'body' => json_encode($body)
                ]);
                if ($response->getStatusCode() == 202) {
                    $updated['server'] = TRUE;

                    $data = [
                        "name"                             => $name,
                        "server_provider_configuration_id" => $new_configuration->id
                    ];
                } else {
                    $errors['server'] = TRUE;
                }
            } catch (Exception $e) {
                logger()->error(
                    'Error updating Bytespree server',
                    [
                        'message'       => $e->getMessage(),
                        'provider_guid' => $server->provider_guid,
                        'body'          => json_encode($body)
                    ]
                );
                $errors['server'] = TRUE;
            }
        } else {
            if ($server->name != $name) {
                $data = compact('name');
                $updated['server'] = TRUE;
            }
        }

        if ($updated['server']) {
            Server::where('id', $server->id)->update($data);
    
            $old_server_intersect = array_intersect_key($old_server, $data);

            DatabaseLog::create([
                'user_id'    => auth()->user()->id,
                'table_name' => 'di_servers',
                'old_data'   => $old_server_intersect,
                'new_data'   => $data,
            ]);
        }

        if ($this->checkForGroupChanges($server, $groups)) {
            if ($this->updateFirewallIps($server, $groups)) {
                $result = $this->updateLocalizedIps($server, $groups);
                if ($result) {
                    $updated['firewall'] = TRUE;
                } else {
                    $errors['firewall'] = TRUE;
                }
            }
        }
    
        logger()->info("up_1", $updated);

        $change_data = [
            "old_server_data" => [],
            "new_server_data" => [],
            "ips_added"       => [],
            "ips_removed"     => []
        ];
    
        if (array_search(TRUE, $errors) !== FALSE) {
            $failed = TRUE;
            $subject = "Updating DigitalOcean Server Failed";
        } else {
            if (array_search(TRUE, $updated) === FALSE) {
                // No changes were made
                return TRUE;
            }

            $failed = FALSE;
            $subject = "DigitalOcean Server Updated";
    
            if ($updated['server']) {
                $change_data["old_server_data"] = $old_server_intersect;
                $change_data["new_server_data"] = $data;
            }
    
            if ($updated['firewall']) {
                $change_data["ips_added"] = array_diff($ips, $old_ips);
                $change_data["ips_removed"] = array_diff($old_ips, $ips);
            }
        }

        logger()->info("up_2", compact('change_data', 'failed', 'subject', 'updated'));

        $server_changes = [];
        foreach ($change_data["new_server_data"] as $key => $new_server_value) {
            if ($change_data["old_server_data"][$key] != $new_server_value) {
                if ($key == "name") {
                    $name = $change_data["old_server_data"][$key];
                }

                if ($key == "server_provider_configuration_id") {
                    $server_changes[] = $old_configuration->slug . " " . $old_configuration->nodes . " node(s) ($" . $old_configuration->resale_price . ") -> " . $new_configuration->slug . " " . $new_configuration->nodes . " node(s) ($" . $new_configuration->resale_price . ")";
                } else {
                    $server_changes[] = $change_data["old_server_data"][$key] . " -> " . $new_server_value;
                }
            }
        }

        $email_addresses = User::isAdmin()->pluck('email')->toArray();

        $data = [
            "failed"         => $failed,
            "name"           => $name,
            "hostname"       => $server->hostname,
            "subject"        => $subject,
            "server_changes" => $server_changes,
            "ips_removed"    => array_unique(array_values($change_data["ips_removed"])),
            "ips_added"      => array_unique(array_values($change_data["ips_added"]))
        ];

        Postmark::send($email_addresses, "digital-ocean-updated", $data);
        
        return ! $failed;
    }

    /**
     * Delete a server
     * 
     * @param  Server $server The server to delete
     * @return true   on success, FALSE on failure
     */
    public function destroy(Server $server)
    {
        try {
            $result = $this->client->request('DELETE', "databases/" . $server->provider_guid);
            if ($result->getStatusCode() == 204) {
                return TRUE;
            }
        } catch (Exception $e) {
            logger()->error(
                'Error deleting Bytespree server',
                [
                    'message'       => $e->getMessage(),
                    'provider_guid' => $server->provider_guid
                ]
            );
        }

        return FALSE;
    }

    /**
     * Get a server
     * 
     * @param  Server $server The server to delete
     * @return true   on success, FALSE on failure
     */
    public function get(Server $server)
    {
        try {
            $response = $this->client->request('GET', "databases/" . $server->provider_guid);
            if ($response->getStatusCode() == 200) {
                $response_body = (string) $response->getBody();
                $server = json_decode($response_body);

                return $server;
            }
        } catch (Exception $e) {
            logger()->error(
                'Could not find Bytespree server',
                [
                    'message'       => $e->getMessage(),
                    'provider_guid' => $server->provider_guid
                ]
            );
        }

        return NULL;
    }

    /**
     * Get a list of all database server names from DO
     * 
     * @return array
     */
    public function list()
    {
        try {
            $response = $this->client->request("GET", "databases");
            $status_code = $response->getStatusCode();

            if ($status_code == 200) {
                $results = json_decode((string) $response->getBody());
                if (! is_object($results) || ! property_exists($results, 'databases')) {
                    return [];
                }

                return array_map(fn($db) => $db->name, $results->databases);
            }
        } catch (Exception $e) {
            logger()->error(
                'Error getting Bytespree servers',
                [
                    'message' => $e->getMessage()
                ]
            );
        }

        return [];
    }

    /**
     * Can the database_name be used to create a new database server?
     * 
     * @param  string $database_name The name of the database
     * @return true   if the name is available, FALSE if not
     */
    public function canCreateDatabase(string $database_name): bool
    {
        try {
            $response = $this->client->request("GET", "databases");
            $status_code = $response->getStatusCode();

            if ($status_code == 200) {
                $results = json_decode((string) $response->getBody());
                if (! is_object($results) || ! property_exists($results, 'databases')) {
                    throw new Exception('Invalid response from DigitalOcean');
                }

                $databases = array_map(fn($db) => $db->name, $results->databases);

                if (in_array($database_name, $databases)) {
                    throw new Exception("This database name already exists.");
                }

                return TRUE;
            }
        } catch (Exception $e) {
            logger()->error(
                'Error getting Bytespree servers',
                [
                    'message' => $e->getMessage()
                ]
            );

            if ($e::class === Exception::class) {
                throw $e;
            }
        }

        throw new Exception("Could not connect to DigitalOcean API.");
    }

    public function certificate(Server $server)
    {
        try {
            $response = $this->client->request('GET', "databases/{$server->provider_guid}/ca");
            if ($response->getStatusCode() == 200) {
                $response_body = (string) $response->getBody();
                $certificate = json_decode($response_body);

                return $certificate->ca->certificate;
            }
        } catch (Exception $e) {
            logger()->error(
                'Could not find certificate',
                [
                    'message'       => $e->getMessage(),
                    'provider_guid' => $server->provider_guid
                ]
            );
        }

        return NULL;
    }
}
