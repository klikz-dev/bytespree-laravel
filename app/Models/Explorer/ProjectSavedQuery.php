<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectSavedQuery
 *
 * @property        int                                                     $id
 * @property        int|null                                                $project_id
 * @property        string|null                                             $user_id
 * @property        string|null                                             $name
 * @property        string|null                                             $description
 * @property        array|null                                              $query
 * @property        string|null                                             $source_table
 * @property        string|null                                             $source_schema
 * @property        bool|null                                               $is_deleted
 * @property        \Illuminate\Support\Carbon|null                         $created_at
 * @property        \Illuminate\Support\Carbon|null                         $updated_at
 * @property        \Illuminate\Support\Carbon|null                         $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSavedQuery    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereQuery($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereSourceSchema($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereSourceTable($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSavedQuery whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectSavedQuery    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSavedQuery    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectSavedQuery extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_saved_queries';

    protected $casts = [
        'query' => 'json',
    ];
}
