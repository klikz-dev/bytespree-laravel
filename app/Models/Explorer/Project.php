<?php

namespace App\Models\Explorer;

use App\Classes\Database\Connection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationSqlUser;
use App\Models\Manager\Tag;
use App\Models\Manager\DatabaseTag;
use App\Models\Product;
use App\Classes\Database\Schema;
use App\Classes\Database\SqlUser;
use App\Classes\Postmark;
use App\Classes\IntegrationJenkins;
use App\Models\PartnerIntegrationForeignDatabase;
use DB;
use Exception;

/**
 * App\Models\Explorer\Project
 *
 * @property        int                                                                                       $id
 * @property        int|null                                                                                  $partner_integration_id
 * @property        int|null                                                                                  $destination_schema_id
 * @property        string|null                                                                               $name
 * @property        string|null                                                                               $description
 * @property        string|null                                                                               $created_by
 * @property        bool|null                                                                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                           $created_at
 * @property        \Illuminate\Support\Carbon|null                                                           $updated_at
 * @property        string|null                                                                               $updated_by
 * @property        string|null                                                                               $display_name
 * @property        \Illuminate\Support\Carbon|null                                                           $deleted_at
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectColumnComment[]      $comments
 * @property        int|null                                                                                  $comments_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectColumnFlag[]         $flags
 * @property        int|null                                                                                  $flags_count
 * @property        \Illuminate\Database\Eloquent\Collection|PartnerIntegration[]                             $foreign_databases
 * @property        int|null                                                                                  $foreign_databases_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectHyperlink[]          $links
 * @property        int|null                                                                                  $links_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectColumnMapping[]      $mappings
 * @property        int|null                                                                                  $mappings_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectTableNote[]          $notes
 * @property        int|null                                                                                  $notes_count
 * @property        PartnerIntegration|null                                                                   $primary_database
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectPublishingSchedule[] $publishers
 * @property        int|null                                                                                  $publishers_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectSavedQuery[]         $saved_queries
 * @property        int|null                                                                                  $saved_queries_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectSnapshot[]           $snapshots
 * @property        int|null                                                                                  $snapshots_count
 * @property        \Illuminate\Database\Eloquent\Collection|Tag[]                                            $tags
 * @property        int|null                                                                                  $tags_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectView[]               $views
 * @property        int|null                                                                                  $views_count
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Project                                                onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereCreatedBy($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereDestinationSchemaId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereDisplayName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             wherePartnerIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Project                                             whereUpdatedBy($value)
 * @method   static \Illuminate\Database\Query\Builder|Project                                                withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Project                                                withoutTrashed()
 * @mixin \Eloquent
 */
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_projects';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($project) {
            $project->dropSchema();

            ProjectAttachment::where('project_id', $project->id)->delete();
            ProjectColumnAttachment::where('project_id', $project->id)->delete();
            ProjectColumnComment::where('project_id', $project->id)->delete();
            ProjectColumnFlag::where('project_id', $project->id)->delete();
            ProjectColumnMapping::where('project_id', $project->id)->delete();
            ProjectForeignDatabase::where('project_id', $project->id)->delete();
            ProjectHyperlink::where('project_id', $project->id)->delete();
            ProjectSavedQuery::where('project_id', $project->id)->delete();
            ProjectSettingValue::where('project_id', $project->id)->delete();
            ProjectSnapshot::where('project_id', $project->id)->delete();
            ProjectTableNote::where('project_id', $project->id)->delete();
            ProjectUser::where('project_id', $project->id)->delete();
            ProjectView::where('project_id', $project->id)->delete();

            app(IntegrationJenkins::class)->removePublishJobForProject($project);
            app(IntegrationJenkins::class)->removePublishProjectFolder($project);
        });
    }

    public static function getProjects()
    {
        return DB::table('bp_projects as bp')
            ->select('bp.*', 'pi.integration_id', 'pi.database', 'di.name as integration_name')
            ->join("di_partner_integrations as pi", "pi.id", "bp.partner_integration_id")
            ->leftJoin("di_integrations as di", "di.id", "pi.integration_id")
            ->where('bp.is_deleted', FALSE)
            ->get();
    }

    public function members()
    {
        $results = User::getUsersByProductNameAndChildId('studio', $this->id);

        $user_handles = [];
        $users = [];
        foreach ($results as $key => $result) {
            if (! in_array($result->user_handle, $user_handles)) {
                $user_handles[] = $result->user_handle;
                $users[] = $result;
            }
        }

        return $users;
    }

    public function tags()
    {
        return $this->hasManyThrough(
            Tag::class,
            DatabaseTag::class,
            'control_id',
            'id',
            'partner_integration_id',
            'tag_id'
        );
    }

    public function mappings()
    {
        return $this->hasMany(ProjectColumnMapping::class);
    }

    public function flags()
    {
        return $this->hasMany(ProjectColumnFlag::class);
    }

    public function comments()
    {
        return $this->hasMany(ProjectColumnComment::class);
    }

    public function column_attachments()
    {
        return $this->hasMany(ProjectColumnAttachment::class);
    }

    public function snapshots()
    {
        return $this->hasMany(ProjectSnapshot::class);
    }

    public function views()
    {
        return $this->hasMany(ProjectView::class);
    }

    public function saved_queries()
    {
        return $this->hasMany(ProjectSavedQuery::class);
    }

    public function links()
    {
        return $this->hasMany(ProjectHyperlink::class);
    }

    public function notes()
    {
        return $this->hasMany(ProjectTableNote::class);
    }

    public function publishers()
    {
        return $this->hasMany(ProjectPublishingSchedule::class);
    }

    public function primary_database()
    {
        return $this->hasOne(PartnerIntegration::class, 'id', 'partner_integration_id')->without(['server']);
    }

    public function sql_user()
    {
        return $this->hasOne(PartnerIntegrationSqlUser::class, 'project_id', 'id');
    }

    public function foreign_databases()
    {
        return $this->hasManyThrough(
            PartnerIntegration::class,
            ProjectForeignDatabase::class,
            'project_id',
            'id',
            'id',
            'foreign_database_id'
        );
    }

    public function getForeignDatabaseQuery()
    {
        return DB::table('bp_project_foreign_databases')
            ->join('di_partner_integration_foreign_databases', 'bp_project_foreign_databases.foreign_database_id', '=', 'di_partner_integration_foreign_databases.id')
            ->join('di_partner_integrations', 'di_partner_integration_foreign_databases.foreign_control_id', '=', 'di_partner_integrations.id')
            ->whereNull('bp_project_foreign_databases.deleted_at')
            ->where('bp_project_foreign_databases.project_id', $this->id);
    }

    /**
     * Get all foreign databases for all projects. This could be a relationship, but
     * nesting this many relationships is less than ideal and not performant.
     */
    public static function getAllProjectForeignDatabases()
    {
        return DB::table('bp_project_foreign_databases')
            ->join('di_partner_integration_foreign_databases', 'bp_project_foreign_databases.foreign_database_id', '=', 'di_partner_integration_foreign_databases.id')
            ->join('di_partner_integrations', 'di_partner_integration_foreign_databases.foreign_control_id', '=', 'di_partner_integrations.id')
            ->whereNull('bp_project_foreign_databases.deleted_at')
            ->get();
    }

    public static function createProject(PartnerIntegration $database, string $display_name, string $schema_name, string $description = NULL, User $user, $foreign_databases = NULL, $destination_schema_id = NULL) : Project
    {
        $project = Project::create([
            "partner_integration_id" => $database->id,
            "destination_schema_id"  => $destination_schema_id,
            "display_name"           => $display_name,
            "name"                   => $schema_name,
            "description"            => $description,
            "created_by"             => $user->user_handle
        ]);

        if (! empty($foreign_databases)) {
            $product = Product::where('name', 'studio')->first();
            foreach ($foreign_databases as $foreign_database) {
                if ($foreign_database->id != $database->id) {
                    $foreign_database_id = $database->ensureForeignDataWrapperExists($foreign_database, $product);

                    if (! is_null($foreign_database_id)) {
                        ProjectForeignDatabase::updateOrCreate([
                            "project_id"          => $project->id,
                            "foreign_database_id" => $foreign_database_id
                        ]);
                    }
                }
            }
        }

        app(Schema::class)->createSchema($database, $schema_name);
        
        if (! empty($database->sqlUser)) {
            app(SqlUser::class)->grantAccessToSchema($database, $database->sqlUser->username, $schema_name);
        }

        return $project;
    }

    public function updateForeignDatabases($foreign_databases)
    {
        $product = Product::where('name', 'studio')->first();

        $existing_foreign_databases = $this->getForeignDatabaseQuery()->get();

        // Are we removing any?
        $foreign_databases_to_remove = $existing_foreign_databases->filter(function ($project_foreign_database) use ($foreign_databases) {
            return $foreign_databases->where('id', $project_foreign_database->foreign_control_id)->count() === 0;
        });

        $foreign_databases_with_dependents = $foreign_databases_to_remove->filter(function ($foreign_database_entry) {
            $dependents = app(Schema::class)->dependentViews($this->primary_database, $foreign_database_entry->schema_name);

            return count($dependents) > 0;
        });

        $foreign_databases_to_remove->each(function ($project_foreign_database) {
            ProjectForeignDatabase::where('project_id', $this->id)
                ->where('foreign_database_id', $project_foreign_database->foreign_database_id)
                ->delete();
        });

        // If any of the foreign databases to be removed have dependents, return before modifying anything
        if ($foreign_databases_with_dependents->count() > 0) {
            return FALSE;
        }

        foreach ($foreign_databases as $foreign_database) {
            if ($foreign_database->id != $this->primary_database->id) {
                $foreign_database_id = $this->primary_database->ensureForeignDataWrapperExists($foreign_database, $product);
                ProjectForeignDatabase::updateOrCreate([
                    "project_id"          => $this->id,
                    "foreign_database_id" => $foreign_database_id,
                ]);
            }
        }

        // Todo: Old code didn't remove the foreign data wrapper, but should it?
        return TRUE;
    }

    public function activity(int $limit, int $offset)
    {
        $from = <<<SQL
            (
                (select c.comment_text, c.created_at, c.project_id, c.is_deleted, c.user_id, c.table_name, c.column_name, c.schema_name, 'comment' as activity_type from bp_project_column_comments as c where c.is_auto_generated = false)
                union all
                (select f.flag_reason, f.created_at, f.project_id, f.is_deleted, f.user_id, f.table_name, f.column_name, f.schema_name, 'flag' as activity_type from bp_project_column_flags as f)
            )
            SQL;

        $logic = <<<SQL
            join u_users as u on results.user_id = u.user_handle 
            where results.project_id = $this->id and results.is_deleted = false 
            SQL;

        $sql = <<<SQL
            select results.*, u.name as full_name from $from results
            $logic
            order by results.created_at desc
            offset $offset limit $limit 
            SQL;

        $count_sql = <<<SQL
            select count(results.*) as count from $from results 
            $logic
            SQL;

        $activity = collect(DB::select($sql))
            ->map(function ($activity) {
                $activity->created_at_formatted = '';
                $activity->comment_text = strip_tags(html_entity_decode($activity->comment_text));

                return $activity;
            });

        $counts = collect(DB::select($count_sql))->first();

        return (object) ["activity" => $activity, "counts" => $counts->count];
    }

    public function isComplete(): Attribute
    {
        return Attribute::make(
            get: fn($value) => is_null($this->primary_database) ? FALSE : $this->primary_database->is_complete
        );
    }

    public function sendCompletedEmail($feature, $action, $table, $column, $username, $comment_text = "")
    {
        if (! $this->primary_database) {
            return;
        }

        if (! $this->primary_database->is_complete) {
            return;
        }

        if (! empty($this->primary_database->notificants)) {
            $data = [
                "feature"      => $feature,
                "name"         => $this->display_name,
                "action"       => $action,
                "table"        => $table,
                "column"       => $column,
                "user"         => $username,
                "comment_text" => $comment_text
            ];

            return Postmark::send($this->primary_database->notificants, "completed-project-change", $data);
        }

        return TRUE;
    }

    /**
     * Get a key/value pair array of all schemas available to this project.
     * [[schema_nice_name => actual_schema_name]] where actual_schema_name could be the foreign schema, e.g. [["my_database" => "6ef3cabdcf"]]
     */
    public function getSchemas(): array
    {
        $schemas = PartnerIntegrationForeignDatabase::schemas($this->primary_database, "studio");

        return array_filter($schemas, function ($database) {
            return $this->getForeignDatabaseQuery()->where('database', $database)->count() > 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Drop a project's schema (name) from the primary database
     */
    public function dropSchema()
    {
        app(Schema::class)->dropSchema($this->primary_database, $this->name);
    }

    /**
     * Create a new SQL user for this project
     */
    public function createSqlUser(string $username, string $password): PartnerIntegrationSqlUser
    {
        try {
            // Delete and drop pre-existing SQL users
            PartnerIntegrationSqlUser::where('project_id', $this->id)->each(function ($user) {
                $this->primary_database->dropSqlUser($user);
                $user->delete();
            });

            // Wrap permissions in a transaction in case there's weirdness
            Connection::connect($this->primary_database)->transaction(function () use ($username, $password) {
                app(SqlUser::class)->createUser($this->primary_database, $username, $password);

                SqlUser::grantReadOnlyAccessToSchema($this->primary_database, $username, 'public');

                SqlUser::grantAll($this->primary_database, $username, $this->name);
                                        
                // Read only permissions to all foreign databases in the project
                foreach ($this->getSchemas() as $name => $schema) {
                    SqlUser::grantReadOnlyAccessToSchema($this->primary_database, $username, $schema);
                }
            });

            $user = PartnerIntegrationSqlUser::create([
                'project_id' => $this->id,
                'username'   => $username,
                'password'   => $password,
                'product_id' => Product::where('name', 'studio')->first()->id,
            ]);

            return $user;
        } catch (Exception $e) {
            logger()->error(
                'Studio SQL user creation failed',
                [
                    'project_id' => $this->id,
                    'exception'  => $e
                ]
            );

            throw $e;
        }
    }

    /**
     * Generate a unique username for a project's SQL user
     */
    public function generateSqlUsername(): string
    {
        $project_name = substr($this->name, 0, 32);
        $uid = uniqid();

        return "studio_{$project_name}_{$uid}";
    }
}
