<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectSettingValue
 *
 * @property        int                                                       $id
 * @property        int|null                                                  $setting_id
 * @property        int|null                                                  $project_id
 * @property        string|null                                               $value
 * @property        bool|null                                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null                           $created_at
 * @property        \Illuminate\Support\Carbon|null                           $updated_at
 * @property        \Illuminate\Support\Carbon|null                           $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSettingValue    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue whereSettingId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSettingValue whereValue($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectSettingValue    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSettingValue    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectSettingValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_setting_values';
}
