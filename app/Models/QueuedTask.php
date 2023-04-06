<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\QueuedTask
 *
 * @property        int                                              $id
 * @property        string|null                                      $name
 * @property        string|null                                      $status
 * @property        mixed|null                                       $task_data
 * @property        mixed|null                                       $work_data
 * @property        bool|null                                        $is_deleted
 * @property        \Illuminate\Support\Carbon|null                  $created_at
 * @property        \Illuminate\Support\Carbon|null                  $updated_at
 * @property        \Illuminate\Support\Carbon|null                  $deleted_at
 * @method   static \Database\Factories\QueuedTaskFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask newQuery()
 * @method   static \Illuminate\Database\Query\Builder|QueuedTask    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask query()
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereStatus($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereTaskData($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|QueuedTask whereWorkData($value)
 * @method   static \Illuminate\Database\Query\Builder|QueuedTask    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|QueuedTask    withoutTrashed()
 * @mixin \Eloquent
 */
class QueuedTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'queued_tasks';
}
