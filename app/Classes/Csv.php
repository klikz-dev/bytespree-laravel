<?php

namespace App\Classes;

use App\Models\PartnerIntegration;
use App\Models\Attachment;
use App\Models\User;
use App\Models\Manager\ImportLog;
use App\Classes\Postmark;
use App\Classes\Database\Connection;
// revisit this later
use Storage;
use Exception;

/**
 * Class description
 */
class Csv
{
    public const BYTESPREE_PREFIX = '__bytespree';

    public static function columns(string $file_path, bool $has_columns, string $delimiter)
    {
        if (file_exists($file_path)) {
            $handle = fopen($file_path, "r");
            $record = fgetcsv($handle, 0, $delimiter);
            if ($has_columns == TRUE) {
                foreach ($record as $column) {
                    $columns[] = preg_replace('/[^0-9a-z_]/', '', str_replace(' ', '_', strtolower($column)));
                }
            } else {
                $index = 0;
                foreach ($record as $c) {
                    $columns[] = "column_$index";
                    ++$index;
                }
            }
            fclose($handle);
        } else {
            logger()->error("Failed to retrieve columns because CSV file was not found.", compact('file_path', 'has_columns'));
        }

        return $columns;
    }

    public static function import(array $data)
    {
        extract($data);
        extract($settings);

        $database = PartnerIntegration::find($database_id);
        $connection = Connection::connect($database);

        $indexes = $indexes ?? [];
        $num_columns = count($columns);
        $orig_file_name = $file_name;

        $old_file_name = $file_path;
        $file_name = config("app.upload_directory") . "/tmp" . uniqid("/imports_") . ".csv";

        if (! rename($old_file_name, $file_name)) {
            throw new Exception("File failed to rename");
        }

        if (! is_file($file_name)) {
            throw new Exception("File does not exist");
        }

        $file_lock = fopen($file_name, 'r');
        if (! $file_lock) {
            throw new Exception("File was not properly opened");
        }
        fseek($file_lock, 0, SEEK_SET);

        $batch_number = 1;
        $batch_size = 10000;
        $imported_rows = 0;
        $row_errors = [];
        $batch_data = [];
        $line_count = 0;
        $line_number = 0;

        $end_of_file = FALSE;
        if (! $ignore_errors) {
            $connection->beginTransaction();
        }

        if (empty($enclosed)) {
            $enclosed = chr(30);
        }

        do {
            // initialize vars / reset values to clear memory before continuing.
            $csv_line = FALSE;

            // read CSV line using import configuration settings. Then increase line count.
            $csv_line = fgetcsv($file_lock, 0, $delimiter, $enclosed, $escape);
            ++$line_number;

            // make sure that the line was read successfully.
            if ($csv_line === FALSE) {
                if (! feof($file_lock)) {
                    $message = "Failed to parse CSV contents at line $line_number...";
                    $row_errors[] = self::checkForIgnoreErrors($ignore_errors, $message, $csv_line, $data);
                    continue;
                }
            } else {
                // skip header row.
                if ($line_number === 1 && $has_columns) {
                    echo "Skipping Header Row...\n";
                    continue;
                }

                ++$line_count;
                $csv_line_size = count($csv_line);

                // make sure that the line actually contains at least one value.
                if (! array_filter($csv_line)) {
                    if ($ignore_empty) {
                        continue;
                    }

                    // it looks like this CSV line failed to parse, or the line that was parsed was empty.
                    $message = "Empty line detected at line $line_number...";
                    $row_errors[] = self::checkForIgnoreErrors($ignore_errors, $message, $csv_line, $data);
                    continue;
                }

                // make sure array size is same as number of columns.
                if ($csv_line_size < $num_columns) {
                    // it looks like this CSV line doesn't contain values for all columns.
                    $message = "File line $line_number only defines values for $csv_line_size out of $num_columns fields...";
                    $row_errors[] = self::checkForIgnoreErrors($ignore_errors, $message, $csv_line, $data);
                    continue;
                } elseif ($csv_line_size > $num_columns) {
                    // it looks like this CSV line contains too many values...
                    $message = "File line $line_number defines too many values for $csv_line_size out of $num_columns fields...";
                    $row_errors[] = self::checkForIgnoreErrors($ignore_errors, $message, $csv_line, $data);
                    continue;
                }

                // Checks all columns and makes sure the values match the column definition
                foreach ($columns as $key => $column) {
                    $column_name = $column["column_name"] ?? $column["column"];
                    if (substr($column_name, 0, strlen(self::BYTESPREE_PREFIX)) == self::BYTESPREE_PREFIX) {
                        if (empty($columns[$key]["column_name"])) {
                            $columns[$key]["column_name"] = $column["column"];
                        }
                        continue;
                    }

                    $failed = self::validateColumn($column, $csv_line[$key]);

                    if ($failed == TRUE) {
                        $message = "File line $line_number values did not match column definition";
                        $row_errors[] = self::checkForIgnoreErrors($ignore_errors, $message, $csv_line, $data);
                        continue 2;
                    }

                    if (empty($columns[$key]["column_name"])) {
                        $columns[$key]["column_name"] = $column["column"];
                    }
                }

                // loop through each value found within this line.
                for ($c = 0; $c < count($columns); ++$c) {
                    if (isset($csv_line[$c])) {
                        // remove trailing characters from the value (not preceding).
                        $csv_line[$c] = rtrim($csv_line[$c]);

                        // Convert incoding based on what the user supplies
                        $csv_line[$c] = mb_convert_encoding($csv_line[$c], $encoding);
                    }
                }

                // row compiled successfully, append it to batch rows.
                $batch_data[] = array_combine(array_column($columns, "column_name"), $csv_line);
            }

            if (feof($file_lock)) {
                $end_of_file = TRUE;
            }

            if (! empty($batch_data) && (count($batch_data) >= $batch_size || $end_of_file == TRUE)) {
                echo "Loading batch $batch_number...\n";
                foreach ($batch_data as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        if (substr($key2, 0, strlen(self::BYTESPREE_PREFIX)) == self::BYTESPREE_PREFIX) {
                            unset($batch_data[$key][$key2]);
                        }
                    }
                }

                $failed_rows = [];
                $result = self::insertData($connection, $table_name, $batch_data, $ignore_errors, $data);

                if ($result) {
                    $imported_rows += count($batch_data);
                } else {
                    $failed_rows = self::checkBatchData($connection, $table_name, $batch_data);
                    $imported_rows += count($batch_data) - count($failed_rows);
                }

                $batch_data = [];
                ++$batch_number;

                foreach ($failed_rows as $failed_row) {
                    $row_errors[] = $failed_row;
                }
            }
        } while (! $end_of_file);

