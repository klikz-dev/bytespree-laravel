<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SftpSite;
use App\Models\SavedData;
use App\Models\Explorer\MicrosoftSqlServer;
use App\Models\Explorer\Project;
use App\Models\Explorer\PublishingDestination;
use App\Models\Explorer\ProjectPublishingSchedule;
use App\Models\Explorer\ProjectSnapshot;
use App\Classes\IntegrationJenkins;
use App\Classes\Mssql;
use App\Classes\Database\Table;
use App\Classes\Publishers\Sftp;
use App\Attributes\Can;
use Exception;

class PublisherController extends Controller
{
    public function list(Request $request, Project $project)
    {
        $publishers = ProjectPublishingSchedule::whereHas('destination', function ($q) {
            $q->where('name', '!=', 'View');
        })
            ->where('project_id', $project->id)
            ->get();

        return response()->success($publishers);
    }

    public function logs(Request $request, Project $project, ProjectPublishingSchedule $publisher)
    {
        return response()->success(["logs" => $publisher->logs, "name" => $publisher->destination->name]);
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project, ProjectPublishingSchedule $publisher)
    {
        app(IntegrationJenkins::class)->deletePublisherJob(
            $publisher,
            $project,
            $publisher->schema_name,
            $publisher->table_name
        );
        $publisher->delete();

        return response()->empty();
    }

    public function destinations(Request $request, Project $project)
    {
        return response()->success(PublishingDestination::get());
    }

