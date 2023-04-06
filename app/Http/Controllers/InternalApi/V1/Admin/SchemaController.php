<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\Explorer\ManagedDatabase;
use App\Models\Explorer\DestinationDatabaseTable;
use App\Models\Explorer\DestinationDatabaseTableColumn;
use App\Classes\Database\Connection;

class SchemaController extends Controller
{
    public function list()
    {
        return response()->success(ManagedDatabase::get());
    }

    public function get(int $id)
    {
        return response()->success(ManagedDatabase::find($id));
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);
        
        ManagedDatabase::create($request->all());

        return response()->success();
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $site = ManagedDatabase::find($id)->update($request->all());

        return response()->success();
    }

    public function clone(Request $request, int $id)
    {
        $request->validate([
            'database_id' => 'required'
        ]);
        
        ManagedDatabase::find($id)->update(['cloned_from' => $request->database_id]);
        $this->cloneDatabaseSchema($id, $request->database_id);

        return response()->success();
    }

    public function resync(Request $request, int $id)
    {
        $site = ManagedDatabase::find($id);

        if (empty($site->cloned_from)) {
            return response()->error("Schema is currently not cloning any existing schema. If you wish to clone a schema delete all existing tables.");
        }

        DestinationDatabaseTable::where('managed_database_id', $id)->delete();
        $this->cloneDatabaseSchema($id, $site->cloned_from);

        return response()->success();
    }

    public function destroy(int $id)
    {
        ManagedDatabase::find($id)->delete();

        return response()->empty();
    }

    public function cloneDatabaseSchema(int $schema_id, int $database_id)
    {
        // This has not been tested I will loop back after we finish the warehouse page
        $database = PartnerIntegration::find($database_id);
        $datbase_connection = Connection::connect($database);

        $sql = <<<SQL
            SELECT * 
            FROM information_schema.tables
            WHERE
                table_schema = 'public' AND
                table_type = 'BASE TABLE' AND
                lower(table_name) NOT LIKE 'dropped_%' AND
                lower(table_name) NOT LIKE 'zd_%' AND
                lower(table_name) NOT LIKE 'z_%'
            SQL;

        $tables = $datbase_connection->select($sql);

        foreach ($tables as $table) {
            $managed_database_table = DestinationDatabaseTable::create([
                "managed_database_id" => $schema_id,
                "name"                => $table->table_name
            ]);

            $sql = <<<SQL
                SELECT *
                FROM information_schema.columns
                WHERE
                    table_schema = '$table->table_schema' AND
                    table_name = '$table->table_name'
                SQL;

            $columns = $datbase_connection->select($sql);

            foreach ($columns as $column) {
                switch ($column->udt_name) {
                    case 'varchar':
                        $length = $column->character_maximum_length;
                        $precision = NULL;
                        break;
                    case 'decimal':
                    case 'numeric':
                        $length = $column->numeric_precision;
                        $precision = $column->numeric_scale;
                        break;
                    case 'date':
                    case 'timestamp':
                        $length = NULL;
                        $precision = $column->datetime_precision;
                        break;
                    default:
                        $length = NULL;
                        $precision = NULL;
                }

                $data = [
                    'managed_database_table_id' => $managed_database_table->id,
                    'name'                      => $column->column_name,
                    'type'                      => $column->udt_name,
                    'length'                    => $length,
                    'precision'                 => $precision,
                ];

                DestinationDatabaseTableColumn::create($data);
            }
        }
    }
}
