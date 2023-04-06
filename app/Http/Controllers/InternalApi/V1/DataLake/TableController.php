<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\SavedData;
use App\Models\Manager\ImportedTable;
use App\Models\Manager\ImportLog;
use App\Classes\IntegrationJenkins;
use App\Classes\Csv;
use App\Classes\Database\Table;
use App\Classes\Database\View;
use App\Classes\Database\ForeignDatabase;
use App\Attributes\Can;
use App\Models\Explorer\ProjectPublishingSchedule;
use DB;

class TableController extends Controller
{
    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function list(PartnerIntegration $database)
    {
        return response()->success(Table::list($database, []));
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function details(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $columns = Table::columns($database, $table_schema, $table_name);
        $indexes = Table::indexes($database, $table_schema, $table_name);
        // We should disucss removing this when we're finished with everything else
        $relationships = Table::relationships($database, $table_schema, $table_name);
        $views = Table::views($database, $table_schema, $table_name);

        $index_columns = [];
        foreach ($indexes as $index) {
            $index_columns[] = $index->column_name;
        }

        return response()->success([
            "columns"       => $columns,
            "indexes"       => $indexes,
            "index_columns" => $index_columns,
            "relationships" => $relationships,
            "views"         => $views
        ]);
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function dependencies(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        $views = Table::dependencies($database, $table_schema, $table_name);

        if (! empty($views)) {
            $views = array_column($views, 'view');
        }

        return response()->success($views);
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function checkDependencies(PartnerIntegration $database, string $table_schema, string $table_name)
    {
        if (! Table::exists($database, $table_schema, $table_name)) {
            return response()->success();
        }

        $views = Table::dependencies($database, $table_schema, $table_name);
        
        $schedules = ProjectPublishingSchedule::database($database)
            ->where('schema_name', $table_schema)
            ->where('table_name', $table_name)
            ->get();

        if (! empty($views)) {
            $view_string = collect($views)->map(function ($view) {
                return $view->view_schema . '.' . $view->view_name;
            })->implode(', ');

            return response()->error("The $table_name table has the following dependent view(s): $view_string. Please delete the view(s) first.");
        } else if ($schedules->count() > 0 && ! $ignore_warning) {
            return response()->success("warning", "$table_name is being published on a recurring basis. Deleting this table will also delete the publishing job. Do you want to continue?");
        }

        return response()->success();
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function logs(PartnerIntegration $database, int $table_id)
    {
        $logs = ImportLog::select("dw_import_logs.*", DB::raw("to_char(dw_import_logs.created_at, 'YYYY-MM-DD HH12:MI:SS AM') as \"formatted_date\""), "u_users.user_handle as author")
            ->where("dw_import_logs.table_id", $table_id)
            ->leftJoin("u_users", "u_users.id", "=", "dw_import_logs.user_id")
            ->get();

        return response()->success($logs);
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function logDetails(PartnerIntegration $database, int $log_id)
    {
        return response()->success(ImportLog::find($log_id));
    }

    public function compareColumns(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'table_name'  => 'required',
            'temp_file'   => 'required',
            'delimiter'   => 'required',
            'has_columns' => 'required'
        ]);

        $table_columns = array_column(Table::columns($database, 'public', $request->table_name), 'column_name');

        if (empty($table_columns)) {
            return response()->error("There was a problem retrieving the destination table's columns.");
        }

        $uploaded_file_path = config("app.upload_directory") . '/tmp/' . $request->temp_file;

        $file_columns = Csv::columns($uploaded_file_path, TRUE, $request->delimiter);

        $bytespree_columns = array_filter($file_columns, function ($column) {
            if (substr($column, 0, strlen(Csv::BYTESPREE_PREFIX)) == Csv::BYTESPREE_PREFIX) {
                return TRUE;
            }

            return FALSE;
        });

        if (count($bytespree_columns) > 0) {
            foreach ($bytespree_columns as $key => $column) {
                unset($file_columns[$key]);
            }
            $file_columns = array_values($file_columns);
        }

        // Do the counts of the columns match?
        if ($request->is_replacing && count($file_columns) < count($table_columns)) {
            return response()->error("The columns in the table do not match the uploaded file's.", ['column_mismatch' => TRUE, 'counts_mismatch' => TRUE]);
        } else if ($request->is_replacing && count($file_columns) > count($table_columns)) {
            return response()->error("The columns in the table do not match the uploaded file's.", ['column_mismatch' => TRUE, 'counts_mismatch' => FALSE]);
        } else if (count($file_columns) != count($table_columns)) {
            return response()->error("The columns in the table do not match the uploaded file's.", ['column_mismatch' => TRUE, 'counts_mismatch' => TRUE]);
        }

        // Are the arrays the same?
        if ($request->has_columns) {
            if (array_diff($file_columns, $table_columns) != [] || array_diff($table_columns, $file_columns) != []) {
                return response()->error("The columns in the table do not match the uploaded file's.", ['column_mismatch' => TRUE]);
            }
        }

        return response()->success();
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function build(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'table_name'    => 'required',
            'table_id'      => 'required',
            'file_name'     => 'required',
            'columns_temp'  => 'required',
            'encoding'      => 'required',
            'delimiter'     => 'required',
            'ignore_errors' => 'required',
            'ignore_empty'  => 'required',
            'has_columns'   => 'required',
            'is_replacing'  => 'required',
            'is_appending'  => 'required'
        ]);

        extract($request->all());
        
        // Fully qualified file path of the uploaded CSV file
        $file_path = config("app.upload_directory") . '/tmp/' . $file_name;

        // Does the table have any unallowed characters in its name?
        $match = preg_match('/^[0-9a-z_]+$/', $table_name);
        if ($match === 0 || $match === FALSE) {
            return response()->error("Invalid character(s) in table name. Name must contain only letters, numbers, and underscores.");
        }

        // Is the first character of the table name a number?
        if (is_numeric(substr($table_name, 0, 1))) {
            return response()->error("First character of table name cannot be a number.");
        }

        $type = "";
        $indexes = [];
        $indexes_to_create = [];
        $is_adding = FALSE;
        if ($is_replacing) {
            $type = "replace";
            $indexes_to_create = array_column(Table::indexes($database, 'public', $table_name), 'column_name');
            $definitions = View::dropForTable($database, 'public', $table_name);
            Table::drop($database, 'public', $table_name);
        } else {
            $type = $is_appending ? 'append' : 'add';
            $is_adding = $is_appending ? FALSE : TRUE;
        }

        if ($is_adding) {
            $mappings = NULL;

            if (Table::exists($database, 'public', $table_name)) {
                return response()->error("Table already exists. Please choose another name.");
            }

            $table = ImportedTable::create([
                'control_id' => $database->id,
                'table_name' => $table_name,
                'user_id'    => auth()->user()->user_handle
            ]);

            if (! empty($table)) {
                $table_id = $table->id;
            } else {
                return response()->error("Table has failed to import.");
            }

            foreach ($columns_temp as $column) {
                $column = (object) $column;
                // Does this column have any unallowed characters in its name?
                $match = preg_match('/^[0-9a-z_]+$/', $column->column);
                if ($match === 0 || $match === FALSE) {
                    return response()->error("Invalid character(s) in column. Name must contain only letters, numbers, and underscores.");
                }

                // Is the first character of the column name a number?
                if (is_numeric(substr($column->column, 0, 1))) {
                    return response()->error("First character of column name cannot be a number.");
                }

                if (! is_numeric($column->precision)) {
                    return response()->error("Precision must be a number.");
                }

                $columns[] = [
                    "column_name"              => strtolower($column->column),
                    "character_maximum_length" => $column->value,
                    "type"                     => $column->type,
                    "precision"                => $column->precision
                ];
            }
        }
        
        if (! $is_adding) {
            $columns = array_map(function ($column) {
                $column = (object) $column;
    
                $column->column_name = $column->map_to ?? $column->column;
    
                return (array) $column;
            }, $columns_temp);
    
            $mappings = array_map(function ($column) {
                $column = (object) $column;
    
                return [
                    "column" => $column->column,
                    "map"    => $column->map_to
                ];
            }, $columns_temp);
        }
       
        if (! $is_appending) {
            Table::create($database, 'public', $table_name, $columns);

            $indexes = array_map(function ($index) use ($database, $table_name) {
                return [
                    'column'  => $index,
                    'created' => Table::createIndex($database, $table_name, $index)
                ];
            }, $indexes_to_create);

            if ($is_replacing) {
                View::createFromDefinitions($database, $definitions);
            }
        }

        ForeignDatabase::addTable($database, $table_name);

        $data = [
            "database_id" => $database->id,
            "table_id"    => $table_id,
            "team"        => app('environment')->getTeam(),
            "user_handle" => auth()->user()->user_handle,
            "table_name"  => $table_name,
            "file_name"   => $file_name,
            "file_size"   => filesize($file_path),
            "file_path"   => $file_path,
            "type"        => $type,
            "columns"     => $columns,
            "mappings"    => $mappings,
            "settings"    => [
                "encoding"      => $encoding,
                "enclosed"      => $enclosed,
                "escape"        => $escape,
                "delimiter"     => str_replace('\\', '\\\\', $delimiter),
                "ignore_errors" => $ignore_errors,
                "ignore_empty"  => $ignore_empty,
                "has_columns"   => $has_columns
            ],
            "ip_address" => $request->ip(),
            "indexes"    => $indexes
        ];

        $saved_data = SavedData::create([
            'data' => $data
        ]);

        $params = [
            "TEAM"    => $data['team'],
            "DATA_ID" => $saved_data->guid
        ];

        $result = app('jenkins')->launchFunction('importTable', $params);

        if (! $result) {
            return response()->error("Table has failed to import.");
        }

        return response()->success();
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function createIndex(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'table'  => 'required',
            'column' => 'required'
        ]);

        $params = [
            'DATABASE_ID' => $database->id,
            'TABLE'       => $request->table,
            'COLUMN'      => $request->column,
            'TEAM'        => app('environment')->getTeam()
        ];

        if (! app('jenkins')->launchFunction("addIndex", $params)) {
            return response()->error("Failed to queue up adding the index.");
        }

        return response()->success();
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function import(Request $request, PartnerIntegration $database)
    {
        $request->validateWithErrors([
            'table_name'    => 'required',
            'table_id'      => 'required',
            'file_name'     => 'required',
            'encoding'      => 'required',
            'delimiter'     => 'required',
            'ignore_errors' => 'required',
            'ignore_empty'  => 'required',
            'has_columns'   => 'required',
            'is_replacing'  => 'required',
            'is_appending'  => 'required'
        ]);

        $file_path = config("app.upload_directory") . '/tmp/' . $request->file_name;
        $csv_columns = Csv::columns($file_path, $request->has_columns, $request->delimiter);
        $database_columns = Table::columns($database, 'public', $request->table_name);

        $columns = Csv::removeBytespreeColumns($csv_columns, $database_columns);

        if ($request->is_replacing) {
            $type = 'replace';
            Table::truncate($database, 'public', $request->table_name);
        } else {
            $type = 'append';
        }

        $data = [
            "database_id" => $database->id,
            "table_id"    => $request->table_id,
            "team"        => app('environment')->getTeam(),
            "user_handle" => auth()->user()->user_handle,
            "table_name"  => $request->table_name,
            "file_name"   => $request->file_name,
            "file_size"   => filesize($file_path),
            "file_path"   => $file_path,
            "type"        => $type,
            "columns"     => $columns,
            "mappings"    => [],
            "settings"    => [
                "encoding"      => $request->encoding,
                "enclosed"      => $request->enclosed,
                "escape"        => $request->escape,
                "delimiter"     => str_replace('\\', '\\\\', $request->delimiter),
                "ignore_errors" => $request->ignore_errors,
                "ignore_empty"  => $request->ignore_empty,
                "has_columns"   => $request->has_columns
            ],
            "ip_address" => $request->ip(),
            "indexes"    => []
        ];

        $saved_data = SavedData::create([
            'data' => $data
        ]);

        $params = [
            "TEAM"    => $data['team'],
            "DATA_ID" => $saved_data->guid
        ];

        $result = app('jenkins')->launchFunction('importTable', $params);

        if (! $result) {
            return response()->error("Table has failed to import.");
        }

        return response()->success();
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function drop(Request $request, PartnerIntegration $database, string $table_schema, string $table_name, bool $ignore_errors)
    {
        $ignore_warning = filter_var($request->ignore_warning, FILTER_VALIDATE_BOOLEAN);

        $views = Table::dependencies($database, $table_schema, $table_name);
        
        $schedules = ProjectPublishingSchedule::database($database)
            ->where('schema_name', $table_schema)
            ->where('table_name', $table_name)
            ->get();

        if (! empty($views)) {
            $view_string = collect($views)->map(function ($view) {
                return $view->view_schema . '.' . $view->view_name;
            })->implode(', ');

            return response()->error("The $table_name table has the following dependent view(s): $view_string. Please delete the view(s) first.");
        } else if ($schedules->count() > 0 && ! $ignore_warning) {
            return response()->success("warning", "$table_name is being published on a recurring basis. Deleting this table will also delete the publishing job. Do you want to continue?");
        }

        Table::drop($database, $table_schema, $table_name);
        ForeignDatabase::removeTable($database, $table_name);

        if ($schedules->count() > 0) {
            app(IntegrationJenkins::class)->removePublishJobForDatabase($database, $table_schema, $table_name);

            foreach ($schedules as $schedule) {
                $schedule->delete();
            }
        }

        if (! Table::exists($database, $table_schema, $table_name)) {
            ImportedTable::where('control_id', $database->id)
                ->where('table_name', $table_name)
                ->first()
                ->delete();

            return response()->success();
        }

        return response()->error("Failed to drop table $table_name");
    }

    #[Can(permission: 'manage_schema', product: 'datalake', id: 'database.id')]
    public function dropIndex(PartnerIntegration $database, string $index_name)
    {
        if (! Table::dropIndex($database, $index_name)) {
            return response()->error("Failed to delete index.");
        } 

        return response()->success();
    }
}
