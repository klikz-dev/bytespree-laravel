<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PartnerIntegrationTableSchedule
 *
 * @property        int                                                                   $id
 * @property        int|null                                                              $partner_integration_table_id
 * @property        int|null                                                              $schedule_type_id
 * @property        bool|null                                                             $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                       $created_at
 * @property        \Illuminate\Support\Carbon|null                                       $updated_at
 * @property        \Illuminate\Support\Carbon|null                                       $deleted_at
 * @property        \App\Models\PartnerIntegrationTable|null                              $table
 * @method   static \Database\Factories\PartnerIntegrationTableScheduleFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTableSchedule    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule wherePartnerIntegrationTableId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule whereScheduleTypeId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTableSchedule whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTableSchedule    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTableSchedule    withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationTableSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_table_schedules';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($schedule) {
            $schedule->properties()->delete();
        });
    }

    public function properties()
    {
        return $this->hasMany(PartnerIntegrationTableSchedulePropertyValue::class, 'schedule_id')
            ->join('di_integration_schedule_type_properties', 'di_integration_schedule_type_properties.id', '=', 'di_partner_integration_table_schedule_property_values.schedule_type_property_id');
    }

    public function table()
    {
        return $this->belongsTo(PartnerIntegrationTable::class, 'partner_integration_table_id');
    }
}
