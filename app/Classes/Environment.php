<?php

namespace App\Classes;

use Exception;

/**
 * Class to manage the environment details for the application
 */
class Environment
{
    /**
     * Get the current team
     * 
     * @throws Exception if the team could not be determined
     */
    public function getTeam(): string
    {
        $parsed_url = parse_url(config('app.url'));
        
        if (! array_key_exists('host', $parsed_url)) {
            if (app()->isLocal()) {
                return 'dev';
            }

            throw new Exception('Could not determine team.');
        }

        $host = $parsed_url['host'];
        
        if (! empty($host)) {
            $data = explode('.', $host);

            return $data[0];
        }

        throw new Exception('Team could not be determined.');
    }

    /**
     * Get region name from URL
     */
    public function getRegionName(): string
    {
        $hostname = parse_url(config('app.url'), PHP_URL_HOST);

        if (empty($hostname)) {
            $hostname = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST);
        }

        if (empty($hostname)) {
            throw new Exception("Hostname could not be determined.");
        }
        
        $host_parts = explode('.', $hostname);

        if (count($host_parts) > 0) {
            return $host_parts[1];
        }

        return '';
    }

    /**
     * Gets a users gravatar profile image if they have one
     *
     * @param  string $email The email of the user
     * @param  int    $s
     * @param  string $d
     * @param  string $r
     * @return string
     */
    public function getGravatar($email, $s = 25, $d = 'mp', $r = 'g')
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";

        return $url;
    }
}
