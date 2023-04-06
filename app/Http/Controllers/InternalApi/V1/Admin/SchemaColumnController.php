<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\DestinationDatabaseTableColumn;

class SchemaColumnController extends Controller
{
    public function list(int $table_id)
    {
        return response()->success(DestinationDatabaseTableColumn::where('managed_database_table_id', $table_id)->get());
    }

    public function create(Request $request)
    {
        $request->validate([
            'managed_database_table_id' => 'required',
            'name'                      => 'required',
            'type'                      => 'required'
        ]);
        
        DestinationDatabaseTableColumn::create($request->all());

        return response()->success();
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'managed_database_table_id' => 'required',
            'name'                      => 'required',
            'type'                      => 'required'
        ]);

        $site = DestinationDatabaseTableColumn::find($id)->update($request->all());

        return response()->success();
    }

    public function destroy(int $id)
    {
        DestinationDatabaseTableColumn::find($id)->delete();

        return response()->empty();
    }
}
