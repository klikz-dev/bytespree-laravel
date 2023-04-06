<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PartnerIntegration;
use DB;

/**
 * App\Models\Explorer\ProjectPublishingSchedule
 *
 * @property        int                                                                          $id
 * @property        int|null                                                                     $project_id
 * @property        int|null                                                                     $destination_id
 * @property        array|null                                                                   $destination_options
 * @property        string|null                                                                  $username
 * @property        string|null                                                                  $table_name
 * @property        string|null                                                                  $schema_name
 * @property        array|null                                                                   $schedule
 * @property        bool|null                                                                    $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                              $created_at
 * @property        \Illuminate\Support\Carbon|null                                              $updated_at
 * @property        int|null                                                                     $publisher_id
 * @property        string|null                                                                  $status
 * @property        string|null                                                                  $last_ran
 * @property        \Illuminate\Support\Carbon|null                                              $deleted_at
 * @property        \App\Models\Explorer\PublishingDestination|null                              $destination
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\PublisherLog[] $logs
 * @property        int|null                                                                     $logs_count
 * @property        \App\Models\Explorer\Project|null                                            $project
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectPublishingSchedule                 onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereDestinationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereDestinationOptions($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereLastRan($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              wherePublisherId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereSchedule($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereSchemaName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereStatus($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectPublishingSchedule              whereUsername($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectPublishingSchedule                 withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectPublishingSchedule                 withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectPublishingSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_publishing_schedules';

    protected $with = 'destination';

    protected $casts = [
        'destination_options' => 'json',
        'schedule'            => 'json',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function destination()
    {
        return $this->belongsTo(PublishingDestination::class);
    }

    public function logs()
    {
        return $this->hasMany(PublisherLog::class);
    }

    public static function scopeDatabase($query, PartnerIntegration $database)
    {
        $project_ids = Project::where('partner_integration_id', $database->id)->pluck('id');

        return $query->whereIn('project_id', $project_ids);
    }

    public static function getCountsForDestination(PublishingDestination $destination, int $invididual_id, string $id_name)
    {
        return self::select(['bp_projects.display_name as name', DB::raw('count(*)')])
            ->where(DB::raw("bp_project_publishing_schedules.destination_options->>'$id_name'"), $invididual_id)
            ->where('bp_project_publishing_schedules.destination_id', $destination->id)
            ->whereNull('bp_project_publishing_schedules.deleted_at')
            ->join("bp_projects", "bp_projects.id", "=", "bp_project_publishing_schedules.project_id")
            ->groupBy('bp_projects.display_name')
            ->get();
    }

    public static function checkSnapshotDuplicate(Project $project, int $id, string $snapshot_name)
    {
        $destination = PublishingDestination::className('Snapshot');

        $sql = <<<SQL
            SELECT id
            FROM public.bp_project_publishing_schedules
            WHERE
                project_id = $project->id AND
                destination_id = $destination->id AND
                (destination_options -> 'append_timestamp')::boolean = false AND
                (destination_options ->> 'name')::varchar = '$snapshot_name' AND
                is_deleted  = false AND
                id != $id
            limit 1
            SQL;

        if (count(DB::select($sql)) == 0) {
            return FALSE;
        }

        return TRUE;
    }
}
