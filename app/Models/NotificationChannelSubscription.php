<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

/**
 * App\Models\NotificationChannelSubscription
 *
 * @property        int                                                                                           $id
 * @property        int|null                                                                                      $channel_id
 * @property        int|null                                                                                      $type_id
 * @property        int|null                                                                                      $user_id
 * @property        bool|null                                                                                     $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                               $created_at
 * @property        \Illuminate\Support\Carbon|null                                                               $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                               $deleted_at
 * @property        \App\Models\NotificationChannel|null                                                          $channel
 * @property        \App\Models\NotificationChannelSubscriptionHistory|null                                       $mostRecentHistory
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\NotificationChannelSubscriptionSetting[] $settings
 * @property        int|null                                                                                      $settings_count
 * @property        \App\Models\NotificationType|null                                                             $type
 * @method   static \Database\Factories\NotificationChannelSubscriptionFactory                                    factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         newQuery()
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscription                            onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         query()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         whereChannelId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         whereTypeId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscription                         whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscription                            withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscription                            withoutTrashed()
 * @mixin \Eloquent
 */
class NotificationChannelSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'notification_channel_subscriptions';

    public function channel()
    {
        return $this->belongsTo(NotificationChannel::class, 'channel_id');
    }

    public function type()
    {
        return $this->belongsTo(NotificationType::class, 'type_id');
    }

    public function settings()
    {
        return $this->hasMany(NotificationChannelSubscriptionSetting::class, 'subscription_id');
    }

    public function mostRecentHistory()
    {
        return $this->hasOne(NotificationChannelSubscriptionHistory::class, 'subscription_id')->latest();
    }

    /**
     * Deploy a notification (add it to the history table with a NULL notification_status value)
     *
     * @param  string $channel The channel to deploy to
     * @param  array  $data    The data to send to the notification handler
     * @return bool   TRUE if the notification(s) were deployed (or none exist), FALSE if there was an error deploying the notification(s)
     */
    public static function deploy(string $channel, array $data = []): bool
    {
        $subscriptions = self::whereHas('channel', function ($q) use ($channel) {
            $q->where('key', $channel);
        })->get();
    
        if ($subscriptions->count() == 0) {
            return TRUE;
        }

        foreach ($subscriptions as $subscription) {
            $history = NotificationChannelSubscriptionHistory::create([
                'subscription_id'   => $subscription->id,
                'notification_data' => self::buildNotificationData($channel, $data)
            ]);

            if (! empty($history)) {
                // todo loop back and convert this
                app('jenkins')->launchFunction('systemNotification', ["TEAM" => app('environment')->getTeam(), "SUBSCRIPTION_HISTORY_ID" => $history->id]);
            }
        }

        return TRUE;
    }

    /**
     * Build out a subscription's notification data array
     *
     * @param  string $channel The channel to build the data for
     * @param  array  $data    The data to send to the notification handler
     * @return array  The notification data array
     */
    public static function buildNotificationData(string $channel, array $data): array
    {
        return [
            'bytespree_channel' => $channel,
            'data'              => $data,
        ];
    }
}
