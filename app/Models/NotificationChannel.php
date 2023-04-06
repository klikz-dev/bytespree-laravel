<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\NotificationChannel
 *
 * @property        int                                                       $id
 * @property        string|null                                               $name
 * @property        string|null                                               $key
 * @property        bool|null                                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null                           $created_at
 * @property        \Illuminate\Support\Carbon|null                           $updated_at
 * @property        \Illuminate\Support\Carbon|null                           $deleted_at
 * @method   static \Database\Factories\NotificationChannelFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel newQuery()
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannel    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel query()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel whereKey($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannel whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannel    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannel    withoutTrashed()
 * @mixin \Eloquent
 */
class NotificationChannel extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'notification_channels';
}
