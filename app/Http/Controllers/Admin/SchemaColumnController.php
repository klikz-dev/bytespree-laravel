<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Explorer\DestinationDatabaseTable;
use App\Models\Explorer\ManagedDatabase;

class SchemaColumnController extends Controller
{
    public function index($table_id)
    {
        $table = DestinationDatabaseTable::find($table_id);
        $schema = ManagedDatabase::find($table->managed_database_id);

        if (strlen($schema->name) > 20) {
            $schema_name = substr($schema->name, 0, 20) . "...";
            $schema_tooltip = TRUE;
        } else {
            $schema_name = $schema->name;
            $schema_tooltip = FALSE;
        }

        if (strlen($table->name) > 20) {
            $table_name = substr($table->name, 0, 20) . "...";
            $table_tooltip = TRUE;
        } else {
            $table_name = $table->name;
            $table_tooltip = FALSE;
        }

        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "Schema Builder",
                    "location" => "/admin/schemas"
                ],
                [
                    "title"          => $schema_name,
                    "location"       => "/admin/schemas/{$schema->id}/tables",
                    "isToolTip"      => $schema_tooltip,
                    "unTrimmedTitle" => $schema->name
                ],
                [
                    "title"          => $table_name,
                    "location"       => "/admin/schemas/{$schema->id}/tables/{$table->id}/columns",
                    "isToolTip"      => $table_tooltip,
                    "unTrimmedTitle" => $table->name
                ]
            ]
        );

        return view('schema_columns', compact('table_id'));
    }
}
