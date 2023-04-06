<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\Json;

/**
 * App\Models\Manager\ViewSchedule
 *
 * @property        int                                                $id
 * @property        int                                                $control_id
 * @property        string|null                                        $view_name
 * @property        string|null                                        $frequency
 * @property        array|null                                         $schedule
 * @property        bool|null                                          $is_deleted
 * @property        \Illuminate\Support\Carbon|null                    $created_at
 * @property        \Illuminate\Support\Carbon|null                    $updated_at
 * @property        string|null                                        $view_schema
 * @property        \Illuminate\Support\Carbon|null                    $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ViewSchedule    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereFrequency($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereSchedule($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereViewName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewSchedule whereViewSchema($value)
 * @method   static \Illuminate\Database\Query\Builder|ViewSchedule    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ViewSchedule    withoutTrashed()
 * @mixin \Eloquent
 */
class ViewSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_view_schedules';

    protected $casts = [
        'schedule' => 'json'
    ];
}
