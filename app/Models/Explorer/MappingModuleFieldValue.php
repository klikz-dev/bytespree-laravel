<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\MappingModuleFieldValue
 *
 * @property        int                                                           $id
 * @property        int|null                                                      $mapping_id
 * @property        int|null                                                      $mapping_module_field_id
 * @property        string|null                                                   $value
 * @property        bool|null                                                     $is_deleted
 * @property        \Illuminate\Support\Carbon|null                               $created_at
 * @property        \Illuminate\Support\Carbon|null                               $updated_at
 * @property        \Illuminate\Support\Carbon|null                               $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue newQuery()
 * @method   static \Illuminate\Database\Query\Builder|MappingModuleFieldValue    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue query()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue whereMappingId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue whereMappingModuleFieldId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModuleFieldValue whereValue($value)
 * @method   static \Illuminate\Database\Query\Builder|MappingModuleFieldValue    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|MappingModuleFieldValue    withoutTrashed()
 * @mixin \Eloquent
 */
class MappingModuleFieldValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_mapping_module_field_values';
    
    public function mapping()
    {
        return $this->belongsTo(ProjectColumnMapping::class);
    }
    
    public function field()
    {
        return $this->belongsTo(MappingModuleField::class);
    }
}
