<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\MappingModule
 *
 * @property        int                                                 $id
 * @property        string|null                                         $name
 * @property        string|null                                         $code
 * @property        bool|null                                           $is_deleted
 * @property        \Illuminate\Support\Carbon|null                     $created_at
 * @property        \Illuminate\Support\Carbon|null                     $updated_at
 * @property        \Illuminate\Support\Carbon|null                     $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule newQuery()
 * @method   static \Illuminate\Database\Query\Builder|MappingModule    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule query()
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule whereCode($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MappingModule whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|MappingModule    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|MappingModule    withoutTrashed()
 * @mixin \Eloquent
 */
class MappingModule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_mapping_modules';

    public function fields()
    {
        return $this->hasMany(MappingModuleField::class);
    }
}
