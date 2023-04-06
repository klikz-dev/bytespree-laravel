<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PartnerIntegrationSchedule
 *
 * @property        int                                                                                            $id
 * @property        int|null                                                                                       $partner_integration_id
 * @property        int|null                                                                                       $schedule_type_id
 * @property        bool|null                                                                                      $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                                $created_at
 * @property        \Illuminate\Support\Carbon|null                                                                $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                                $deleted_at
 * @property        \App\Models\PartnerIntegration|null                                                            $database
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\PartnerIntegrationSchedulePropertyValue[] $properties
 * @property        int|null                                                                                       $properties_count
 * @property        \App\Models\IntegrationScheduleType|null                                                       $scheduleType
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\PartnerIntegrationSchedulePropertyValue[] $values
 * @property        int|null                                                                                       $values_count
 * @method   static \Database\Factories\PartnerIntegrationScheduleFactory                                          factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSchedule                                  onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               wherePartnerIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               whereScheduleTypeId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSchedule                               whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSchedule                                  withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSchedule                                  withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_schedules';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($schedule) {
            $schedule->properties()->delete();
        });
    }

    public function database()
    {
        return $this->belongsTo(PartnerIntegration::class, 'partner_integration_id');
    }

    public function properties()
    {
        return $this->hasMany(PartnerIntegrationSchedulePropertyValue::class, 'schedule_id');
    }

    public function values()
    {
        return $this->hasMany(PartnerIntegrationSchedulePropertyValue::class, 'schedule_id');
    }

    public function scheduleType()
    {
        return $this->hasOne(IntegrationScheduleType::class, 'id', 'schedule_type_id');
    }
}
