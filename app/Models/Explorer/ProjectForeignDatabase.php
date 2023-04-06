<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectForeignDatabase
 *
 * @property        int                                                          $id
 * @property        int                                                          $project_id
 * @property        int|null                                                     $foreign_database_id
 * @property        bool|null                                                    $is_deleted
 * @property        \Illuminate\Support\Carbon|null                              $created_at
 * @property        \Illuminate\Support\Carbon|null                              $updated_at
 * @property        \Illuminate\Support\Carbon|null                              $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectForeignDatabase    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase whereForeignDatabaseId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectForeignDatabase whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectForeignDatabase    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectForeignDatabase    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectForeignDatabase extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_foreign_databases';

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
