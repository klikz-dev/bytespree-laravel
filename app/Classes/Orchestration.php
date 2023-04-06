<?php

namespace App\Classes;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Exception;
use Log;

/**
 * Class to communicate with the Orchestration API
 */
class Orchestration
{
    public function __construct(
        private string $base_url,
        private string $api_key,
        private $client = NULL,
        private bool $debug = FALSE
    ) {
        $this->client = new Client([
            'base_uri' => $this->base_url,
            'headers'  => [
                'X-Orchestration-Api-Key' => $this->api_key,
                'Accept'                  => 'application/json',
            ],
            'verify' => FALSE,
        ]);
    }

    /**
     * Make the request with our client
     * 
     * @param  string $method               The HTTP method to use (GET, POST, PUT, DELETE)
     * @param  string $endpoint             The endpoint to call e.g. /service/users
     * @param  array  $data                 The data to send with the request (Only for POST or PUT)
     * @param  bool   $return_full_response Return the full response object or just the JSON decoded $body['data]
     * @return array  The JSON decoded response, if valid JSON. [] if not valid JSON
     */
    public function request(string $method, string $endpoint, array $options = [], bool $return_full_response = FALSE)
    {
        if (count($options) > 0) {
            $options = [
                'json' => $options,
            ];
        }

        Log::info("Orchestration request: {$method} {$endpoint} " . json_encode($options));

        try {
            $response = $this->client->request($method, $endpoint, $options);

            $json = json_decode($response->getBody()->getContents(), TRUE);

            if (! is_array($json)) {
                return [];
            }

            if ($return_full_response) {
                return $json;
            }

            if (array_key_exists('data', $json) && array_key_exists('status', $json)) {
                return $json['data'];
            }

            return $json;
        } catch (ClientException|Exception|ServerException $e) {
            $json = json_decode($e->getResponse()->getBody()->getContents(), TRUE);

            if (! is_array($json)) {
                return [];
            }

            if ($return_full_response) {
                return $json;
            }

            if (array_key_exists('data', $json)) {
                return $json['data'];
            }

            return $json;
        }
    }

    /**
     * Gets the connection pool for the team
     *
     * @param  string $region The name of the region
     * @param  string $domain The name of the team
     * @return void
     */
    public function getConnectionPool(string $region, string $domain)
    {
        return $this->request('GET', "/service/v1/infrastructure/connection-pools?region=$region&domain=$domain");
    }

    public function getConfigurationValue(string $key_name)
    {
        return $this->request('GET', "/service/v1/infrastructure/configuration?key_name=$key_name");
    }

    /**
     * Method user to provide team details from ORC
     *
     * @param string $domain handle of team
     *
     * @return array
     */
    public function getTeamByDomain(string $domain)
    {
        return $this->request('GET', "/service/v1/teams/$domain");
    }

    public function getTeamConnectors(string $domain)
    {
        return $this->request('GET', "/service/v1/teams/$domain/connectors");
    }

    /**
     * Method user to get the users for this team
     *
     * @param string $domain handle of team
     *
     * @return array
     */
    public function getTeamUsers(string $domain)
    {
        return $this->request('GET', "/service/v1/teams/$domain/users");
    }

    /**
     * Try to send invitations to users
     *
     * @param  array  $invitees An array of emails (or handles) we're sending invites to
     * @param  string $handle   The user sending the invites
     * @param  string $type     Type of invitation - can only be email or handle at this point in time
     * @return bool   TRUE if successful; FALSE if not
     */
    public function sendInvitation(array $invites = [], $handle = '', $type = 'email')
    {
        $type = $type == 'handle' ? 'handle' : 'email';
        $domain = app('environment')->getTeam();
        $body = compact('invites', 'handle', 'type', 'domain');

        return $this->request('POST', "/service/v1/team-invitations", $body, TRUE);
    }

   /**
    * Accepts an team invitation for a user
    *
    * @param  string $handle          The user handle
    * @param  string $invitation_code The invitation code
    * @param  string $email_code      Needed if users email is different then the invitations
    * @return array
    */
   public function acceptInvitation(string $handle, string $invitation_code, ?string $email_code = NULL)
   {
       $body = compact('handle', 'invitation_code', 'email_code');

       return $this->request('PUT', "/service/v1/team-invitations/accept", $body, TRUE);
   }

    /**
     * Update version information for team in Orchestration
     *
     * @param  string $domain            Team name
     * @param  string $version           Version team was upgraded to
     * @param  string $rollbar_deploy_id Deploy ID returned by Rollbar
     * @return array
     */
    public function updateVersion(string $domain, string $version, string $rollbar_deploy_id)
    {
        $body = compact('version', 'rollbar_deploy_id');

        return $this->request('PUT', "/service/v1/teams/$domain/version", $body);
    }

    /**
     * Makes a call to orchestration to delete an invite by user ID
     *
     * @param  int   $id The id of the invitation user
     * @return array
     */
    public function removeInvitedUser(int $id, string $domain)
    {
        return $this->request('DELETE', "/service/v1/team-invitations/$id", compact('domain'), TRUE);
    }

    /**
     * Makes a call to the orchestration api to delete a user
     *
     * @param  int    $id   The id of the user
     * @param  string $team The team this is for
     * @return array
     */
    public function removeUserFromTeam(int $id, string $domain)
    {
        return $this->request('DELETE', "/service/v1/teams/user/$id?domain=$domain");
    }

