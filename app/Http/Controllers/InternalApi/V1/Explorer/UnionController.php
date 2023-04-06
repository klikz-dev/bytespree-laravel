<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Classes\Database\Table;
use App\Http\Controllers\Controller;
use App\Models\Explorer\Project;
use Illuminate\Http\Request;

class UnionController extends Controller
{
    public function test(Request $request, Project $project, string $schema, string $table)
    {
        $request->validate([
            'columns' => 'required|array',
            'table'   => 'required|string',
            'schema'  => 'required|string',
        ]);

        $expected_column_count = count($request->input('columns', []));
        $union_table = $request->table;
        $union_schema = $request->schema;

        $columns = collect(Table::columns($project->primary_database, $union_schema, $union_table))
            ->pluck('column_name')
            ->values()
            ->toArray();

        if (count($columns) !== $expected_column_count) {
            return response()->error('Column count from unioned table does not match original table.');
        }

        return response()->success(compact('columns'));
    }
}
