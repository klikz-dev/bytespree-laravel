<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\MappingModuleField
 *
 * @property        int                                                      $id
 * @property        int|null                                                 $mapping_module_id
 * @property        string|null                                              $name
 * @property        string|null                                              $type
 * @property        bool|null                                                $is_required
 * @property        string|null                                              $calculation_script
 * @property        bool|null                                                $is_deleted
 * @property        \Illuminate\Support\Carbon|null                          $created_at
 * @property        \Illuminate\Support\Carbon|null                          $updated_at
 * @property        \Illuminate\Support\Carbon|null                          $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField newQuery()
 * @method   static \Illuminate\Database\Query\Builder|MappingModuleField    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField query()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereCalculationScript($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereIsRequired($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereMappingModuleId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleField whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|MappingModuleField    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|MappingModuleField    withoutTrashed()
 * @mixin \Eloquent
 */
class MappingModuleField extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_mapping_module_fields';

    public function module()
    {
        return $this->belongsTo(MappingModule::class);
    }

    public function value(int $mapping_id)
    {
        $value = MappingModuleFieldValue::where('mapping_id', $mapping_id)
            ->where('mapping_module_field_id', $this->id)
            ->first();

        if (! empty($value)) {
            return $value->value;
        }

        return "";
    }
}
