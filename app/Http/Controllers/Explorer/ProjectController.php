<?php

namespace App\Http\Controllers\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectAttachment;
use App\Models\Explorer\ProjectColumnMapping;
use App\Classes\Database\Table;
use App\Attributes\Can;

class ProjectController extends Controller
{
    #[Can(permission: 'studio_access', product: 'studio', id: 'project.id')]
    public function show(Project $project)
    {
        $this->crumbs($project);

        return view('project', [
            "project_id"            => $project->id,
            "destination_schema_id" => $project->destination_schema_id ?? 0,
            "completed"             => $project->primary_database->is_complete,
            "max_size"              => ini_get('upload_max_filesize'),
            "from_download_link"    => FALSE,
            "file_upload_url"       => config('services.file_upload.url')
        ]);
    }

    #[Can(permission: 'read_attach', product: 'studio', id: 'project.id')]
    public function attachment(Request $request, Project $project, ProjectAttachment $attachment)
    {
        if ($request->has('download')) {
            $this->download($attachment);
        } else {
            $this->crumbs($project);
    
            return view('project', [
                "project_id"            => $project->id,
                "destination_schema_id" => $project->destination_schema_id ?? 0,
                "completed"             => $project->primary_database->is_complete,
                "max_size"              => ini_get('upload_max_filesize'),
                "from_download_link"    => TRUE,
                "file_upload_url"       => config('services.file_upload.url')
            ]);
        }
    }

    public function downloadMappings(Request $request, Project $project)
    {
        setcookie("downloadStarted", 1, time() + 60, '/', "", FALSE, FALSE);
        ob_clean();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', FALSE);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $project->primary_database->database . '_project_mappings.csv');

        $tables = Table::list($project->primary_database, [$project->primary_database->database => $project->name]);
        $index = 0;
        foreach ($tables as $table) {
            $ordinals = Table::ordinals($project->primary_database, $table->table_schema, $table->table_name);
            $full_table_map = ProjectColumnMapping::fullTableMap($project, $table->table_schema, $table->table_name, $ordinals);

            if ($full_table_map->count() > 0) {
                foreach ($full_table_map as $key => $row) {
                    foreach ($row as $column_name => $value) {
                        if ($column_name == 'module_data') {
                            $full_table_map[$key]->$column_name = json_encode($value, JSON_FORCE_OBJECT);
                        }
                    }
                }

                $fp = fopen('php://output', 'w');
                if ($index == 0) {
                    fputcsv($fp, array_keys((array) $full_table_map['0']));
                }

                foreach ($full_table_map as $values) {
                    fputcsv($fp, (array) $values);
                }

                fclose($fp);
                $index = $index + 1;
            }
        }
        ob_flush();
    }
    
    public function download(ProjectAttachment $attachment)
    {
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

    public function crumbs(Project $project)
    {
        $this->setCrumbs(
            'studio',
            [
                [
                    "title"    => $project->display_name,
                    "location" => "/studio/projects/{$project->id}"
                ]
            ]
        );
    }
}
