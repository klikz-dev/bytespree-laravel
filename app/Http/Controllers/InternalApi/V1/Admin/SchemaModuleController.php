<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\DestinationDatabaseMappingModule;

class SchemaModuleController extends Controller
{
    public function list(int $schema_id)
    {
        $schema_modules = DestinationDatabaseMappingModule::where('managed_database_id', $schema_id)->get()->toArray();

        return response()->success(array_column($schema_modules, 'mapping_module_id'));
    }

    public function update(Request $request, int $schema_id)
    {
        $request->validate([
            'modules' => 'required'
        ]);

        DestinationDatabaseMappingModule::where('managed_database_id', $schema_id)->delete();

        foreach ($request->modules as $module) {
            DestinationDatabaseMappingModule::create([
                'managed_database_id' => $schema_id,
                'mapping_module_id'   => $module
            ]);
        }

        return response()->success();
    }
}
