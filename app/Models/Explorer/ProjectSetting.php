<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectSetting
 *
 * @property        int                                                  $id
 * @property        string|null                                          $name
 * @property        string|null                                          $label
 * @property        string|null                                          $type
 * @property        bool|null                                            $is_deleted
 * @property        \Illuminate\Support\Carbon|null                      $created_at
 * @property        \Illuminate\Support\Carbon|null                      $updated_at
 * @property        \Illuminate\Support\Carbon|null                      $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSetting    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereLabel($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectSetting whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectSetting    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectSetting    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_settings';
}
