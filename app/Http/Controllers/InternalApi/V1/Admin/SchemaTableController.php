<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\DestinationDatabaseTable;

class SchemaTableController extends Controller
{
    public function list(int $schema_id)
    {
        return response()->success(DestinationDatabaseTable::where('managed_database_id', $schema_id)->get());
    }

    public function create(Request $request)
    {
        $request->validate([
            'managed_database_id' => 'required',
            'name'                => 'required'
        ]);
        
        DestinationDatabaseTable::create($request->all());

        return response()->success();
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'managed_database_id' => 'required',
            'name'                => 'required'
        ]);

        $site = DestinationDatabaseTable::find($id)->update($request->all());

        return response()->success();
    }

    public function destroy(int $id)
    {
        DestinationDatabaseTable::find($id)->delete();

        return response()->empty();
    }
}
