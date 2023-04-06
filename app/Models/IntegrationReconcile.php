<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\IntegrationReconcile
 *
 * @property        int                                                        $dmi_id
 * @property        int|null                                                   $control_id
 * @property        string|null                                                $table_name
 * @property        int|null                                                   $difference
 * @property        int|null                                                   $shadow_count
 * @property        int|null                                                   $api_count
 * @property        string|null                                                $date_started
 * @property        string|null                                                $dmi_created
 * @property        string|null                                                $integration_name
 * @property        bool|null                                                  $is_deleted
 * @property        \Illuminate\Support\Carbon|null                            $deleted_at
 * @method   static \Database\Factories\IntegrationReconcileFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile newQuery()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationReconcile    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile query()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereApiCount($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereDateStarted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereDifference($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereDmiCreated($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereDmiId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereIntegrationName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereShadowCount($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationReconcile whereTableName($value)
 * @method   static \Illuminate\Database\Query\Builder|IntegrationReconcile    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationReconcile    withoutTrashed()
 * @mixin \Eloquent
 */
class IntegrationReconcile extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'integration_reconcile';
}
