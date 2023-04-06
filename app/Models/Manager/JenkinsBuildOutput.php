<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Manager\JenkinsBuildOutput
 *
 * @property        int                                                      $id
 * @property        int|null                                                 $build_id
 * @property        string|null                                              $console_text
 * @property        bool|null                                                $is_compressed
 * @property        bool|null                                                $is_deleted
 * @property        \Illuminate\Support\Carbon|null                          $created_at
 * @property        \Illuminate\Support\Carbon|null                          $updated_at
 * @property        \Illuminate\Support\Carbon|null                          $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput newQuery()
 * @method   static \Illuminate\Database\Query\Builder|JenkinsBuildOutput    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput query()
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput whereBuildId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput whereConsoleText($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput whereIsCompressed($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuildOutput whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|JenkinsBuildOutput    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|JenkinsBuildOutput    withoutTrashed()
 * @mixin \Eloquent
 */
class JenkinsBuildOutput extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_jenkins_build_output';
}
