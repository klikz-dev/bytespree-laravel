<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\FileUpload;
use App\Classes\CompressedFile;
use App\Classes\Csv;
use Exception;

class UploadController extends Controller
{
    public function get(string $token)
    {
        $upload_service = new FileUpload(config('services.file_upload.url'));

        $meta = $upload_service->getFileMetadata($token);

        if (empty($meta)) {
            return response()->error('Your file could not be uploaded.');
        }

        $upload_directory = config("app.upload_directory") . "/tmp/";

        if (file_exists($upload_directory) === FALSE) {
            $message = "Unable to upload file. Upload directory does not exist.";
            logger()->error($message, compact('upload_directory', 'meta'));
            
            return response()->error($message);
        }

        $file_name_to_use = $meta->filename . "_" . date("YmdHis");

        try {
            $result = $upload_service->downloadFile($token, $upload_directory, $file_name_to_use);
        } catch (Exception $e) {
            $result = FALSE;
        }

        if (! $result) {
            logger()->error('Unable to download file from the upload service.', compact('upload_directory', 'token', 'meta', 'file_name_to_use'));

            return $this->_sendAjax("error", 'An error occurred when uploading your file.', [], 500);
        }

        // Do we need to do any post-transfer processing? (unzipping a file)
        $extension = strtolower(pathinfo($meta->filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['zip'])) {
            try {
                $compressed_file = new CompressedFile($upload_directory . '/' . $file_name_to_use);

                if ($compressed_file->count() < 1 || $compressed_file->count() > 1) {
                    return response()->error('Your ZIP file could not be processed.');
                }

                $files = $compressed_file->files();

                $file_info = pathinfo($files[0]);
                $extension = strtolower($file_info['extension'] ?? '');
                $accepted_extensions = ['csv', 'txt', 'pipe', 'psv', 'tab', 'tsv'];

                if (! in_array($extension, $accepted_extensions)) {
                    return response()->error('Only the following formats can be imported: ' . implode(', ', $accepted_extensions));
                }

                $compressed_file->extractFile($file_info['basename'], $upload_directory . '/' . $file_info['basename']);
            } catch (Exception $e) {
                return response()->error('Your ZIP file could not be processed.');
            } finally {
                unlink($upload_directory . '/' . $file_name_to_use); // Remove the original zip file
            }

            $file_name_to_use = $file_info['basename'];
        }

        return response()->success($file_name_to_use);
    }

    public function columns(string $file_name, bool $has_columns, string $delimiter)
    {
        $delimiter = str_replace('\t', "\t", $delimiter);
        $upload_dir = config("app.upload_directory") . '/tmp';
        $upload_file = $upload_dir . '/' . $file_name;

        $csv_columns = Csv::columns($upload_file, $has_columns, $delimiter);

        $columns = [];
        foreach ($csv_columns as $column) {
            $columns[] = [
                "column"    => $column,
                "value"     => 500,
                "type"      => "varchar",
                "precision" => 0
            ];
        }

        return response()->success($columns);
    }

    public function create(Request $request)
    {
        $token = app('orchestration')->createUploadToken(auth()->user()->id, $request->ip(), app('environment')->getTeam());

        return response()->success(compact('token'));
    }
}
