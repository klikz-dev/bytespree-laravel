<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\IntegrationScheduleType
 *
 * @property        int                                                                                    $id
 * @property        string|null                                                                            $name
 * @property        bool|null                                                                              $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                        $created_at
 * @property        \Illuminate\Support\Carbon|null                                                        $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                        $deleted_at
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\IntegrationScheduleTypeProperty[] $properties
 * @property        int|null                                                                               $properties_count
 * @method   static \Database\Factories\IntegrationScheduleTypeFactory                                     factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          newQuery()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationScheduleType                             onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          query()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationScheduleType                          whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|IntegrationScheduleType                             withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationScheduleType                             withoutTrashed()
 * @mixin \Eloquent
 */
class IntegrationScheduleType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'is_deleted'];

    protected $table = 'di_integration_schedule_types';

    public function properties()
    {
        return $this->hasMany(IntegrationScheduleTypeProperty::class, 'schedule_type_id');
    }
}