    public function getUserTeams(string $handle)
    {
        return $this->request('GET', "/service/v1/users/$handle/teams");
    }

    public function getUser(string $handle)
    {
        return $this->request('GET', '/service/v1/users/' . $handle);
    }

    public function checkIsAdmin(string $handle)
    {
        return $this->request('GET', "/service/v1/users/$handle/admin");
    }

    public function changePassword(string $handle, string $current_password, string $new_password)
    {
        return $this->request('PUT', "/service/v1/users/{$handle}/password", [
            'password'     => $current_password,
            'new_password' => $new_password,
        ], return_full_response: TRUE);
    }

    public function changeEmail(string $handle, string $email)
    {
        return $this->request('PUT', "/service/v1/users/{$handle}/email", compact('email'), TRUE);
    }

    public function changePhone(string $handle, string $phone)
    {
        return $this->request('PUT', "/service/v1/users/{$handle}/phone", compact('phone'), TRUE);
    }

    public function removePhone(string $handle)
    {
        return $this->request('DELETE', "/service/v1/users/{$handle}/phone", return_full_response: TRUE);
    }

    public function updateUser(string $handle, ?string $first_name, ?string $last_name, ?string $dfa_preference = NULL, ?string $team_preference = NULL)
    {
        $body = compact('first_name', 'last_name', 'dfa_preference', 'team_preference');

        return $this->request('PUT', "/service/v1/users/{$handle}", $body, TRUE);
    }

    public function getUserNotifications(string $handle, int $last_id)
    {
        return $this->request('GET', "/service/v1/notifications/{$handle}?last_id={$last_id}");
    }

    public function markNotificationRead(string $handle, int $id)
    {
        $body = compact('handle');

        return $this->request('PUT', "/service/v1/notifications/$id/read", $body);
    }

    public function markAllNotificationsRead(string $handle)
    {
        $body = compact('handle');

        return $this->request('PUT', "/service/v1/notifications/all/read", $body);
    }

    /**
     * Adds a notification for the user via the Orchestration APIs.
     *
     * @param  string $user      the handle for the user to whom the notification should be assigned
     * @param  string $team      (Optional) The name of the team
     * @param  string $subject   (Optional) A brief header that introduces the notification's content
     * @param  string $message   (Optional) The content of the notification
     * @param  string $type      (Optional) One of "info", "danger", "success", or "warning". Default is "info".
     * @param  string $hyperlink (Optional) A URL to redirect the user to, if any
     * @return array  a response array from the Orchestration APIs
     */
    public function addNotification($user, $team = NULL, $subject = NULL, $message = NULL, $type = "info", $hyperlink = NULL)
    {
        $body = compact('user', 'team', 'subject', 'message', 'type', 'hyperlink');

        return $this->request('POST', "/service/v1/notifications", $body);
    }

    public function dismissNotification(string $handle, int $id)
    {
        $body = compact('handle');

        return $this->request('PUT', "/service/v1/notifications/$id/dismiss", $body);
    }

    public function dismissAllNotifications(string $handle)
    {
        $body = compact('handle');

        return $this->request('PUT', "/service/v1/notifications/all/dismiss", $body);
    }

    public function getRegions()
    {
        return $this->request('GET', "/service/v1/regions");
    }

    public function refreshSession(string $team, string $handle, ?string $first_name, ?string $last_name, string $email)
    {
        $body = compact('team', 'handle', 'first_name', 'last_name', 'email');

        return $this->request('POST', '/service/v1/sessions', $body);
    }

    public function getConnector(int|string $id)
    {
        return $this->request('GET', "/service/v1/connectors/$id");
    }

    /**
     * Fetch DMI IPs from ORC
     *
     * @return array
     */
    public function getAllowedIPs()
    {
        return $this->request('GET', "/service/v1/ip-addresses");
    }

    /**
     * Get a new upload token from Orchestration
     *
     * @param  int    $user_id    ID of the user
     * @param  string $ip_address IP address of the current user
     * @param  string $team       String name of the team
     * @return array
     */
    public function createUploadToken(int $user_id, string $ip_address, string $domain)
    {
        $body = compact('user_id', 'ip_address', 'domain');

        return $this->request('POST', "/service/v1/upload-tokens", $body);
    }

    /**
     * Get a list of all server providers
     *
     * @return array
     */
    public function getServerProviders()
    {
        return $this->request('GET', "/service/v1/providers");
    }

    /**
     * Get a server provider by its ID
     *
     * @param  int   $id ID of the provider
     * @return array
     */
    public function getServerProviderById(int $id)
    {
        return $this->request('GET', "/service/v1/providers/$id");
    }

    /**
     * Get a list of all server provider configurations
     *
     * @return array
     */
    public function getServerProviderConfigurations()
    {
        return $this->request('GET', "/service/v1/provider-configurations");
    }

    /**
     * Get a server provider configuration's details by its ID
     *
     * @param  int   $id ID of the configuration (NOT the slug)
     * @return array
     */
    public function getServerProviderConfigurationById(int $id)
    {
        return $this->request('GET', "/service/v1/provider-configurations/$id");
    }

    /**
     * Generate a redirect url to be used for unauthenticated users
     */
    public function redirectUrl(string $url): string
    {
        return rtrim(config('orchestration.url'), '/') . '/app/team?redirect_uri=' . urlencode($url);
    }
}