        echo "File import complete! Loaded $imported_rows rows out of $line_count lines in file!\n";
        fclose($file_lock);

        if (! $ignore_errors) {
            $connection->commit();
        }

        if (file_exists($file_name)) {
            unlink($file_name);
        }

        $errored_indexes = array_filter($indexes, function ($index) {
            return $index['created'] !== TRUE;
        });

        $additional_body = "";

        if (count($errored_indexes) > 0) {
            $errored_indexes = array_column($errored_indexes, 'column');
            $additional_body = "<br /><br />The following indexes could not be recreated:<br /><ul><li>" . implode('</li><li>', $errored_indexes) . '</ul>';
        }

        if (count($row_errors) > 0) {
            $status = "Successful with Errors";
            $error_report_file = rtrim(config('app.attach_directory'), '/') . "/csv_error_report_" . uniqid() . ".csv";
            $error_report = fopen($error_report_file, 'w');

            $column_names = array_column($columns, 'column_name');
            array_unshift($column_names, self::BYTESPREE_PREFIX . "_failure_reason");

            fputcsv($error_report, $column_names);
            foreach ($row_errors as $row_error) {
                fputcsv($error_report, $row_error);
            }

            fclose($error_report);

            $attachment = Attachment::create([
                'control_id' => $database_id,
                'user_id'    => $user_handle,
                'path'       => $error_report_file,
                'file_name'  => basename($error_report_file)
            ]);

            $prefix = self::BYTESPREE_PREFIX;
            $download_link = rtrim(config('app.url'), '/') . "/data-lake/database-manager/$database_id/attachments/$attachment->id";

            self::sendImportNotification(
                $database_id,
                $user_handle,
                $team,
                $table_name,
                "Your recent table import has failed. Please download the file from the link above and open it in Excel or a similar data viewer. You will find the reason why the row failed in the {$prefix}_failure_reason column and may correct the reported issue, save the file, and re-upload the file to Bytespree as an append.",
                "danger",
                $download_link,
                $additional_body
            );
        } else {
            $status = "Success";

            $heading = "Your recent table import has succeeded with no issues!";

            if (count($errored_indexes) > 0) {
                $heading = "You recent table import is complete.";
            }

            self::sendImportNotification(
                $database_id,
                $user_handle,
                $team,
                $table_name,
                $heading,
                'success',
                "",
                ""
            );
        }

