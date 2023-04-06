<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\PublisherLog
 *
 * @property        int                                                 $id
 * @property        int|null                                            $project_id
 * @property        int|null                                            $destination_id
 * @property        int|null                                            $publisher_id
 * @property        int|null                                            $project_publishing_schedule_id
 * @property        int|null                                            $jenkins_build_id
 * @property        int|null                                            $user_id
 * @property        string|null                                         $type
 * @property        string|null                                         $publishing_started
 * @property        string|null                                         $publishing_finished
 * @property        bool|null                                           $is_deleted
 * @property        \Illuminate\Support\Carbon|null                     $created_at
 * @property        \Illuminate\Support\Carbon|null                     $updated_at
 * @property        \Illuminate\Support\Carbon|null                     $deleted_at
 * @property        \App\Models\Explorer\PublishingDestination|null     $destination
 * @property        \App\Models\Explorer\Project|null                   $project
 * @property        \App\Models\Explorer\ProjectPublishingSchedule|null $schedule
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PublisherLog     onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereDestinationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereJenkinsBuildId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereProjectPublishingScheduleId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  wherePublisherId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  wherePublishingFinished($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  wherePublishingStarted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublisherLog  whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|PublisherLog     withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PublisherLog     withoutTrashed()
 * @mixin \Eloquent
 */
class PublisherLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_publisher_logs';

    protected $with = 'schedule';

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function destination()
    {
        return $this->belongsTo(PublishingDestination::class);
    }

    public function schedule()
    {
        return $this->belongsTo(ProjectPublishingSchedule::class, 'project_publishing_schedule_id');
    }
}
