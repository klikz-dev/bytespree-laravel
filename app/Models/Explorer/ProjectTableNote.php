<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * App\Models\Explorer\ProjectTableNote
 *
 * @property        int                                                    $id
 * @property        int                                                    $project_id
 * @property        string|null                                            $table
 * @property        string|null                                            $note
 * @property        bool|null                                              $is_deleted
 * @property        \Illuminate\Support\Carbon|null                        $created_at
 * @property        \Illuminate\Support\Carbon|null                        $updated_at
 * @property        string|null                                            $user_id
 * @property        string|null                                            $schema
 * @property        \Illuminate\Support\Carbon|null                        $deleted_at
 * @property        User|null                                              $user
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectTableNote    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereNote($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereSchema($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereTable($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNote whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectTableNote    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectTableNote    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectTableNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_table_notes';

    protected $with = 'user';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_handle');
    }
}
