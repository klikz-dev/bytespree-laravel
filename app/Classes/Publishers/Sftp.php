<?php

namespace App\Classes\Publishers;

use App\Models\SftpSite;
use ErrorException;
use Exception;

class Sftp extends Publisher
{
    private $source_path = NULL;
    private $connection = NULL;
    private $file_pointer = NULL;
    private $sftp = NULL;
    
    public $rows_published = 0;
    public $notify_users = TRUE; // Should BP_Publish notify users of success or failure?
    public $publisher_name = 'SFTP';
    public $sftp_site = NULL;
    public $sftp_path = NULL;
    public $sftp_file_name = NULL;

    public function __destruct()
    {
        if (! is_null($this->connection)) {
            // Disconnect if open, but don't bother us if it's already disconnected
            @ssh2_disconnect($this->connection);
        }
    }

    /**
     * Callback that is called when the columns from our publish job are available to us
     *
     * @param  array $columns An array of columns that are returned by the query
     * @return void
     */
    public function retrievedColumns(array $columns)
    {
        fputcsv($this->file_pointer, $this->filterUsedColumns($columns));
    }

    /**
     * Callback to be executed before our publish job runs. Set our out file path and create our file pointer for writing to it.
     *
     * @return void
     */
    public function beforePublish()
    {
        if (isset($this->destination_options->site_id)) {
            $this->sftp_site = SftpSite::find($this->destination_options->site_id);
        }

        if ($this->sftp_site) {
            $this->sftp_path = $this->buildFilePath();
        } else {
            return $this->sendResponse("SFTP site with ID {$this->destination_options->site_id} not found");
        }

        if ($this->destination_options->append_timestamp == TRUE) {
            $sftp_table_name = $this->source_table . "_" . date('Ymdhis') . ".csv";
        } else {
            $sftp_table_name = $this->source_table . ".csv";
        }

        $this->sftp_file_name = $this->sftp_path . "/" . $sftp_table_name;

        $this->sftp = $this->verifySftpConnection();

        if (substr($this->sftp_path, 0, 1) == ".") {
            $real_path = @ssh2_sftp_realpath($this->sftp, $this->sftp_path);
            $this->sftp_file_name = $real_path . "/" . $sftp_table_name;
        }

        $this->source_path = rtrim(config('app.attach_directory'), '/') . "/" . date('Ymdhis') . "_db_{$this->source_table}.csv";
        $this->file_pointer = fopen($this->source_path, 'w');

        if (! $this->file_pointer) {
            throw new Exception('The CSV file could not be created.');
        }
    }

    /**
     * Callback for when we retrieve a chunk of data from our outer SQL cursor
     *
     * @param  array $data Chunked data sent from the inherited class
     * @return void
     */
    public function chunk($data)
    {
        foreach ($data as $row) {
            ++$this->rows_published;
            fputcsv($this->file_pointer, (array) $row ?? []);
        }
    }

