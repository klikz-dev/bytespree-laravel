<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationForeignDatabase;
use App\Models\Product;
use App\Classes\Database\Connection;
use App\Classes\Database\Table;
use App\Classes\Database\ForeignDatabase;
use App\Attributes\Can;

class ForeignDatabaseController extends Controller
{
    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function tables(PartnerIntegration $database)
    {
        $schemas = PartnerIntegrationForeignDatabase::schemas($database, "dataLake");

        if (empty($schemas)) {
            return response()->success();
        }

        return response()->success(["tables" => Table::list($database, $schemas, FALSE), "schemas" => $schemas]);
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function unused(PartnerIntegration $database)
    {
        $used_database_ids = PartnerIntegrationForeignDatabase::dataLake()
            ->where('control_id', $database->id)
            ->get()
            ->map(function ($foreign_database) {
                return $foreign_database->foreign_control_id;
            });
        
        $used_database_ids->push($database->id);

        return response()->success(PartnerIntegration::whereNotIn('id', $used_database_ids)->get());
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function create(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'foreign_database_id' => 'required',
            'schema_name'         => 'required'
        ]);

        $data_lake = Product::where('name', 'datalake')->first();
        $foreign_database = PartnerIntegration::find($request->foreign_database_id);
        $server_name = uniqid("d");

        $schemas = Connection::getSchemas($database, TRUE);

        if (in_array($request->schema_name, $schemas)) {
            return response()->error("Schema already exists please choose another schema name.");
        }

        $created = ForeignDatabase::create($database, $foreign_database, $server_name, $request->schema_name);

        if ($created) {
            PartnerIntegrationForeignDatabase::create([
                "control_id"          => $database->id,
                "foreign_control_id"  => $request->foreign_database_id,
                "product_id"          => $data_lake->id,
                "schema_name"         => $request->schema_name,
                "foreign_server_name" => $server_name
            ]);

            return response()->success();
        }
  
        return response()->error("Failed to create new foreign database.");
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function refresh(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'schema' => 'required',
            'table'  => 'required'
        ]);

        $foreign_database = PartnerIntegrationForeignDatabase::dataLake()
            ->where('control_id', $database->id)
            ->where('schema_name', $request->schema)
            ->first();

        if ($foreign_database->count() == 0) {
            return response()->error("Foreign database not found.");
        }

        // Does this schema have dependents?
        $dependents = Connection::getObjectDependencies($database, $request->schema, $request->table);

        if (! empty($dependents)) {
            $dependents = array_map(function ($dependent) {
                return $dependent['schema'] . '.' . $dependent['name'];
            }, $dependents);

            return response()->error("Foreign table cannot be refreshed because it has dependent views.", compact('dependents'));
        }

        if (! ForeignDatabase::refreshTable($database, $foreign_database, $request->schema, $request->table)) {
            return response()->error("There was an issue refreshing this foreign table.");
        }

        // Verify the foreign table exists, in case it was delete in the source database
        if (! Table::exists($database, $request->schema, $request->table)) {
            return response()->error("Foreign table appears to have been deleted in its source schema.", ['deleted' => TRUE]);
        }

        return response()->success();
    }
}
