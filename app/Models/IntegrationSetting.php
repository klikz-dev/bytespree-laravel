<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\IntegrationSetting
 *
 * @property        int                                                      $id
 * @property        int|null                                                 $integration_id
 * @property        string|null                                              $name
 * @property        string|null                                              $description
 * @property        bool|null                                                $is_private
 * @property        bool|null                                                $is_secure
 * @property        bool|null                                                $is_required
 * @property        string|null                                              $setting_type
 * @property        bool|null                                                $is_deleted
 * @property        \Illuminate\Support\Carbon|null                          $created_at
 * @property        \Illuminate\Support\Carbon|null                          $updated_at
 * @property        string|null                                              $friendly_name
 * @property        string|null                                              $default_value
 * @property        string|null                                              $data_type
 * @property        string|null                                              $options
 * @property        string|null                                              $required_if
 * @property        string|null                                              $visible_if
 * @property        mixed|null                                               $properties
 * @property        int|null                                                 $ordinal_position
 * @property        \Illuminate\Support\Carbon|null                          $deleted_at
 * @method   static \Database\Factories\IntegrationSettingFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting newQuery()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationSetting    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting query()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereDataType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereDefaultValue($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereFriendlyName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereIsPrivate($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereIsRequired($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereIsSecure($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereOptions($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereOrdinalPosition($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereProperties($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereRequiredIf($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereSettingType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSetting whereVisibleIf($value)
 * @method   static \Illuminate\Database\Query\Builder|IntegrationSetting    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationSetting    withoutTrashed()
 * @mixin \Eloquent
 */
class IntegrationSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_integration_settings';
}
