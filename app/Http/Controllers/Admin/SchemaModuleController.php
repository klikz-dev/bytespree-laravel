<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Explorer\ManagedDatabase;

class SchemaModuleController extends Controller
{
    public function index($schema_id)
    {
        $schema = ManagedDatabase::find($schema_id);
        if (strlen($schema->name) > 20) {
            $name = substr($schema->name, 0, 20) . "...";
            $tooltip = TRUE;
        } else {
            $name = $schema->name;
            $tooltip = FALSE;
        }

        $this->setCrumbs(
            'admin',
            [
                [
                    "title"    => "Schema Builder",
                    "location" => "/admin/schemas"
                ],
                [
                    "title"          => $name,
                    "location"       => "/admin/schemas/{$schema_id}/tables",
                    "isToolTip"      => $tooltip,
                    "unTrimmedTitle" => $schema->name
                ],
                [
                    "title"    => "Manage Modules",
                    "location" => ""
                ]
            ]
        );

        return view('schema_modules', compact('schema_id'));
    }
}
