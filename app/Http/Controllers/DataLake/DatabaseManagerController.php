<?php

namespace App\Http\Controllers\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\Attachment;
use App\Attributes\Can;

class DatabaseManagerController extends Controller
{
    #[Can(permission: '*', product: 'datalake', id: 'database.id')]
    public function index(PartnerIntegration $database)
    {
        $upload_url = rtrim(config('services.file_upload.url'), '/');
        $this->crumbs($database);

        return view('database_manager', [
            'database_id'        => $database->id,
            'from_download_link' => FALSE,
            'upload_url'         => $upload_url
        ]);
    }

    public function attachment(Request $request, int $database_id, int $attachment_id)
    {
        if ($request->has('download')) {
            $this->download($attachment_id);
        } else {
            $database = PartnerIntegration::find($database_id);
            $upload_url = rtrim(config('services.file_upload.url'), '/');
            $this->crumbs($database);

            return view('database_manager', [
                'database_id'        => $database_id,
                'from_download_link' => TRUE,
                'upload_url'         => $upload_url
            ]);
        }
    }
    
    public function download(int $attachment_id)
    {
        $attachment = Attachment::find($attachment_id);
        if ($attachment->user_id != auth()->user()->user_handle) {
            return redirect('/data-lake');
        }

        header('Content-Disposition: attachment; filename="' . $attachment->file_name . '"');
        header("Content-Type: application/download");
        header("Content-Description: File Transfer");
        header("Content-Length: " . filesize($attachment->path));

        // todo fix this to not update memory limit
        ini_set('memory_limit', '2048M');
        $chunk_size = 5 * (1024 * 1024);
        $fp = fopen($attachment->path, "r");
        while (! feof($fp)) {
            echo fread($fp, $chunk_size);
            ob_flush();
            flush();
        }

        fclose($fp);
        ini_set('memory_limit', '512M');
    }

    public function create()
    {
        // $this->checkWarehouseAccess();
        // $this->checkCreateDb();

        $vars = [
            'system_timezone'    => date('T'),
            'system_time_offset' => date('Z')
        ];

        echo view("database", $vars);

        // $this->setCrumbs(
        //     'warehouse',
        //     [
        //         [
        //             "title"    => "Create New Database",
        //             "location" => "/Database"
        //         ]
        //     ]
        // );

        // // Try to load any connector front-end code, if they have any via the getDatabaseFrontend() model method
        // foreach ($this->ConnectorsModel->getAll() as $connector) {
        //     $class_name = $connector['class_name'] . 'Model';

        //     try {
        //         $this->load->model('Integrations/' . $class_name);

        //         if (method_exists($this->{$class_name}, 'getDatabaseFrontend')) {
        //             $this->output->append_output($this->{$class_name}->getDatabaseFrontend());
        //         }
        //     } catch (Exception $e) {
        //         // ...
        //     }
        // }
    }

    public function crumbs(PartnerIntegration $database)
    {
        $this->setCrumbs(
            'datalake',
            [
                [
                    "title"    => $database->database,
                    "location" => "/data-lake/database-manager/$database->id"
                ]
            ]
        );
    }
}