        $user = User::handle($user_handle);
        $insert_data = [
            "control_id"       => $database_id,
            "user_id"          => $user->id,
            "table_id"         => $table_id,
            "file_name"        => $orig_file_name,
            "file_size"        => $file_size,
            "table_name"       => $table_name,
            "type"             => $type,
            "status"           => $status,
            "records_imported" => $imported_rows,
            "records_in_error" => count($row_errors),
            "settings"         => $settings,
            "columns"          => $columns,
            "mappings"         => empty($mappings) ? [] : $mappings,
            "ip_address"       => $ip_address
        ];

        ImportLog::create($insert_data);
    }

    public static function insertData($connection, string $table_name, array $data, bool $ignore_errors, array $run_data, bool $retry = FALSE)
    {
        // Try to insert the data, but batch/chunk it into pieces of 100 rows at a time. Maybe it's worth extending the DB class, but I'm not sure at this point.
        // Note: Original CI batch_insert used 100, and we never overrrode it.
        try {
            $total = count($data);
            $chunk_size = 100;
            for ($i = 0; $i < $total; $i += $chunk_size) {
                $result = $connection->table($table_name)->insert(
                    array_slice($data, $i, $chunk_size)
                );
            }
        } catch (Exception $e) {
            $result = FALSE;
        }

        if (! $result) {
            if (! $ignore_errors) {
                $connection->rollBack();
                self::handleFailure($run_data);
                throw new Exception("Batch failed to insert. Rolling back transaction");
            } else if ($retry == FALSE) {
                return self::insertData($connection, $table_name, $data, $ignore_errors, $run_data, TRUE);
            }
        }

        return $result;
    }

    public static function checkBatchData($connection, string $table_name, array $data)
    {
        $failed_rows = [];
        foreach ($data as $value) {
            try {
                $result = $connection->table($table_name)->insert($value);
            } catch (Exception $e) {
                $result = FALSE;
            }

            if (! $result) {
                array_unshift($value, "Row failed to insert");
                $failed_rows[] = $value;
            }
        }

        return $failed_rows;
    }

    public static function validateColumn(array $column, string $value)
    {
        $type = $column["type"] ?? $column["udt_name"];
        switch ($type) {
            case 'varchar':
                $max_length = $column["character_maximum_length"] ?? $column["value"];
                if (strlen($value) > $max_length) {
                    return TRUE;
                }
                break;
            case 'int':
            case 'int4':
                if (ctype_digit($value)) {
                    if ($value > 2147483647) {
                        return TRUE;
                    }
                } else {
                    return TRUE;
                }
                break;
            case 'bigint':
            case 'int8':
                if (ctype_digit($value)) {
                    if ($value > '9223372036854775807') {
                        return TRUE;
                    }
                } else {
                    return TRUE;
                }
                break;
            case 'decimal':
            case 'numeric':
                if (is_numeric($value)) {
                    $precision = $column["character_maximum_length"] == '' ? $column["numeric_precision"] : $column["character_maximum_length"];
                    $scale = $column["precision"] ?? $column["numeric_scale"];
                    if (strpos($value, ".")) {
                        if (strlen($value) - 1 > $precision || strlen(explode(".", $value)[1]) > $scale) {
                            return TRUE;
                        }
                    } else {
                        // Hard coded to 1 because postgres returns 10 as the 'numeric_scale' no matter what the user enters
                        if (strlen($value) > $precision - 1) {
                            return TRUE;
                        }
                    }
                } else {
                    return TRUE;
                }
                break;
            case 'jsonb':
                try {
                    $test = json_decode($value);

                    if (json_last_error() != JSON_ERROR_NONE) {
                        return TRUE;
                    }
                } catch (Exception $e) {
                    return TRUE;
                }
                break;
        }

        return FALSE;
    }

    public static function checkForIgnoreErrors(bool $ignore_errors, string $message, $row, array $data)
    {
        if ($ignore_errors) {
            echo $message . "\n";
            array_unshift($row, $message);

            return $row;
        }

        self::handleFailure($data);
        throw new Exception($message);
    }

    public static function handleFailure(array $data)
    {
        extract($data);
        $user = User::handle($user_handle);

        self::sendImportNotification(
            $database_id,
            $user_handle,
            $team,
            $table_name,
            "Your recent table import has failed. Since you did not ignore errors, no data was modified. If you wish to import this data, please correct your file and reupload it to Bytespree.",
            "danger"
        );

        $insert_data = [
            "control_id"       => $database_id,
            "table_id"         => $table_id,
            "user_id"          => $user->id,
            "file_name"        => $file_name,
            "file_size"        => $file_size,
            "table_name"       => $table_name,
            "type"             => $type,
            "status"           => "Failure",
            "records_imported" => 0,
            "records_in_error" => 0,
            "settings"         => $settings,
            "columns"          => $columns,
            "mappings"         => empty($mappings) ? [] : $mappings,
            "ip_address"       => $ip_address
        ];

        ImportLog::create($insert_data);
    }

    public static function sendImportNotification(int $database_id, string $user_handle, string $team, string $table_name, string $message, string $type = "success", string $link = "", string $additional_body = "")
    {
        $database = PartnerIntegration::find($database_id);
        
        if ($type == "success") {
            $subject = "Table import for $table_name in database $database->database has succeeded for team $team.";
        } else {
            $subject = "Table import for $table_name in database $database->database has failed for team $team.";
        }

        $message .= $additional_body;

        app('orchestration')->addNotification(
            $user_handle,
            $team,
            $subject,
            $message,
            $type,
            $link
        );

        $user = User::handle($user_handle);
        if (! empty($user)) {
            if (! empty($link)) {
                $message = "<a href=\"$link\" traget=\"_blank\">Click here to download</a> <p>$message</p>";
            }

            $user_name = "$user->first_name $user->last_name";

            Postmark::send(
                $user->email,
                "table-import",
                [
                    "user_name" => $user_name,
                    "subject"   => $subject,
                    "body"      => $message
                ]
            );
        } else {
            echo "Email failed to send user was not found!";
        }
    }

    public static function removeBytespreeColumns($csv_columns, $database_columns)
    {
        return array_map(function ($column) use ($database_columns) {
            $column_arr = [];
            if (substr($column, 0, strlen(self::BYTESPREE_PREFIX)) == self::BYTESPREE_PREFIX) {
                return ["column_name" => $column];
            }  
            $database_column = array_filter($database_columns, function ($database_column) use ($column) {
                return $database_column->column_name == $column;
            });

            if (! empty($database_column)) {
                return (array) array_shift($database_column);
            }
        }, $csv_columns);
    }
}