    public function check(Request $request, Project $project, string $schema, string $table)
    {
        $schedule = ProjectPublishingSchedule::where('project_id', $project->id)
            ->where('schema_name', $schema)
            ->where('table_name', $table)
            ->first();

        if (! empty($schedule)) {
            return response()->success(TRUE);
        }
        
        return response()->success(FALSE);
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function csv(Request $request, Project $project, string $schema, string $table)
    {
        $request->validateWithErrors([
            'id'               => 'required',
            'publish_schedule' => 'required',
            'publish_type'     => 'required',
            'query'            => 'required',
            'append_timestamp' => 'required',
            'users'            => 'required'
        ]);
        
        $options = (object) $request->only(['id', 'message', 'append_timestamp', 'limit', 'users', 'query']);

        if ($request->publish_type == 'one_time') {
            $result = $this->publishOnce($project, $schema, $table, (object) $options, 'Csv');
        } else {
            $result = $this->createSchedule($project, $schema, $table, (object) $request->publish_schedule, (object) $options, 'Csv');
        }

        if (! $result) {
            return response()->error("Failed to launch publisher.");
        }

        return response()->success();
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function mssql(Request $request, Project $project, string $schema, string $table)
    {
        $request->validateWithErrors([
            'id'                  => 'required',
            'publish_schedule'    => 'required',
            'publish_type'        => 'required',
            'query'               => 'required',
            'server_id'           => 'required',
            'using_new_database'  => 'required',
            'using_new_table'     => 'required',
            'append_timestamp'    => 'required',
            'truncate_on_publish' => 'required',
        ]);

        try {
            $server = MicrosoftSqlServer::find($request->server_id);
            $server->data = (object) $server->data;

            $config = [
                'host'     => $server->data->hostname,
                'port'     => $server->data->port,
                'username' => $server->data->username,
                'password' => $server->data->password
            ];

            $mssql = new Mssql($config);

            // Are we using a new database, or an existing one?
            if (! $request->using_new_database) {
                $mssql->setDatabase($request->target_database);

                if (! $request->using_new_table) {
                    // TODO: This is too redundant - we need a better way
                    $parts = explode('.', $request->target_table);
                    if (count($parts) > 1) {
                        $target_schema = $parts[0];
                        $target_table = $parts[1];
                    } else {
                        $target_schema = 'dbo';
                        $target_table = $parts[0];
                    }  
                    if (! $mssql->tableExists($target_schema, $target_table, $request->target_database)) {
                        return response()->error('Table could not be accessed.');
                    }
                }
            }

            // Will the database name exceed the maximum 124 characters?
            if ($request->using_new_database && mb_strlen($request->target_create_database) > 124) {
                return response()->error('Table name cannot be longer than 124 characters.');
            }

            // Will the table name exceed the maximum 128 characters?
            if ($request->using_new_table) {
                if (mb_strlen($request->target_create_table) > 128) {
                    return response()->error('Table name cannot be longer than 128 characters.');
                }
            }
        } catch (Exception $e) {
            return response()->error($e->getMessage());
        }

        $options = (object) $request->only([
            'id',
            'publish_type',
            'publish_schedule',
            'server_id',
            'using_new_database',
            'target_database',
            'target_create_database',
            'using_new_table',
            'target_table',
            'target_create_table',
            'append_timestamp',
            'truncate_on_publish',
            'query',
            'column_mappings'
        ]);

        $options->target_database = empty($options->target_database) ? '' : $options->target_database;
        $options->target_table = empty($options->target_table) ? '' : $options->target_table;

        // If we're not creating a new table, let's redirect the user to map the columns they'd like to utilize.
        if (! $options->using_new_table) {
            $destination = PublishingDestination::className('Mssql');

            $data = [
                'id'                  => $request->id,
                'destination'         => $destination,
                'destination_options' => $options,
                'project_id'          => $project->id,
                'schema_name'         => $schema,
                'table_name'          => $table,
                'username'            => auth()->user()->user_handle
            ];
    
            $saved_data = SavedData::create([
                'data'       => $data,
                'controller' => self::class,
            ]);

            return response()->success(['redirect' => TRUE, 'location' => "/studio/projects/$project->id/tables/$schema/$table/mssql/map/$saved_data->guid"]);
        }

        if ($request->publish_type == 'one_time') {
            $result = $this->publishOnce($project, $schema, $table, $options, 'Mssql');
        } else {
            $result = $this->createSchedule($project, $schema, $table, (object) $request->publish_schedule, $options, 'Mssql');
        }

        if (! $result) {
            return response()->error("Failed to launch publisher.");
        }

        return response()->success();
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function mssqlDetails(Request $request, Project $project, string $schema, string $table, string $guid)
    {
        $saved_data = SavedData::where('guid', $guid)->first();

        $options = (object) $saved_data->data['destination_options'];
        
        try {
            $server = MicrosoftSqlServer::find($options->server_id);
            $server->data = (object) $server->data;

            $config = [
                'host'     => $server->data->hostname,
                'port'     => $server->data->port,
                'username' => $server->data->username,
                'password' => $server->data->password
            ];

            $mssql = new Mssql($config);

            $mssql->setDatabase($options->target_database);

            // TODO: This is too redundant - we need a better way
            $parts = explode('.', $options->target_table);
            if (count($parts) > 1) {
                $target_schema = $parts[0];
                $target_table = $parts[1];
            } else {
                $target_schema = 'dbo';
                $target_table = $parts[0];
            }  

            $destination_columns = $mssql->getColumns($target_schema, $target_table)->toArray();
        } catch (Exception $e) {
            return response()->error($e->getMessage());
        }

        $filtered = array_filter($options->query['columns'], function ($column) {
            return $column['checked'] ? TRUE : FALSE;
        });

        $source_columns = array_map(function ($column) use ($mssql) {
            $column = (object) $column;

            if ($column->added && $column->prefix == 'custom') {
                $column->data_type = 'custom';
            }

            return [
                'name'               => $column->alias != '' ? $column->alias : $column->target_column_name,
                'matching_type'      => $mssql->getConvertedType($column->data_type),
                'destination_column' => NULL
            ];
        }, $filtered);

        if (! empty($options->column_mappings)) {
            $column_mappings = array_column((array) $options->column_mappings, "destination_column", "name");
            $source_columns = array_map(function ($source_column) use ($column_mappings) {
                if (isset($column_mappings[$source_column["name"]])) {
                    $source_column["destination_column"] = $column_mappings[$source_column["name"]];
                }

                return $source_column;
            }, $source_columns);
        }

        $source_columns = array_values($source_columns);

        $destination_columns = array_map(function ($column) {
            return [
                'name'      => $column->COLUMN_NAME,
                'data_type' => $column->DATA_TYPE,
                'precision' => $column->NUMERIC_PRECISION,
                'nullable'  => $column->IS_NULLABLE == 'YES' ? TRUE : FALSE
            ];
        }, $destination_columns);

        return response()->success(compact('source_columns', 'destination_columns'));
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function mssqlMap(Request $request, Project $project, string $schema, string $table, string $guid)
    {
        $request->validateWithErrors([
            'columns' => 'required',
        ]);

        $saved_data = SavedData::where('guid', $guid)->first();

        // Recursivly make all arrays objects
        $data = json_decode(json_encode($saved_data->data, JSON_FORCE_OBJECT));

        $data->destination_options->column_mappings = array_filter($request->columns, function ($column) {
            return ! empty($column['destination_column']);
        });

        $saved_data->update(['data' => $data]);

        if ($data->destination_options->publish_type == 'one_time') {
            $word = 'queued';
            $result = app('jenkins')->launchFunction(
                'publishTable',
                [
                    'TEAM'    => app('environment')->getTeam(),
                    'DATA_ID' => $guid
                ]
            );
        } else {
            $word = $data->id == -1 ? "created" : "updated";
            $result = $this->createSchedule($project, $schema, $table, $data->destination_options->publish_schedule, $data->destination_options, 'Mssql');
        }

        if (! $result) {
            return response()->error("Failed to launch publisher.");
        }

        $url_append = $data->id == -1 ? "" : "&publisher_id=" . $data->id;

        return response()->success([
            'redirect' => "/studio/projects/$project->id/tables/{$schema}/{$table}?message=" . urlencode("Your publisher has been $word successfully!") . "&message_type=success" . $url_append
        ]);
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function sftp(Request $request, Project $project, string $schema, string $table)
    {
        $request->validateWithErrors([
            'id'                  => 'required',
            'publish_schedule'    => 'required',
            'publish_type'        => 'required',
            'destination_options' => 'required'
        ]);

        $sftp = new Sftp();

        try {
            $sftp->destination_options = (object) $request->destination_options;
            $sftp->sftp_site = SftpSite::find($sftp->destination_options->site_id);
            $sftp->sftp_path = $sftp->buildFilePath();
    
            if ($sftp->destination_options->append_timestamp == TRUE) {
                $sftp_table_name = $table . "_" . date('Ymdhis') . ".csv";
            } else {
                $sftp_table_name = $table . ".csv";
            }
    
            $sftp->sftp_file_name = $sftp->sftp_path . "/" . $sftp_table_name;
    
            $sftp->verifySftpConnection();
        } catch (Exception $e) {
            return response()->error($e->getMessage());
        }
        
        $options = (object) $request->destination_options;
        $options->id = $request->id;

        if ($request->publish_type == 'one_time') {
            $result = $this->publishOnce($project, $schema, $table, (object) $options, 'Sftp');
        } else {
            $result = $this->createSchedule($project, $schema, $table, (object) $request->publish_schedule, (object) $options, 'Sftp');
        }

        if (! $result) {
            return response()->error("Failed to launch publisher.");
        }

        return response()->success(message: 'Publishing complete');
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function snapshot(Request $request, Project $project, string $schema, string $table)
    {
        $request->validateWithErrors([
            'id'               => 'required',
            'publish_schedule' => 'required',
            'publish_type'     => 'required',
            'query'            => 'required',
            'name'             => 'required',
            'append_timestamp' => 'required'
        ]);

        if (! $request->append_timestamp) {
            $has_conflicts = ProjectPublishingSchedule::checkSnapshotDuplicate($project, $request->id, $request->name);

            if ($has_conflicts) {
                return response()->error('A snapshot is already scheduled with the same name.');
            }

            if ($request->id != -1) {
                $current_publisher = ProjectPublishingSchedule::find($request->id);
            }

            if (empty($current_publisher) || $current_publisher->destination_options['name'] != $request->name) {
                $snapshot = ProjectSnapshot::where('project_id', $project->id)
                    ->where('name', $request->name)
                    ->first();

                if (! empty($snapshot)) {
                    return response()->error("Another snapshot using the same table name already exists.");
                } else if (Table::exists($project->primary_database, $project->name, $request->name)) {
                    return response()->error("Another asset already uses this name. Try using a different name.");
                }
            }
        }
        
        $options = $request->only(['id', 'name', 'description', 'append_timestamp', 'query']);

        if ($request->publish_type == 'one_time') {
            $result = $this->publishOnce($project, $schema, $table, (object) $options, 'Snapshot');
        } else {
            $result = $this->createSchedule($project, $schema, $table, (object) $request->publish_schedule, (object) $options, 'Snapshot');
        }

        if (! $result) {
            return response()->error("Failed to launch publisher.");
        }

        return response()->success();
    }

    public function publishOnce(Project $project, string $schema, string $table, object $options, string $class_name)
    {
        $destination = PublishingDestination::className($class_name);

        $data = [
            'id'                  => $options->id,
            'destination'         => $destination,
            'destination_options' => $options,
            'project_id'          => $project->id,
            'schema_name'         => $schema,
            'table_name'          => $table,
            'username'            => auth()->user()->user_handle
        ];

        $saved_data = SavedData::create([
            'data'       => $data,
            'controller' => self::class,
        ]);

        $result = app('jenkins')->launchFunction(
            'publishTable',
            [
                'TEAM'    => app('environment')->getTeam(),
                'DATA_ID' => $saved_data->guid
            ]
        );

        return $result;
    }

    public function createSchedule(Project $project, string $schema, string $table, object $schedule, object $options, string $class_name, int|NULL $publisher_id = NULL)
    {
        $destination = PublishingDestination::className($class_name);
        $updating = $options->id > 0 ? TRUE : FALSE;

        // Looks like we're scheduling it. Do we need to create a new schedule or update the existing?
        if ($updating) {
            $publishing_schedule = ProjectPublishingSchedule::find($options->id);
        } else {
            $publishing_schedule = ProjectPublishingSchedule::create([
                'project_id'          => $project->id,
                'destination_id'      => $destination->id,
                'destination_options' => $options,
                'username'            => auth()->user()->user_handle,
                'schema_name'         => $schema,
                'table_name'          => $table,
                'schedule'            => $schedule,
                'publisher_id'        => $publisher_id
            ]);
        }

        try {
            // Create it in Jenkins...
            $result = app(IntegrationJenkins::class)->createOrUpdatePublishJob(
                $project,
                $destination->name,
                $schema,
                $table,
                $publishing_schedule->id,
                $schedule
            );
        } catch (Exception $e) {
            if (! $updating) {
                $publishing_schedule->delete();
            }

            logger()->error("Unable to schedule publishing at this time", [
                'publisher_id'   => $options->id ?? 0,
                'project_id'     => $project->id,
                'destination_id' => $destination->id,
                'options'        => $options,
                'user'           => auth()->user()->user_handle,
                'table'          => $table,
                'schema'         => $schema,
                'schedule'       => $schedule,
            ]);

            return FALSE;
        }
        
        if ($updating) {
            $publishing_schedule->update([
                'destination_options' => $options,
                'schedule'            => $schedule,
                'publisher_id'        => $publisher_id
            ]);
        }

        return TRUE;
    }
}
