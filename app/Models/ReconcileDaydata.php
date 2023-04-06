<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ReconcileDaydata
 *
 * @property        int                                                    $dmi_id
 * @property        int|null                                               $reconcile_id
 * @property        string|null                                            $date
 * @property        int|null                                               $api_count
 * @property        int|null                                               $shadow_count
 * @property        int|null                                               $difference
 * @property        string|null                                            $dmi_created
 * @property        bool|null                                              $is_deleted
 * @property        \Illuminate\Support\Carbon|null                        $deleted_at
 * @method   static \Database\Factories\ReconcileDaydataFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ReconcileDaydata    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereApiCount($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereDate($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereDifference($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereDmiCreated($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereDmiId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereReconcileId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ReconcileDaydata whereShadowCount($value)
 * @method   static \Illuminate\Database\Query\Builder|ReconcileDaydata    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ReconcileDaydata    withoutTrashed()
 * @mixin \Eloquent
 */
class ReconcileDaydata extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'reconcile_daydata';
}
