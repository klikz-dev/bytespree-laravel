<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectUser
 *
 * @property        int                                               $id
 * @property        int|null                                          $project_id
 * @property        int|null                                          $user_id
 * @property        int|null                                          $role_id
 * @property        bool|null                                         $is_deleted
 * @property        \Illuminate\Support\Carbon|null                   $created_at
 * @property        \Illuminate\Support\Carbon|null                   $updated_at
 * @property        \Illuminate\Support\Carbon|null                   $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectUser    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser whereRoleId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectUser whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectUser    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectUser    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_users';
}
