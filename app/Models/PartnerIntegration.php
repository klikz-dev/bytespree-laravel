<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use DB;
use Exception;
use App\Models\Explorer\Project;
use App\Models\Manager\JenkinsBuild;
use App\Models\Manager\DatabaseHookKey;
use App\Models\Manager\DatabaseTag;
use App\Classes\Database\Connection;
use App\Classes\Database\SqlUser;
use App\Classes\Database\ForeignDatabase;
use App\Classes\IntegrationJenkins;
use App\Models\Explorer\ProjectForeignDatabase;
use App\Models\Bytespree\JenkinsBuildsLatestSyncs;
use App\Models\Bytespree\JenkinsBuildsLatestNoTableSyncs;
use App\Models\Explorer\ProjectColumnAttachment;
use App\Models\Explorer\ProjectColumnComment;
use App\Models\Explorer\ProjectColumnFlag;
use App\Models\Explorer\ProjectColumnMapping;

/**
 * App\Models\PartnerIntegration
 *
 * @property        int                                                                                $id
 * @property        int|null                                                                           $integration_id
 * @property        int|null                                                                           $server_id
 * @property        string|null                                                                        $schema
 * @property        string|null                                                                        $database
 * @property        bool|null                                                                          $is_active
 * @property        bool|null                                                                          $is_running
 * @property        string|null                                                                        $notificants
 * @property        bool|null                                                                          $use_ssl
 * @property        string|null                                                                        $ca_certificate
 * @property        int|null                                                                           $managed_database_id
 * @property        bool|null                                                                          $is_complete
 * @property        bool|null                                                                          $is_deleted
 * @property        bool|null                                                                          $retry_syncs
 * @property        \Illuminate\Support\Carbon|null                                                    $created_at
 * @property        \Illuminate\Support\Carbon|null                                                    $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                    $deleted_at
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\IntegrationCallback[]         $callbacks
 * @property        int|null                                                                           $callbacks_count
 * @property        DatabaseHookKey|null                                                               $hook_key
 * @property        \App\Models\Integration|null                                                       $integration
 * @property        \Illuminate\Database\Eloquent\Collection|JenkinsBuild[]                            $logs
 * @property        int|null                                                                           $logs_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\PartnerIntegrationMigration[] $migrations
 * @property        int|null                                                                           $migrations_count
 * @property        \Illuminate\Database\Eloquent\Collection|Project[]                                 $projects
 * @property        int|null                                                                           $projects_count
 * @property        \App\Models\PartnerIntegrationSchedule|null                                        $schedule
 * @property        \App\Models\Server|null                                                            $server
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\PartnerIntegrationSetting[]   $settings
 * @property        int|null                                                                           $settings_count
 * @property        \App\Models\PartnerIntegrationSqlUser|null                                         $sqlUser
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\PartnerIntegrationTable[]     $tables
 * @property        int|null                                                                           $tables_count
 * @property        \Illuminate\Database\Eloquent\Collection|DatabaseTag[]                             $tags
 * @property        int|null                                                                           $tags_count
 * @method   static \Database\Factories\PartnerIntegrationFactory                                      factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegration                              onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereCaCertificate($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereDatabase($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereIsActive($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereIsComplete($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereIsRunning($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereManagedDatabaseId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereNotificants($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereRetrySyncs($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereSchema($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereServerId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegration                           whereUseSsl($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegration                              withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegration                              withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegration extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integrations';

    protected $with = 'server';
    
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($database) {
            $database->sqlUser()->delete();
            $database->schedule()->delete();
            $database->migrations()->delete();

            $database->tables->each(function ($table) {
                $table->delete();
            });

            $database->settings->each(function ($setting) {
                $setting->delete();
            });

            PartnerIntegrationForeignDatabase::where('foreign_control_id', $database->id)->each(function ($foreign_database) {
                ProjectColumnFlag::where('schema_name', $foreign_database->schema_name)->delete();
                ProjectColumnComment::where('schema_name', $foreign_database->schema_name)->delete();
                ProjectColumnMapping::where('schema_name', $foreign_database->schema_name)->delete(); // Todo: recursion into mapping_conditions?
                ProjectColumnAttachment::where('schema_name', $foreign_database->schema_name)->delete();
            });

            // Delete Jenkins stuff
            app(IntegrationJenkins::class)->deleteIntegration($database);
                
            $database->projects->each(function ($project) {
                $project->delete();
            });

            $database->deleteForeignDataWrappers();

            // Drop it from the server
            Connection::dropDatabase($database, $database->database);
        });
    }

    public function integration()
    {
        return $this->belongsTo(Integration::class, 'integration_id');
    }

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function sqlUser()
    {
        return $this->hasOne(PartnerIntegrationSqlUser::class, 'partner_integration_id');
    }

    public function settings()
    {
        // todo : fix this up for use in data lake
        return $this->hasMany(PartnerIntegrationSetting::class, 'partner_integration_id');
    }

    public function migrations()
    {
        return $this->hasMany(PartnerIntegrationMigration::class, 'partner_integration_id');
    }

    public function schedule()
    {
        return $this->hasOne(PartnerIntegrationSchedule::class, 'partner_integration_id');
    }

    public function tables()
    {
        return $this->hasMany(PartnerIntegrationTable::class, 'partner_integration_id');
    }

    public function hook_key()
    {
        return $this->hasOne(DatabaseHookKey::class, 'partner_integration_id');
    }

    public function logs()
    {
        return $this->hasMany(JenkinsBuild::class, 'parameters->4')
            ->where('parameters->1', 'Services')
            ->where('parameters->2', 'Run')
            ->where('jenkins_build_id', '>', 0)
            ->select('*')
            ->addSelect('parameters->3 as job_type')
            ->addSelect(DB::raw('to_timestamp(build_timestamp / 1000)::timestamp as build_timestamp_formatted'));
    }

    public function projects()
    {
        // todo maybe make this a better relationship?
        return $this->hasMany(Project::class, 'partner_integration_id');
    }

    public function foreign_projects()
    {
        $projects = [];
        $foreign_databases = $this->foreign_databases();

        foreach ($foreign_databases as $foreign_database) {
            $foreign_projects = ProjectForeignDatabase::with('project')
                ->where('foreign_database_id', $foreign_database->id)
                ->get();

            $projects = array_merge($projects, $foreign_projects->toArray());
        }

        return $projects;
    }

    public function foreign_databases()
    {
        return PartnerIntegrationForeignDatabase::where('foreign_control_id', $this->id)->get();
    }

    public function warehouse_foreign_databases()
    {
        return PartnerIntegrationForeignDatabase::dataLake()->where('foreign_control_id', $this->id)->with('database')->get();
    }

    public function callbacks()
    {
        return $this->hasMany(IntegrationCallback::class, 'control_id');
    }

    public function tags()
    {
        return $this->hasMany(DatabaseTag::class, 'control_id')
            ->join('dw_tags as bt', 'bt.id', '=', 'dw_database_tags.tag_id')
            ->select('dw_database_tags.*', 'bt.name', 'bt.color');
    }

    public function jenkins_latest_syncs()
    {
        if ($this->integeration && $this->integration->use_tables) {
            return $this->hasMany(JenkinsBuildsLatestSyncs::class, 'database_id');
        }
  
        return $this->hasMany(JenkinsBuildsLatestNoTableSyncs::class, 'database_id');
    }

    /**
     * Gets database users as an array
     *
     * @param  int   $control_id The id of the database
     * @return array
     */
    public function getDatabaseUsers()
    {
        $connection = Connection::connect($this, TRUE);
        
        $users = array_column($connection->select('SELECT usename FROM pg_catalog.pg_user'), 'usename');

        $connection->disconnect();

        return $users;
    }

    /**
     * Generate a read-only user using the partner integration ID
     *
     * @param  int    $control_id The partner integration id of the database
     * @return string
     */
    public function generateReadOnlyUser()
    {
        $suffix = '_readonly';
        $users = $this->getDatabaseUsers();

        return $this->generateUserName(50, $this->database, $suffix, $users);
    }

    /**
     * Generate a unique user name
     *
     * @param  int    $max_length     The maximum length of the username
     * @param  string $prefix         String that represents first part of the username
     * @param  string $suffix         String that represents the end of the username
     * @param  array  $existing_users Array of existing users
     * @return string
     */
    public function generateUserName($max_length, $prefix, $suffix, $existing_users)
    {
        $prefix_max_length = $max_length - strlen($suffix) - 3;
        $username = $prefix . $suffix;
        if (strlen($username) < $max_length) {
            if (! in_array($username, $existing_users)) {
                return $username;
            }
        }

        for ($i = 1; $i < 999; ++$i) {
            $prefix = substr($prefix, 0, $prefix_max_length) . str_pad($i, 3, '0', STR_PAD_LEFT);
            $username = $prefix . $suffix;
            if (! in_array($username, $existing_users)) {
                return $username;
            }
        }

        return $username;
    }

    public function createSqlUser(string $username, string $password)
    {
        $sqlUser = new SqlUser();

        try {
            $sqlUser->createUser($this, $username, $password);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== FALSE) {
                throw new Exception('User already exists');
            }

            throw new Exception("Could not create SQL user {$username} for partner integration {$this->id}");
        }
        
        $schemas = array_column(
            $sqlUser->getSchemas($this, all: FALSE),
            'schema_name'
        );

        $schemas[] = 'public';
        
        foreach ($schemas as $schema) {
            $result = $sqlUser->grantAccessToSchema($this, $username, $schema);

            if (! $result) {
                throw new Exception("Could not create SQL user {$username} for partner integration {$this->id}");
            }
        }

        return PartnerIntegrationSqlUser::create([
            'username'               => $username,
            'password'               => $password,
            'partner_integration_id' => $this->id,
        ]);
    }

    public function dropSqlUser(PartnerIntegrationSqlUser $user)
    {
        app(SqlUser::class)->drop($this, $user->username, $this->server->username);

        $user->delete();
    }

    public function updateSqlUserPassword(PartnerIntegrationSqlUser $user, string $password)
    {
        app(SqlUser::class)->setPassword($this, $user->username, $password);

        $user->update(['password' => $password]);
    }

    public function ensureForeignDataWrapperExists(PartnerIntegration $foreign_database, Product $product, string $schema_name = NULL)
    {
        $partner_integration_foreign_database = PartnerIntegrationForeignDatabase::where('control_id', $this->id)
            ->where('foreign_control_id', $foreign_database->id)
            ->where('product_id', $product->id)
            ->first();
            
        if ($partner_integration_foreign_database) {
            return $partner_integration_foreign_database->id;
        }
        
        $server_name = uniqid('d');

        if (empty($schema_name)) {
            $schema_name = $server_name;
        }

        app(ForeignDatabase::class)->create($this, $foreign_database, $server_name, $schema_name);

        $partner_integration_foreign_database = PartnerIntegrationForeignDatabase::create([
            "control_id"          => $this->id,
            "foreign_control_id"  => $foreign_database->id,
            "foreign_server_name" => $server_name,
            "product_id"          => $product->id,
            "schema_name"         => $schema_name ?? $server_name
        ]);
    
        return $partner_integration_foreign_database->id;
    }

    public function connectorTap()
    {
        $path = config('app.connector_path') . "/taps/{$this->integration->safe_name}/{$this->tap_version}/tap.php";
        if (file_exists($path)) {
            return $path;
        }  
        throw new Exception('This databases current version was not found on the file system.');
    }

    /**
     * Get a key value pair of our settings. Will override parent's client_id and client_secret if defined.
     */
    public function getKeyValueSettings(): array
    {
        $this->load('settings', 'settings.setting', 'integration');

        $settings = collect($this->settings)->mapWithKeys(fn($item) => [$item->setting->name => $item->value])->toArray();
        
        if ($this->integration->is_oauth === TRUE) {
            if (! array_key_exists('client_id', $settings)) {
                $settings['client_id'] = $this->integration->client_id;
            }
                    
            if (! array_key_exists('client_secret', $settings)) {
                $settings['client_secret'] = $this->integration->client_secret;
            }
        }

        return $settings;
    }

    /**
     * Get all partner integrations
     *
     * @return Collection Collection of all partner integrations with statuses attached
     */
    public static function getAllWithBuildStatus()
    {
        return DB::table('di_partner_integrations as p')
            ->leftJoin('di_integrations as i', 'i.id', '=', 'p.integration_id')
            ->leftJoin('__bytespree.v_dw_jenkins_builds__latest_results_by_database as r', 'r.database_id', '=', 'p.id')
            ->select([
                'p.id',
                'p.integration_id',
                'p.server_id',
                'p.database',
                'p.is_active',
                'p.notificants',
                'p.retry_syncs',
                'p.created_at',
                'p.updated_at',
                'i.fully_replace_tables',
                'i.name as integration_name',
                'i.use_tables',
                'i.use_hooks',
                'i.class_name',
                'r.result_code',
                DB::raw("COALESCE(r.status_color, 'blue') as status_color"),
                'r.failed_jobs',
                'r.is_running'
            ])
            ->whereNull('p.deleted_at')
            ->orderBy('i.name')
            ->orderBy('p.database')
            ->get();
    }

    /**
     * Get a key value pair of our settings. Will override parent's client_id and client_secret if defined.
     */
    public function getKeyValueTableSettings(string $table): array
    {
        $this->load('settings');

        $settings = collect($this->settings->where('table_name', $table))->mapWithKeys(fn($item) => [$item->setting->name => $item->value])->toArray();

        return $settings;
    }
    
    public function deleteForeignDataWrappers()
    {
        PartnerIntegrationForeignDatabase::where('control_id', $this->id)
            ->orWhere('foreign_control_id', $this->id)
            ->each(function ($foreign_database) {
                try {
                    if ($foreign_database->foreign_control_id == $this->id) {
                        $partner_integration = PartnerIntegration::find($foreign_database->control_id);
                    } else {
                        $partner_integration = $this;
                    }

                    $connection = Connection::connect($partner_integration);

                    $sql = <<<SQL
                        DROP SCHEMA IF EXISTS "{$foreign_database->schema_name}" CASCADE
                        SQL;
        
                    $connection->statement($sql);
        
                    $sql = <<<SQL
                        DROP FOREIGN DATA WRAPPER IF EXISTS "{$foreign_database->foreign_server_name}"
                        SQL;
        
                    $connection->statement($sql);

                    ProjectForeignDatabase::where('foreign_database_id', $foreign_database->id)->delete();
                    PartnerIntegrationForeignDatabase::where('id', $foreign_database->id)->delete();
                } catch (Exception $e) {
                    // Do nothing, for now...
                    // todo: maybe log this? There's many reasons this could fail, and we don't want to stop the process
                }
            });
    }
}
