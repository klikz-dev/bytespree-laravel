<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PartnerIntegrationSchedulePropertyValue
 *
 * @property        int                                                                           $id
 * @property        int|null                                                                      $schedule_id
 * @property        int|null                                                                      $schedule_type_property_id
 * @property        string|null                                                                   $value
 * @property        bool|null                                                                     $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                               $created_at
 * @property        \Illuminate\Support\Carbon|null                                               $updated_at
 * @property        \Illuminate\Support\Carbon|null                                               $deleted_at
 * @property        \App\Models\PartnerIntegrationSchedule|null                                   $schedule
 * @property        \App\Models\IntegrationScheduleTypeProperty|null                              $typeProperty
 * @method   static \Database\Factories\PartnerIntegrationSchedulePropertyValueFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSchedulePropertyValue    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue whereScheduleId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue whereScheduleTypePropertyId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedulePropertyValue whereValue($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSchedulePropertyValue    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSchedulePropertyValue    withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationSchedulePropertyValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_schedule_property_values';

    public function schedule()
    {
        return $this->belongsTo(PartnerIntegrationSchedule::class, 'schedule_id');
    }

    public function typeProperty()
    {
        return $this->hasOne(IntegrationScheduleTypeProperty::class, 'id', 'schedule_type_property_id');
    }
}
