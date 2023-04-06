<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\Role;
use App\Models\Manager\ViewDefinition;
use App\Classes\Database\Connection as DatabaseConnection;
use App\Classes\Database\View;
use App\Classes\Database\Table;
use App\Classes\Database\ForeignDatabase;
use App\Classes\Connector;
use App\Models\Integration;
use Auth;
use App\Classes\IntegrationJenkins;
use App\Models\PartnerIntegrationSchedule;
use App\Models\PartnerIntegrationSchedulePropertyValue;
use App\Models\PartnerIntegrationTable;
use App\Models\PartnerIntegrationTableSchedule;
use App\Models\PartnerIntegrationTableSchedulePropertyValue;
use App\Attributes\Can;
use App\Classes\Postmark;
use App\Models\IntegrationScheduleType;
use App\Models\Manager\DatabaseHookKey;
use App\Models\Manager\Tag;
use App\Models\Manager\JenkinsBuild;
use App\Models\PartnerIntegrationSetting;
use App\Models\User;
use DB;

class DataLakeController extends Controller
{
    public function list(Request $request)
    {
        if ($request->filled('source')) {
            return match ($request->source) {
                'admin' => $this->adminList()
            };
        }
        // check perms

        $databases = PartnerIntegration::getAllWithBuildStatus();

        $user_databases = [];
        $other_databases = [];

        if (Auth::user()->is_admin) {
            $user_databases = $databases;
        } else {
            $assigned_databases = Auth::user()->databases();
            if ($assigned_databases->count() > 0) {
                // User must be assigned at least one database to start seeing anything
                $user_databases = $databases->filter(function ($database) use ($assigned_databases) {
                    return $assigned_databases->contains('product_child_id', $database->id);
                });
                $other_databases = $databases->filter(function ($database) use ($assigned_databases) {
                    return ! $assigned_databases->contains('product_child_id', $database->id);
                });
            }
        }

        $user_databases = collect($user_databases);
        $other_databases = collect($other_databases);

        $tags = Tag::getAllDatabaseTags();
        $hook_keys = DatabaseHookKey::all();

        $user_databases_filled = $user_databases->map(function ($database) use ($tags, $hook_keys) {
            if (! is_null($database->failed_jobs)) {
                $database->failed_jobs = json_decode($database->failed_jobs);
            }

            $database->is_running = filter_var($database->is_running, FILTER_VALIDATE_BOOLEAN) === TRUE;
            
            $database->tags = $tags->where('control_id', $database->id)->values();

            $database->hook_key = $hook_keys->where('partner_integration_id', $database->id)->first();
            
            $database->jobs = [];
            $database->hidden = FALSE;

            return $database;
        });

        $other_databases_filled = $other_databases->map(function ($database) use ($tags) {
            $database->result_code = 10;
            $database->status_color = 'blue';
            $database->failed_jobs = [];
            $database->is_running = FALSE;
            $database->tags = $tags->where('control_id', $database->id)->values();
            $database->hidden = FALSE;

            return $database;
        });

        return response()->success([
            'user_databases'  => array_values($user_databases_filled->toArray()),
            'other_databases' => array_values($other_databases_filled->toArray()),
        ]);
    }

