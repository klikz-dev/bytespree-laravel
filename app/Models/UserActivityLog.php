<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\UserActivityLog
 *
 * @property        int                                                   $id
 * @property        string|null                                           $user_handle
 * @property        int|null                                              $project_id
 * @property        string|null                                           $table_name
 * @property        bool|null                                             $is_deleted
 * @property        \Illuminate\Support\Carbon|null                       $created_at
 * @property        \Illuminate\Support\Carbon|null                       $updated_at
 * @property        string|null                                           $schema_name
 * @property        \Illuminate\Support\Carbon|null                       $deleted_at
 * @method   static \Database\Factories\UserActivityLogFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog newQuery()
 * @method   static \Illuminate\Database\Query\Builder|UserActivityLog    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog query()
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereSchemaName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|UserActivityLog whereUserHandle($value)
 * @method   static \Illuminate\Database\Query\Builder|UserActivityLog    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|UserActivityLog    withoutTrashed()
 * @mixin \Eloquent
 */
class UserActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'u_user_activity_log';
}
