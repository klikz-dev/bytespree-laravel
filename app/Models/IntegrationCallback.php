<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\IntegrationCallback
 *
 * @property        int                                                       $id
 * @property        int|null                                                  $control_id
 * @property        string|null                                               $name
 * @property        string|null                                               $key
 * @property        string|null                                               $callback_url
 * @property        bool|null                                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null                           $created_at
 * @property        \Illuminate\Support\Carbon|null                           $updated_at
 * @property        \Illuminate\Support\Carbon|null                           $deleted_at
 * @method   static \Database\Factories\IntegrationCallbackFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback newQuery()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationCallback    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback query()
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereCallbackUrl($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereKey($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|IntegrationCallback whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|IntegrationCallback    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|IntegrationCallback    withoutTrashed()
 * @mixin \Eloquent
 */
class IntegrationCallback extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_integration_callbacks';
}
