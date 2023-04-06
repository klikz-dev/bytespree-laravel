<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * App\Models\Explorer\ProjectColumnFlag
 *
 * @property        int                                                     $id
 * @property        int                                                     $project_id
 * @property        string                                                  $user_id
 * @property        string                                                  $table_name
 * @property        string                                                  $column_name
 * @property        string|null                                             $assigned_user
 * @property        bool|null                                               $is_deleted
 * @property        \Illuminate\Support\Carbon|null                         $created_at
 * @property        \Illuminate\Support\Carbon|null                         $updated_at
 * @property        string|null                                             $flag_reason
 * @property        string|null                                             $schema_name
 * @property        \Illuminate\Support\Carbon|null                         $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnFlag    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereAssignedUser($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereColumnName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereFlagReason($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereSchemaName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnFlag whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnFlag    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnFlag    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectColumnFlag extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_column_flags';

    public function user()
    {
        return $this->belongsTo(User::class, 'assigned_user', 'user_handle');
    }
}
