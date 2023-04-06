<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Manager\DatabaseHookKey
 *
 * @property        int                                                   $id
 * @property        int|null                                              $partner_integration_id
 * @property        string|null                                           $key
 * @property        bool|null                                             $is_deleted
 * @property        \Illuminate\Support\Carbon|null                       $created_at
 * @property        \Illuminate\Support\Carbon|null                       $updated_at
 * @property        \Illuminate\Support\Carbon|null                       $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey newQuery()
 * @method   static \Illuminate\Database\Query\Builder|DatabaseHookKey    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey query()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey whereKey($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey wherePartnerIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseHookKey whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|DatabaseHookKey    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|DatabaseHookKey    withoutTrashed()
 * @mixin \Eloquent
 */
class DatabaseHookKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_database_hook_keys';
}
