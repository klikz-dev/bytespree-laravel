<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\NotificationType
 *
 * @property        int                                                                            $id
 * @property        string|null                                                                    $name
 * @property        string|null                                                                    $class
 * @property        string|null                                                                    $descriptor_setting
 * @property        bool|null                                                                      $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                $created_at
 * @property        \Illuminate\Support\Carbon|null                                                $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                $deleted_at
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\NotificationTypeSetting[] $settings
 * @property        int|null                                                                       $settings_count
 * @method   static \Database\Factories\NotificationTypeFactory                                    factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         newQuery()
 * @method   static \Illuminate\Database\Query\Builder|NotificationType                            onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         query()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         whereClass($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         whereDescriptorSetting($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationType                         whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|NotificationType                            withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|NotificationType                            withoutTrashed()
 * @mixin \Eloquent
 */
class NotificationType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'notification_types';

    protected $casts = [
        'input_options' => 'json',
    ];

    public function settings()
    {
        return $this->hasMany(NotificationTypeSetting::class, 'type_id');
    }
}
