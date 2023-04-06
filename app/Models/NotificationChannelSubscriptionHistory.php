<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\NotificationChannelSubscriptionHistory
 *
 * @property        int                                                                          $id
 * @property        int|null                                                                     $subscription_id
 * @property        array|null                                                                   $notification_data
 * @property        bool|null                                                                    $notification_status
 * @property        string|null                                                                  $notification_status_code
 * @property        string|null                                                                  $notification_status_message
 * @property        string|null                                                                  $notification_uuid
 * @property        string|null                                                                  $related_build_number
 * @property        bool|null                                                                    $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                              $created_at
 * @property        \Illuminate\Support\Carbon|null                                              $updated_at
 * @property        \Illuminate\Support\Carbon|null                                              $deleted_at
 * @method   static \Database\Factories\NotificationChannelSubscriptionHistoryFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory newQuery()
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscriptionHistory    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory query()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereNotificationData($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereNotificationStatus($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereNotificationStatusCode($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereNotificationStatusMessage($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereNotificationUuid($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereRelatedBuildNumber($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereSubscriptionId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionHistory whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscriptionHistory    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscriptionHistory    withoutTrashed()
 * @mixin \Eloquent
 */
class NotificationChannelSubscriptionHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'notification_channel_subscription_history';

    protected $casts = [
        'notification_data' => 'json',
    ];
}
