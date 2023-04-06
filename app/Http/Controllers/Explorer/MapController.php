<?php

namespace App\Http\Controllers\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Attributes\Can;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectColumnMapping;
use App\Classes\Database\Table;

class MapController extends Controller
{
    #[Can(permission: 'studio_access', product: 'studio')]
    public function show(Project $project, string $schema, string $table)
    {
        $this->setCrumbs(
            'studio',
            [
                [
                    "title"    => $project->name,
                    "location" => "/studio/projects/$project->id"
                ],
                [
                    "title"    => $table,
                    "location" => "/studio/projects/$project->id/tables/$schema/$table"
                ],
                [
                    "title"    => 'Conversion Map',
                    "location" => "/studio/projects/$project->id/tables/$schema/$table/map"
                ]
            ]
        );

        $vars = [
            "project_id" => $project->id,
            "table"      => $table,
            "schema"     => $schema
        ];

        return view('map', $vars);
    }

    public function download(Project $project, string $schema, string $table)
    {
        setcookie("downloadStarted", 1, time() + 60, '/', "", FALSE, FALSE);
        ob_clean();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', FALSE);
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment;filename={$table}_map.csv");

        $ordinals = Table::ordinals($project->primary_database, $schema, $table);
        $full_table_map = ProjectColumnMapping::fullTableMap($project, $schema, $table, $ordinals);

        $index = 0;
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

        ob_flush();
    }
}
