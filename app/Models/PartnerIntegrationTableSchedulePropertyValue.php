<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PartnerIntegrationTableSchedulePropertyValue
 *
 * @property        int                                                                                $id
 * @property        int|null                                                                           $schedule_id
 * @property        int|null                                                                           $schedule_type_property_id
 * @property        string|null                                                                        $value
 * @property        bool|null                                                                          $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                    $created_at
 * @property        \Illuminate\Support\Carbon|null                                                    $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                    $deleted_at
 * @property        \App\Models\PartnerIntegrationTableSchedule|null                                   $schedule
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTableSchedulePropertyValue    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue whereScheduleId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue whereScheduleTypePropertyId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedulePropertyValue whereValue($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTableSchedulePropertyValue    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTableSchedulePropertyValue    withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationTableSchedulePropertyValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_table_schedule_property_values';

    public function schedule()
    {
        return $this->belongsTo(PartnerIntegrationTableSchedule::class, 'schedule_id');
    }
}
