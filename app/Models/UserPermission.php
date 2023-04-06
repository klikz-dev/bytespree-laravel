<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\UserPermission
 *
 * @property        int                                                  $id
 * @property        int|null                                             $permission_id
 * @property        int|null                                             $user_id
 * @property        bool|null                                            $is_deleted
 * @property        \Illuminate\Support\Carbon|null                      $created_at
 * @property        \Illuminate\Support\Carbon|null                      $updated_at
 * @property        \Illuminate\Support\Carbon|null                      $deleted_at
 * @method   static \Database\Factories\UserPermissionFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission newQuery()
 * @method   static \Illuminate\Database\Query\Builder|UserPermission    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission query()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission wherePermissionId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserPermission whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|UserPermission    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|UserPermission    withoutTrashed()
 * @mixin \Eloquent
 */
class UserPermission extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'u_user_permissions';
}
