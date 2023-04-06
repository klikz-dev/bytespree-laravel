<?php

namespace App\Http\Controllers\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Classes\Database\Table;

class MapController extends Controller
{
    public function index(Request $request, int $database_id)
    {
        $is_replacing = $request->replace == 'true';
        $is_appending = $request->append == 'true';
        $table_columns = [];

        if ($is_appending || $is_replacing) {
            $table_name = $request->table_name;
            $database = PartnerIntegration::find($database_id);
            $table_columns = array_column(Table::columns($database, 'public', $table_name), 'column_name');
        }

        if (empty($table_name)) {
            $table_name = preg_replace('/[^0-9a-z_]/', '', str_replace(' ', '_', strtolower(basename($request->file_name, '.csv'))));
        }

        $this->setCrumbs(
            'datalake',
            [
                [
                    "title"    => $request->database_name,
                    "location" => "/data-lake/database-manager/{$database_id}"
                ],
                [
                    "title"    => $table_name,
                    "location" => ""
                ]
            ]
        );

        $vars = [
            "database_id"   => $database_id,
            "table_id"      => $request->table_id,
            "table_name"    => $table_name,
            "file_name"     => $request->file_name,
            "ignore_errors" => $request->ignore_errors,
            "ignore_empty"  => $request->ignore_empty,
            "has_columns"   => $request->has_columns,
            "delimiter"     => str_replace('\\', '\\\\', $request->delimiter),
            "encoding"      => $request->encoding,
            "enclosed"      => $request->enclosed,
            "escape"        => str_replace('\\', '\\\\', $request->escape),
            "max_size"      => ini_get('upload_max_filesize'),
            "is_replacing"  => $is_replacing,
            "is_appending"  => $is_appending,
            "table_columns" => json_encode($table_columns)
        ];

        return view('uploads', $vars);
    }
}
