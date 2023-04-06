<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\OldCrypt;

/**
 * App\Models\NotificationChannelSubscriptionSetting
 *
 * @property        int                                                                          $id
 * @property        int|null                                                                     $subscription_id
 * @property        int|null                                                                     $setting_id
 * @property        int|null                                                                     $user_id
 * @property        mixed|null                                                                   $value
 * @property        bool|null                                                                    $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                              $created_at
 * @property        \Illuminate\Support\Carbon|null                                              $updated_at
 * @property        \Illuminate\Support\Carbon|null                                              $deleted_at
 * @property        \App\Models\NotificationTypeSetting|null                                     $setting
 * @method   static \Database\Factories\NotificationChannelSubscriptionSettingFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting newQuery()
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscriptionSetting    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting query()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereSettingId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereSubscriptionId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereUserId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationChannelSubscriptionSetting whereValue($value)
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscriptionSetting    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|NotificationChannelSubscriptionSetting    withoutTrashed()
 * @mixin \Eloquent
 */
class NotificationChannelSubscriptionSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'notification_channel_subscription_settings';

    protected $casts = [
        'value' => OldCrypt::class,
    ];

    public function setting()
    {
        return $this->belongsTo(NotificationTypeSetting::class, 'setting_id');
    }
}
