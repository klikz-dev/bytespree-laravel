<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectColumnAttachment;
use App\Classes\FileUpload;
use App\Models\Explorer\ProjectHyperlink;
use App\Attributes\Can;
use Exception;

class TableFileController extends Controller
{
    #[Can(permission: 'read_attach', product: 'studio', id: 'project.id')]
    public function list(Request $request, Project $project, string $schema, string $table)
    {
        $files = [];

        ProjectColumnAttachment::where('project_id', $project->id)
            ->where('schema_name', $schema)
            ->where('table_name', $table)
            ->get()
            ->each(function ($file) use (&$files) {
                $files[$file->table_name . '_' . $file->column_name][] = $file;
            });

        return response()->success($files);
    }

    #[Can(permission: 'link_write', product: 'studio', id: 'project.id')]
    public function store(Request $request, Project $project, string $schema, string $table)
    {
        $column = $request->column;
        $transfer_token = $request->transfer_token;

        $path = rtrim(config('app.attach_directory'), '/') . '/';

        if (! is_dir($path)) {
            if (mkdir($path, 0777) === FALSE) {
                return response()->error('Upload directory does not exist');
            }
        }

        try {
            $upload = new FileUpload(config('services.file_upload.url'));

            $meta = $upload->getFileMetadata($transfer_token);

            if (empty($meta)) {
                logger()->error('Could not get uploaded file from upload service.');
    
                return response()->error('Your file could not be uploaded.');
            }

            $result = $upload->downloadFile($transfer_token, $path, $meta->filename);

            if (! $result) {
                throw new Exception("Could not upload file.");
            }

            $upload->deleteFile($transfer_token);

            $file = ProjectColumnAttachment::create([
                'project_id'  => $project->id,
                'user_id'     => $request->user()->user_handle,
                'path'        => $path . '/' . $meta->filename,
                'file_name'   => $meta->filename,
                'table_name'  => $table,
                'column_name' => $request->column,
                'schema_name' => $schema,
            ]);

            return response()->success();
        } catch (Exception $exception) {
            logger()->error('Could not get uploaded file from upload service.', compact('exception', 'transfer_token'));

            return response()->error($exception->getMessage());
        }
    }
    
    #[Can(permission: 'link_write', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project, string $schema, string $table, ProjectColumnAttachment $file)
    {
        if ($project->is_complete) {
            $project->sendCompletedEmail("Attachment", "deleted from", $table, $file->column_name, $request->user()->name);
        }

        $file->delete();

        return response()->success();
    }
}
