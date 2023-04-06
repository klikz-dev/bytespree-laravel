<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\IntegrationSettingType
 *
 * @property        int                                                          $id
 * @property        string|null                                                  $type
 * @property        bool|null                                                    $is_deleted
 * @property        \Illuminate\Support\Carbon|null                              $created_at
 * @property        \Illuminate\Support\Carbon|null                              $updated_at
 * @property        \Illuminate\Support\Carbon|null                              $deleted_at
 * @method   static \Database\Factories\IntegrationSettingTypeFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType newQuery()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationSettingType    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType query()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType whereType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationSettingType whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|IntegrationSettingType    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationSettingType    withoutTrashed()
 * @mixin \Eloquent
 */
class IntegrationSettingType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_integration_setting_types';
}
