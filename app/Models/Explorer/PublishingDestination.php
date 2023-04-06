<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\PublishingDestination
 *
 * @property        int                                                                                       $id
 * @property        string|null                                                                               $name
 * @property        string|null                                                                               $class_name
 * @property        bool|null                                                                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                           $created_at
 * @property        \Illuminate\Support\Carbon|null                                                           $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                           $deleted_at
 * @property        \App\Models\Explorer\Publisher|null                                                       $publisher
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\ProjectPublishingSchedule[] $schedules
 * @property        int|null                                                                                  $schedules_count
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PublishingDestination                                  onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               whereClassName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PublishingDestination                               whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|PublishingDestination                                  withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PublishingDestination                                  withoutTrashed()
 * @mixin \Eloquent
 */
class PublishingDestination extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_publishing_destinations';

    public function publisher()
    {
        return $this->hasOne(Publisher::class);
    }

    public function schedules()
    {
        return $this->hasMany(ProjectPublishingSchedule::class);
    }

    public static function className($class_name)
    {
        return self::where('class_name', $class_name)->first();
    }
}
