<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectHyperlink
 *
 * @property        int                                                    $id
 * @property        int                                                    $project_id
 * @property        string                                                 $user_id
 * @property        string                                                 $url
 * @property        string                                                 $name
 * @property        string|null                                            $description
 * @property        bool|null                                              $is_deleted
 * @property        \Illuminate\Support\Carbon|null                        $created_at
 * @property        \Illuminate\Support\Carbon|null                        $updated_at
 * @property        string|null                                            $type
 * @property        \Illuminate\Support\Carbon|null                        $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectHyperlink    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereUrl($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectHyperlink whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectHyperlink    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectHyperlink    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectHyperlink extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_hyperlinks';
}