    /**
     * After publish callback
     *
     * @return void
     */
    public function onSuccess()
    {
        fclose($this->file_pointer);

        $this->sftp = $this->refreshSftpConnection($this->sftp);
        $sftp_stream = fopen('ssh2.sftp://' . intval($this->sftp) . "/" . $this->sftp_file_name, 'w');
        try {
            if (! $sftp_stream) {
                throw new Exception("Could not open remote file: $this->sftp_file_name");
            }

            $data_to_send = file_get_contents($this->source_path);

            if ($data_to_send === FALSE) {
                throw new Exception("Could not open local file: $this->source_path.");
            }

            if (fwrite($sftp_stream, $data_to_send) === FALSE) {
                throw new Exception("Could not send data from file: $this->source_path.");
            }

            unlink($this->source_path);
            fclose($sftp_stream);
        } catch (Exception $e) {
            unlink($this->source_path);
            fclose($sftp_stream);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Our error callback
     *
     * @return void
     */
    public function onError()
    {
        fclose($this->file_pointer);
    }

    /**
     * Builds the path to the sftp site based on the options sent in
     *
     * @return string
     */
    public function buildFilePath()
    {
        if (! empty($this->destination_options->path) && substr($this->destination_options->path, 0, 1) == "/") {
            $sftp_path = rtrim($this->destination_options->path, "/");
        } else {
            $default_path = rtrim($this->sftp_site->default_path, "/");
            if (empty($default_path)) {
                $default_path = ".";
            }
            if (! empty($this->destination_options->path)) {
                if (empty($default_path)) {
                    $sftp_path = rtrim($this->destination_options->path, "/");
                } else {
                    $sftp_path = $default_path . "/" . rtrim($this->destination_options->path, "/");
                }
            } else {
                $sftp_path = $default_path;
            }
        }

        return $sftp_path;
    }

    /**
     * Verifies if the sftp connection worked if not it will
     * return a json error or throw an error
     *
     * @return resource/array
     */
    public function verifySftpConnection()
    {
        $this->connection = @ssh2_connect($this->sftp_site->hostname, $this->sftp_site->port);

        if ($this->connection === FALSE) {
            return $this->sendResponse(sprintf("A connection to %s could not be established.", $this->sftp_site->hostname));
        }

        $auth = @ssh2_auth_password($this->connection, $this->sftp_site->username, $this->sftp_site->password);

        if (! $auth) {
            return $this->sendResponse("Authentication failed. Double check username and password.");
        }

        $sftp = @ssh2_sftp($this->connection);

        if ($sftp === FALSE) {
            return $this->sendResponse("There was a problem establishing an SFTP connection.");
        }

        $stat_info = @ssh2_sftp_stat($sftp, $this->sftp_path);

        if ($stat_info == FALSE) {
            return $this->sendResponse("Failed to find path");
        }

        // TODO: devise a way to effectively ensure we can write a file. is_writable only works on files, so we can't use it here. Maybe creating a temp file and then deleting it? But then we assume we have destroy permissions, too...

        if ($this->destination_options->append_timestamp == FALSE && $this->destination_options->overwrite_existing == FALSE) {
            $stat_info = @ssh2_sftp_stat($sftp, $this->sftp_file_name);

            if (! empty($stat_info)) {
                return $this->sendResponse("File already exists. You may need to overwrite the file.");
            }
        }

        return $sftp;
    }

    /**
     * Check the SSH/SFTP connection and reconnect if it is no longer available
     *
     * @param  resource  $sftp The current SFTP resource
     * @return resource
     * @throws Exception
     */
    public function refreshSftpConnection($sftp)
    {
        // Check if SFTP is working
        $stat_info = @ssh2_sftp_stat($sftp, $this->sftp_path);
        if ($stat_info === FALSE) {
            // Check if SSH is working
            $stream = @ssh2_exec($this->connection, '/bin/cat /dev/null');
            if ($stream === FALSE) {
                $this->connection = @ssh2_connect($this->sftp_site->hostname, $this->sftp_site->port);

                if ($this->connection === FALSE) {
                    return $this->sendResponse(sprintf("A connection to %s could not be established.", $this->sftp_site->hostname));
                }

                $auth = @ssh2_auth_password($this->connection, $this->sftp_site->username, $this->sftp_site->password);

                if (! $auth) {
                    return $this->sendResponse("Authentication failed. Double check username and password.");
                }
            }
            $sftp = @ssh2_sftp($this->connection);
        }

        return $sftp;
    }

    /**
     * Filter out unused columns and only return the used columns' titles (names/aliases)
     *
     * @param  array $columns A multidimensional array of columns
     * @return array The filtered columns' titles
     */
    protected function filterUsedColumns(array $columns)
    {
        $used_columns = array_filter($columns, function ($column) {
            return filter_var($column["checked"], FILTER_VALIDATE_BOOLEAN) === TRUE;
        });

        return array_map(function ($column) {
            return empty($column['alias']) ? $column['target_column_name'] : $column['alias'];
        }, $used_columns);
    }

    /**
     * A function that throws errors on a condition
     *
     * @param  string $message The message it is sending
     * @return array
     */
    public function sendResponse($message)
    {
        if (php_sapi_name() == 'cli') {
            fwrite(STDERR, $message . "\n");
        }
        throw new Exception($message);
    }

    /**
     * Get our notification data for any notifications
     *
     * @param  array $data The data sent in
     * @return array An array of data to be appended to our notification data
     */
    public function getNotificationData($data): array
    {
        return [
            'file_path' => $this->sftp_path,
            'host'      => $this->sftp_site->hostname ?? NULL,
            'port'      => $this->sftp_site->port ?? NULL,
            'username'  => $this->sftp_site->username ?? NULL,
        ];
    }
}
