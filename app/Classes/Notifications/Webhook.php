<?php

namespace App\Classes\Notifications;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use App\Classes\Notifications\Exceptions\GenericNotificationException;
use Exception;

class Webhook extends Notification
{
    /**
     * @var mixed Status code (response). NULL if not updated by this notification
     */
    protected $status_code = NULL;

    /**
     * @var mixed Status message (response). NULL if not updated by this notification
     */
    protected $status_message = NULL;

    /**
     * @var mixed Content type for the payload; defaults to ::DEFAULT_CONTENT_TYPE
     */
    protected $content_type = NULL;

    /**
     * @var array Payload options (what we're sending to the webhook endpoint)
     */
    protected $payload = [];

    /**
     * @var mixed payload to POST to endpoint
     */
    protected $payload_to_send = NULL;

    /**
     * @var mixed A secret string to use in hash verification, ignored if value is NULL
     */
    protected $secret = NULL;

    /**
     * @var mixed The URL to send the request to
     */
    protected $url = NULL;

    /**
     * @var GuzzleHttp\Client The Guzzle client to use for sending the request (can be overloaded via setClient())
     */
    private $client;

    /**
     * @var string Default content type for Guzzle to send
     */
    private const DEFAULT_CONTENT_TYPE = 'application/x-www-form-urlencoded';

    /**
     * @var array Acceptable content types for Guzzle to use; defaults to ::DEFAULT_CONTENT_TYPE
     */
    private const ACCEPTED_CONTENT_TYPES = [
        'application/json',
        'application/x-www-form-urlencoded', // Regular form posts
    ];

    /**
     * @var array Acceptable status codes; if Guzzle receives a status code not in this array, it will throw an exception
     */
    private const ACCEPTED_STATUS_CODES = [
        200, // OK
        201, // CREATED
        202, // ACCEPTED
        204, // NO CONTENT
    ];

    /**
     * @var int Timeout for Guzzle to use
     */
    private const CONNECTION_TIMEOUT = 15; // Request timeout, in seconds

    /**
     * Descriptive, innit?
     *
     * @return void
     */
    public function init()
    {
        $this->payload = $this->options;
        $this->url = $this->getSetting('payload_url');
        $this->secret = $this->getSetting('secret');
        $this->content_type = $this->getSetting('content_type');

        if (empty($this->content_type)) {
            $this->content_type = self::DEFAULT_CONTENT_TYPE;
        }

        $this->validateSettings();

        $this->client = new Client();
    }

    /**
     * Attempt to validate our settings
     *
     * @return void
     * @throws GenericNotificationException
     */
    public function validateSettings()
    {
        if (filter_var($this->url, FILTER_VALIDATE_URL) === FALSE) {
            throw new GenericNotificationException('Invalid URL provided.' . $this->url);
        }

        if (! in_array($this->content_type, self::ACCEPTED_CONTENT_TYPES)) {
            throw new GenericNotificationException("Invalid content type provided. {$this->content_type} is not a valid content type.");
        }

        if (empty($this->payload)) {
            throw new GenericNotificationException('Webhook notification called with an empty payload.');
        }
    }

    /**
     * Validate our webhook based on its current settings
     *
     * @return bool                         TRUE if the webhook settings appear to be valid, FALSE otherwise
     * @throws GenericNotificationException If a connection error happens (timeout, invalid status code, etc)
     */
    public function validate()
    {
        try {
            return $this->send();
        } catch (GenericNotificationException $e) {
            throw $e;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Send our request
     *
     * @return bool                         TRUE if successful, FALSE if not
     * @throws GenericNotificationException If a connection error happens (timeout, invalid status code, etc)
     */
    public function send(): bool
    {
        $this->payload_to_send = $this->buildPayload($this->payload);

        $payload_signature = $this->hashWithSecret($this->payload_to_send);

        try {
            $data = [
                'headers' => [
                    'Content-Type'          => $this->content_type,
                    'User-Agent'            => 'Bytespree',
                    'X-Bytespree-Signature' => $payload_signature
                ],
                'timeout'     => self::CONNECTION_TIMEOUT,
                'http_errors' => FALSE
            ];

            $data = array_merge($data, $this->getPayload());

            $response = $this->client->post(
                $this->url,
                $data
            );

            $this->status_code = (int) $response->getStatusCode();
            $this->status_message = (string) $response->getBody();

            if (in_array($this->status_code, self::ACCEPTED_STATUS_CODES)) {
                return TRUE;
            }

            throw new GenericNotificationException("Endpoint failed with status code {$this->status_code}.");
        } catch (ClientException|ConnectException $e) { // We don't want to give out any information about the exception
            throw new GenericNotificationException("Bytespree failed to connect to specified endpoint.");
        }

        return FALSE;
    }

    /**
     * Build our payload
     *
     * @return An array with Guzzle options we can merge into our request
     */
    public function getPayload(): array
    {
        if ($this->content_type == 'application/json') {
            return ['body' => $this->payload_to_send];
        }

        return ['form_params' => $this->payload_to_send];
    }

    /**
     * Build out the payload to send, e.g. encoding for JSON
     *
     * @param  array        $payload The payload to send
     * @return string|array The JSON encoded string or the key => value pair of the payload
     */
    public function buildPayload($payload)
    {
        if ($this->content_type == 'application/json') {
            return json_encode($payload);
        }

        return $payload;
    }

    /**
     * Hash our payload appropriately for a verifiable signature
     *
     * @return string The hashed payload
     */
    public function hashWithSecret($payload): string
    {
        if ($this->content_type !== 'application/json') {
            $payload = http_build_query($payload);
        }

        return hash_hmac('sha256', $payload, $this->secret);
    }

    /**
     * Set our client. Useful for testing/mocking
     *
     * @param GuzzleHttp\Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}
