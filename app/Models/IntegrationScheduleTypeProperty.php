<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\IntegrationScheduleTypeProperty
 *
 * @property        int                                                                   $id
 * @property        int|null                                                              $schedule_type_id
 * @property        string|null                                                           $name
 * @property        array|null                                                            $options
 * @property        bool|null                                                             $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                       $created_at
 * @property        \Illuminate\Support\Carbon|null                                       $updated_at
 * @property        \Illuminate\Support\Carbon|null                                       $deleted_at
 * @method   static \Database\Factories\IntegrationScheduleTypePropertyFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty newQuery()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationScheduleTypeProperty    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty query()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty whereOptions($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty whereScheduleTypeId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleTypeProperty whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|IntegrationScheduleTypeProperty    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationScheduleTypeProperty    withoutTrashed()
 * @mixin \Eloquent
 */
class IntegrationScheduleTypeProperty extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_integration_schedule_type_properties';

    protected $casts = [
        'options' => 'json',
    ];
}
