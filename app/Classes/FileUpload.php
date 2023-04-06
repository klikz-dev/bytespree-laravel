<?php

namespace App\Classes;

use GuzzleHttp\Client;
use Exception;

class FileUpload
{
    private $client = NULL;
    private $url = NULL;

    public function __construct(string $url)
    {
        $this->client = new Client();
        $this->url = rtrim($url, '/');
    }

    /**
     * Get information about a file from the upload service API
     *
     * @param  string      $transfer_token Transfer token of the file
     * @return object|null
     */
    public function getFileMetadata(string $transfer_token)
    {
        try {
            $response = $this->client->request('GET', "{$this->url}/transfer/{$transfer_token}");

            return json_decode($response->getBody());
        } catch (Exception $e) {
            return NULL;
        }
    }

    /**
     * Download a file from the upload service API
     *
     * @param  string $transfer_token    Transfer token
     * @param  string $storage_directory Storage directory
     * @param  string $filename          Filename
     * @return bool
     */
    public function downloadFile(string $transfer_token, string $storage_directory, string $filename)
    {
        $file_path = rtrim($storage_directory, '/') . '/' . $filename;

        try {
            $response = $this->client->request(
                'GET',
                "{$this->url}/download/{$transfer_token}",
                [
                    'headers' => [
                        'accept-encoding' => 'gzip, deflate'
                    ],
                    'sink' => $file_path
                ]
            );

            return file_exists($file_path);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Delete a file from the upload service; should only be done after successful transfers
     *
     * @param  string $transfer_token Transfer token of the file
     * @return bool
     */
    public function deleteFile(string $transfer_token)
    {
        try {
            $response = $this->client->request(
                'DELETE',
                "{$this->url}/transfer/{$transfer_token}"
            );

            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }
}
