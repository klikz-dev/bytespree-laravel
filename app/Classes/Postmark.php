<?php

namespace App\Classes;

use GuzzleHttp\Client;
use Exception;

/**
 * Class description
 */
class Postmark
{
    private static $url = "https://api.postmarkapp.com";

    /**
     * Send Postmark template using Postmark API
     *
     * @param  string $to       The email address or addresses to template to
     * @param  string $template The name of the template to send
     * @param  string $data     The variable data for the template
     * @return bool
     */
    public static function send($to, $template, $data)
    {
        $from = "Bytespree <" . config('mail.from.address') . ">";

        if (is_array($to)) {
            $to = implode(', ', $to);
        }

        if (! app()->isProduction()) {
            if (empty(config('mail.dev.email'))) {
                throw new Exception("DI_DEV_EMAIL is empty you need to set this in your .env file for emails to work.");
            }
            if (! isset($data['intended_recipient'])) {
                if (is_array($to)) {
                    $to = implode(', ', $to);
                }

                $data['intended_recipient'] = [
                    'email' => $to
                ];
            }
            $to = config('mail.dev.email');
        }

        $client = new Client([
            'base_uri' => self::$url
        ]);

        $body = [
            "From"          => $from,
            "To"            => $to,
            "TemplateAlias" => $template,
            "TemplateModel" => $data
        ];

        try {
            $response = $client->request(
                'POST',
                "email/withTemplate",
                [
                    'headers' => [
                        'Accept'                  => 'application/json',
                        'X-Postmark-Server-Token' => config('services.postmark.api_key')
                    ],
                    'body'        => json_encode($body),
                    'http_errors' => FALSE
                ]
            );
        } catch (Exception $e) {
            logger()->error(
                $e->getMessage(),
                [
                    "to"       => $to,
                    "template" => $template
                ]
            );

            return FALSE;
        }
        $status_code = $response->getStatusCode();
        $result = (string) $response->getBody();

        $json = [];
        if ($result) {
            $json = json_decode($result, TRUE);
            if ($status_code == 200) {
                return TRUE;
            }
        }

        logger()->error(
            "Failed to send email. (if json is empty guzzle returned false)",
            [
                "to"       => $to,
                "template" => $template,
                "json"     => $json
            ]
        );

        return FALSE;
    }
}
