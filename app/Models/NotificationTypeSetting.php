<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\NotificationTypeSetting
 *
 * @property        int                                                           $id
 * @property        int|null                                                      $type_id
 * @property        string|null                                                   $key
 * @property        string|null                                                   $name
 * @property        int|null                                                      $sort_order
 * @property        bool|null                                                     $is_secure
 * @property        bool|null                                                     $is_required
 * @property        string|null                                                   $input_validation
 * @property        string|null                                                   $input_placeholder
 * @property        string|null                                                   $input_type
 * @property        array|null                                                    $input_options
 * @property        string|null                                                   $input_default
 * @property        string|null                                                   $input_description
 * @property        bool|null                                                     $is_deleted
 * @property        \Illuminate\Support\Carbon|null                               $created_at
 * @property        \Illuminate\Support\Carbon|null                               $updated_at
 * @property        \Illuminate\Support\Carbon|null                               $deleted_at
 * @method   static \Database\Factories\NotificationTypeSettingFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting newQuery()
 * @method   static \Illuminate\Database\Query\Builder|NotificationTypeSetting    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting query()
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereInputDefault($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereInputDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereInputOptions($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereInputPlaceholder($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereInputType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereInputValidation($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereIsRequired($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereIsSecure($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereKey($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereSortOrder($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereTypeId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|NotificationTypeSetting whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|NotificationTypeSetting    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|NotificationTypeSetting    withoutTrashed()
 * @mixin \Eloquent
 */
class NotificationTypeSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'notification_type_settings';

    protected $casts = [
        'input_options' => 'json',
    ];
}