    public function adminList()
    {
        return response()->success(PartnerIntegration::all());
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function show(Request $request, PartnerIntegration $database)
    {
        // todo
        // if ($this->user['is_admin'] === FALSE && $this->PartnerIntegrationsModel->hasAccessToDatabase($control_id, $this->session->userdata("username")) == FALSE) {
        //     $this->_sendAjax('error', 'Access denied', [], 403);
 
        //     return;
        // }

        $database->load('hook_key', 'integration', 'settings', 'settings.setting', 'tables', 'schedule', 'tables.schedule', 'tables.schedule.properties');

        $settings = [];
        $tables = [];
        $table_settings = [];

        foreach ($database->settings as $setting) {
            if ($setting->setting->setting_type == 'table') {
                $table_settings[] = array_merge(
                    $setting->setting->toArray(),
                    $setting->toArray(),
                );
            } else {
                $settings[] = array_merge(
                    $setting->setting->toArray(),
                    $setting->toArray(),
                );
            }
        }

        if (count($settings) > 0) {
            $settings = collect($settings)->sortBy('ordinal_position')->values()->all();
        }

        if (count($table_settings) > 0) {
            $table_settings = collect($table_settings)->sortBy('ordinal_position')->values()->all();
        }

        if (isset($database->integration) && isset($database->integration->settings)) {
            foreach ($database->integration->settings as $setting) {
                $setting->properties = json_decode($setting->properties);
                $setting->is_private = filter_var($setting->is_private, FILTER_VALIDATE_BOOLEAN) === TRUE;
                $setting->is_secure = filter_var($setting->is_secure, FILTER_VALIDATE_BOOLEAN) === TRUE;
                $setting->is_required = filter_var($setting->is_required, FILTER_VALIDATE_BOOLEAN) === TRUE;
                $setting->added = FALSE;
                $setting->changed = FALSE;
                $setting->deleted = FALSE;

                $setting = $setting->toArray();

                $setting['id'] = 0;

                if ($setting['setting_type'] == 'table') {
                    $empty_table_settings[] = $setting;
                }
            }
        }

        $table_settings = array_map(function ($table_setting) {
            // Force boolean values to true booleans
            if ($table_setting['setting']['data_type'] === 'boolean') {
                $table_setting['value'] = filter_var($table_setting['value'], FILTER_VALIDATE_BOOLEAN) === TRUE;
                $table_setting['setting']['default_value'] = filter_var($table_setting['setting']['default_value'], FILTER_VALIDATE_BOOLEAN) === TRUE;
                $table_setting['default_value'] = filter_var($table_setting['default_value'], FILTER_VALIDATE_BOOLEAN) === TRUE;
            }

            return $table_setting;
        }, $table_settings);
        
        if (isset($database->tables)) {
            $tables = $database->tables->map(function ($table) use ($table_settings) {
                $table->orig_name = $table->name;
                $table->changed = FALSE;
                $table->deleted = FALSE;

                $last_started = strtotime($table->last_started);
                $table->date_last_started = date('Y-m-d', $last_started);
                $table->time_last_started = date('H:i', $last_started);

                $last_finished = strtotime($table->last_finished);
                $table->date_last_finished = date('Y-m-d', $last_finished);
                $table->time_last_finished = date('H:i', $last_finished);

                $table->settings = collect($table_settings)
                    ->filter(fn($setting) => $setting['table_name'] == $table->name)
                    ->mapWithKeys(fn($setting) => [$setting['setting']['name'] => $setting])
                    ->toArray();

                if (! empty($table->schedule)) {
                    $table->schedule->previous_schedule_type_id = $table->schedule->schedule_type_id;
                    $table->schedule->previous_properties = $table->schedule->properties;
                    $table->schedule->added = FALSE;
                    $table->schedule->changed = FALSE;

                    if (! empty($table->schedule->properties)) {
                        foreach ($table->schedule->properties as $key => $value) {
                            $table->schedule->properties[$key]['options'] = json_decode($value['options']);
                        }
                    }
                }

                return $table;
            });
        }

        $database = $database->toArray();

        $database['settings'] = $settings;
        $database['table_settings'] = $table_settings;
        $database['tables'] = $tables;
        if (! empty($database['integration'])) {
            $orc_connector = app('orchestration')->getConnector($database['integration']['name']);
            $database['release_notes'] = $orc_connector['release_notes'];

            $database = array_merge(
                $database['integration'],
                $database
            );

            unset($database['integration']);
        }

        return response()->success($database);
    }

    #[Can(permission: 'datalake_create', product: 'datalake')]
    public function create(Request $request)
    {
        if ($request->has('id')) {
            return $this->createIntegrationDatabase($request);
        }

        $name = strtolower(str_replace(' ', '_', $request->name));

        if (! preg_match('/^[0-9a-z_]+$/', $name)) {
            return response()->error('Invalid character(s) in database name. Name must contain only letters, numbers, and underscores.', [], 400);
        }

        if (PartnerIntegration::where('database', $name)->exists()) {
            return response()->error("Database $name already exists. Please specify another name.", [], 400);
        }

        $database = PartnerIntegration::create([
            'database'       => $name,
            'schema'         => 'public',
            'server_id'      => $request->server_id,
            'integration_id' => 0,
        ]);

        if (DatabaseConnection::databaseExists($database, $database->database)) {
            $database->delete();

            return response()->error("Database {$name} already exists. Please specify another name.", [], 400);
        }

        if (! DatabaseConnection::createDatabase($database, $database->database)) {
            logger()->error(
                "Failed to create database for unknown reason.",
                [
                    'database'      => $database->id,
                    'server_id'     => $database->server->id,
                    'database_name' => $database->database,
                ]
            );

            $database->delete();

            return response()->error('Failed to create database.', [], 500);
        }

        if (! Auth::user()->is_admin) {
            $role = Role::where('role_name', 'Database Admin')->first();

            Auth::user()->assignRole($role, $database->id);
        }

        return response()->success($database, 'Database created');
    }

    public function dependencies(Request $request, PartnerIntegration $database)
    {
        return response()->success([
            "projects"                    => $database->projects,
            "foreign_projects"            => $database->foreign_projects(),
            "warehouse_foreign_databases" => $database->warehouse_foreign_databases(),
        ]);
    }

    public function callbacks(Request $request, PartnerIntegration $database)
    {
        return response()->success(
            $database->callbacks
        );
    }

    public function schedule(PartnerIntegration $database)
    {
        return response()->success(
            $database->schedule
        );
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function saveTables(Request $request, PartnerIntegration $database)
    {
        $new_tables = [];
        $tables = $request->all();

        app(IntegrationJenkins::class)->updateTables($database, $tables);

        foreach ($tables as $table) {
            $new_table = NULL;
            $id = $table['id'] ?? NULL;
            $table_name = $table['name'];
            $orig_name = $table['orig_name'] ?? $table['name'];
            $date_last_started = $table['date_last_started'] ?? NULL;
            $time_last_started = $table['time_last_started'] ?? NULL;
            $date_last_finished = $table['date_last_finished'] ?? NULL;
            $time_last_finished = $table['time_last_finished'] ?? NULL;
            $table_last_started = $date_last_started . ' ' . $time_last_started;
            $table_last_finished = $date_last_finished . ' ' . $time_last_finished;

            // Did we add, update, or delete the table?
            if ($table['added'] ?? FALSE === TRUE) {
                $new_table = PartnerIntegrationTable::create([
                    'is_active'              => TRUE,
                    'partner_integration_id' => $database->id,
                    'name'                   => $table_name
                ]);

                $new_tables[] = $new_table->name;
            } elseif ($table['changed'] ?? FALSE === TRUE) {
                PartnerIntegrationTable::find($id)
                    ->update([
                        'name'          => $table['name'],
                        'last_started'  => $table_last_started,
                        'last_finished' => $table_last_finished,
                        'is_active'     => $table['is_active']
                    ]);
            } elseif ($table['deleted'] ?? FALSE === TRUE) {
                if (Table::exists($database, 'public', $orig_name)) {
                    Table::drop($database, 'public', $orig_name);
                }

                PartnerIntegrationTable::find($id)->delete();
                app(IntegrationJenkins::class)->removePublishJobForDatabase($database, 'public', $orig_name);
                JenkinsBuild::handleTableDelete($database, $orig_name);
                ForeignDatabase::removeTable($database, $orig_name);
                continue;
            }

            // Handle settings
            $table_settings = array_key_exists('settings', $table) ? $table['settings'] : [];

            foreach ($table_settings as $table_setting) {
                if ($table_setting['added'] ?? FALSE === TRUE) {
                    if (isset($table_setting['value']) && ! empty($table_setting['integration_setting_id'])) {
                        PartnerIntegrationSetting::create([
                            'partner_integration_id' => $database->id,
                            'integration_setting_id' => $table_setting['integration_setting_id'],
                            'value'                  => $table_setting['value'],
                            'table_name'             => $table_name,
                        ]);
                    }
                } elseif ($table_setting['changed'] ?? FALSE === TRUE) {
                    if (isset($table_setting['value']) && ! empty($table_setting['id'])) {
                        PartnerIntegrationSetting::find($table_setting['id'])
                            ->update([
                                'value' => $table_setting['value']
                            ]);
                    }
                }
            }

            if (array_key_exists('schedule', $table)) {
                $schedule = $table['schedule'];

                if ($schedule['added'] ?? FALSE === TRUE) {
                    // Is this a new table?
                    if (! is_null($new_table)) {
                        $table_id = $new_table->id;
                    } elseif (isset($table['id'])) {
                        $table_id = $table['id'];
                    } else {
                        return response()->error('Failed to create new schedule');
                    }

                    $created_schedule = PartnerIntegrationTableSchedule::create([
                        'partner_integration_table_id' => $table_id,
                        'schedule_type_id'             => $schedule['schedule_type_id']
                    ]);

                    if (isset($schedule['properties']) && is_array($schedule['properties'])) {
                        foreach ($schedule['properties'] as $property) {
                            PartnerIntegrationTableSchedulePropertyValue::create([
                                'schedule_id'               => $created_schedule->id,
                                'schedule_type_property_id' => $property['id'],
                                'value'                     => $property['value']
                            ]);
                        }
                    }
                } elseif ($schedule['changed'] ?? FALSE === TRUE) {
                    // Did we change the schedule type?
                    $previous_schedule = PartnerIntegrationTableSchedule::find($schedule['id']);
                    
                    if ($previous_schedule && $schedule['schedule_type_id'] != $previous_schedule->schedule_type_id) {
                        PartnerIntegrationTableSchedule::find($schedule['id'])->update([
                            'schedule_type_id' => $schedule['schedule_type_id']
                        ]);

                        PartnerIntegrationTableSchedulePropertyValue::where('schedule_id', $schedule['id'])->delete();

                        foreach ($schedule['properties'] as $property) {
                            PartnerIntegrationTableSchedulePropertyValue::create([
                                'schedule_id'               => $schedule['id'],
                                'schedule_type_property_id' => $property['id'],
                                'value'                     => $property['value']
                            ]);
                        }
                    } else {
                        $updated_property_ids = [];

                        foreach ($schedule['properties'] as $property) {
                            $updated_property_ids[] = $property['id'];
                            PartnerIntegrationTableSchedulePropertyValue::updateOrCreate(
                                ['schedule_id' => $schedule['id'], 'schedule_type_property_id' => $property['id']],
                                ['value' => $property['value']]
                            );
                        }

                        // Clean up any orphaned schedule properties
                        PartnerIntegrationTableSchedulePropertyValue::where('schedule_id', $schedule['id'])
                            ->whereNotIn('schedule_type_property_id', $updated_property_ids)
                            ->delete();
                    }
                }

                $schedule['name'] = IntegrationScheduleType::find($schedule['schedule_type_id'])->name;

                app(IntegrationJenkins::class)->updateSchedule($database, $schedule, $table_name);
            }
        }

        if (count($new_tables) > 0) {
            app(IntegrationJenkins::class)->runBuilds($database, $new_tables);
        }

        return response()->success([], 'Settings have been saved');
    }

    // Delete is what we use to check advanced tab perms 
    // This will be changed when we make a better perms system
    #[Can(permission: 'delete', product: 'datalake', id: 'database.id')]
    public function version(Request $request, PartnerIntegration $database)
    {
        $database->update([
            'tap_version' => $database->integration->version
        ]);

        return response()->success();
    }

    #[Can(permission: 'delete', product: 'datalake', id: 'database.id')]
    public function destroy(Request $request, PartnerIntegration $database)
    {
        $database->delete();

        if (! empty($database->notificants)) {
            $data = [
                "team" => app('environment')->getTeam(),
                "name" => $database->database,
                "user" => Auth::user()->name
            ];

            Postmark::send($database->notificants, "deleted-database", $data);
        }

        return response()->success([], 'Removed integration');
    }

    public function createIntegrationDatabase(Request $request)
    {
        // todo
        // $check_perm = $this->checkUserPerms('warehouse_create');
        // if (! $check_perm) {
        //     $this->_sendAjax("error", "Access denied", [], 403);

        //     return;
        // }

        if (app(IntegrationJenkins::class)->checkAvailability() === FALSE) {
            return response()->error("Databases cannot be created at this time. Please try again later.");
        }

        $name = strtolower(str_replace(' ', '_', $request->database));

        if (! preg_match('/^[0-9a-z_]+$/', $name)) {
            return response()->error('Invalid character(s) in database name. Name must contain only letters, numbers, and underscores.', [], 400);
        }

        if (PartnerIntegration::where('database', $name)->exists()) {
            return response()->error("Database $name already exists. Please specify another name.", [], 400);
        }

        DB::beginTransaction();

        $database = new PartnerIntegration([
            'database'       => $name,
            'schema'         => 'public',
            'server_id'      => $request->server_id,
            'integration_id' => $request->id,
            'tap_version'    => $request->version
        ]);

        if (DatabaseConnection::databaseExists($database, $database->database)) {
            return response()->error("Database {$name} already exists. Please specify another name.", [], 400);
        }

        $integration = Integration::find($request->id);

        $settings = [];

        if ($request->has('settings') && is_array($request->settings)) {
            $settings = collect($request->settings)->mapWithKeys(fn($item) => [$item['name'] => $item['value']])->toArray();
        }

        foreach ($integration->settings as $setting) {
            if ($setting->is_required && empty($setting->table_name) && $setting->is_private !== TRUE) {
                if (! array_key_exists($setting->name, $settings) || empty($settings[$setting->name])) {
                    return response()->error('You have not entered all of the Connector Settings! You may need to reauthorize Bytespree.');
                }
            }
        }

        if (! empty($integration->client_id)) {
            $settings['client_id'] = $integration->client_id;
        }

        if (! empty($integration->client_secret)) {
            $settings['client_secret'] = $integration->client_secret;
        }

        // Test with the connector...
        $caller = new Connector($integration, new PartnerIntegration());

        if (! $caller->test($settings)) {
            return response()->error("The credentials you provided are invalid. Please update the credentials before creating the database.");
        }

        $tables = [];

        if (isset($request->tables) && ! empty($request->tables)) {
            $tables = $request->tables;
        }

        $database->save();

        foreach ($integration->settings as $setting) {
            if (array_key_exists($setting->name, $settings)) {
                $database->settings()->create([
                    'value'                  => $settings[$setting->name],
                    'integration_setting_id' => $setting->id
                ]);
            }
        }

        if ($integration->use_tables !== TRUE) {
            if (! $request->has('schedule')) {
                DB::rollback();

                return response()->error("Schedule information missing.");
            }

            $database_schedule = PartnerIntegrationSchedule::create([
                'partner_integration_id' => $database->id,
                'schedule_type_id'       => $request->schedule['schedule_type_id']
            ]);

            if (count($request->schedule['properties']) > 0) {
                foreach ($request->schedule['properties'] as $property) {
                    PartnerIntegrationSchedulePropertyValue::create([
                        'schedule_id'               => $database_schedule->id,
                        'schedule_type_property_id' => $property['id'],
                        'value'                     => $property['value'],
                    ]);
                }
            }
        }

        foreach ($request->tables as $key => $table) {
            $data = [
                'is_active'              => $table['is_active'] === TRUE,
                'name'                   => $table['name'],
                'partner_integration_id' => $database->id,
            ];

            if (! empty($table['checkbox']) && ! empty($table['date'])) {
                $date = date('Y-m-d H:i:s', strtotime($table['date']));
                $data['last_started'] = $date;
                $data['minimum_sync_date'] = $date;
            }

            $new_table = PartnerIntegrationTable::create($data);

            if (array_key_exists('settings', $table)) {
                $table_settings = collect($table["settings"])->mapWithKeys(fn($item) => [$item['name'] => $item['value']])->toArray();
                foreach ($integration->settings as $setting) {
                    if (array_key_exists($setting->name, $table_settings)) {
                        $database->settings()->create([
                            'value'                  => $table_settings[$setting->name],
                            'integration_setting_id' => $setting->id,
                            'table_name'             => $table['name']
                        ]);
                    }
                }
            }

            if (! empty($new_table->id) && array_key_exists('schedule', $table)) {
                $table_schedule = PartnerIntegrationTableSchedule::create([
                    'partner_integration_table_id' => $new_table->id,
                    'schedule_type_id'             => $table['schedule']['schedule_type_id']
                ]);
                
                if (count($table['schedule']['properties']) > 0) {
                    foreach ($table['schedule']['properties'] as $property) {
                        PartnerIntegrationTableSchedulePropertyValue::create([
                            'schedule_id'               => $table_schedule->id,
                            'schedule_type_property_id' => $property['id'],
                            'value'                     => $property['value'],
                        ]);
                    }
                }
            }
        }

        // Everything saved - commit the transaction
        DB::commit();

        if (! DatabaseConnection::createDatabase($database, $database->database)) {
            logger()->error(
                "Failed to create database for unknown reason.",
                [
                    'database'      => $database->id,
                    'server_id'     => $database->server->id,
                    'database_name' => $database->database,
                ]
            );

            $database->delete();

            return response()->error('Failed to create database.', [], 500);
        }

        $table_names = $database->tables->pluck('name')->toArray();

        app(IntegrationJenkins::class)->createAllJobs($database, $table_names);

        if ($request->use_tables !== TRUE) {
            if ($request->has('schedule')) {
                app(IntegrationJenkins::class)->updateSchedule($database, $request->schedule);
            }
        } else {
            foreach ($request->tables as $table) {
                if (array_key_exists('schedule', $table)) {
                    app(IntegrationJenkins::class)->updateSchedule($database, $table['schedule'], $table['name']);
                }
            }
        }

        if (! Auth::user()->is_admin) {
            $role = Role::where('role_name', 'Database Admin')->first();

            Auth::user()->assignRole($role, $database->id);
        }
 
        app(IntegrationJenkins::class)->runBuilds($database, $table_names);

        return response()->success($database, 'Database created');
    }

    public function requestAccess(Request $request, PartnerIntegration $database)
    {
        $reason = $request->reason;
        $database_name = $database->database;
        $user_full_name = Auth::user()->name;
        $email_addresses = User::isAdmin()->pluck('email')->toArray();
        $database_id = $database->id;

        if (empty($email_addresses)) {
            return response()->error('Could not send access request. No admins found.', [], 500);
        }

        if (empty($database_name) || empty($user_full_name)) {
            logger()->error(
                'Could not send access request',
                compact('database_id', 'database_name', 'user_full_name', 'reason')
            );

            return response()->error('Could not send access request.', [], 500);
        }

        $data = [
            'name'     => $user_full_name,
            'database' => $database_name,
            'region'   => app('environment')->getRegionName(),
            'team'     => app('environment')->getTeam()
        ];

        if (! empty($reason)) {
            $data['reason'] = ['text' => $reason];
        }

        $result = Postmark::send($email_addresses, "request-access", $data);

        if (! $result) {
            logger()->error(
                'Could not send access request',
                compact('database_id', 'database_name', 'user_full_name', 'reason')
            );

            return response()->error('Could not send access request.', [], 500);
        }

        return response()->success(message: 'Access request sent');
    }
}
