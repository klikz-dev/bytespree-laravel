<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectColumnMappingCondition
 *
 * @property        int                                                                 $id
 * @property        int|null                                                            $project_column_mapping_id
 * @property        string|null                                                         $condition
 * @property        bool|null                                                           $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                     $created_at
 * @property        \Illuminate\Support\Carbon|null                                     $updated_at
 * @property        \Illuminate\Support\Carbon|null                                     $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnMappingCondition    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition whereCondition($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition whereProjectColumnMappingId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMappingCondition whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnMappingCondition    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnMappingCondition    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectColumnMappingCondition extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_column_mapping_conditions';
}
