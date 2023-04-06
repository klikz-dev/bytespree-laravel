<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * App\Models\Explorer\ProjectSnapshot
 *
 * @property        int                                                   $id
 * @property        int|null                                              $project_id
 * @property        string|null                                           $user_id
 * @property        string|null                                           $name
 * @property        string|null                                           $description
 * @property        string|null                                           $source_table
 * @property        string|null                                           $source_schema
 * @property        bool|null                                             $is_deleted
 * @property        \Illuminate\Support\Carbon|null                       $created_at
 * @property        \Illuminate\Support\Carbon|null                       $updated_at
 * @property        \Illuminate\Support\Carbon|null                       $deleted_at
 * @property        User|null                                             $user
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSnapshot    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereSourceSchema($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereSourceTable($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSnapshot whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectSnapshot    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSnapshot    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectSnapshot extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_snapshots';

    protected $with = 'user';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_handle');
    }
}
