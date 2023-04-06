<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\DestinationDatabaseMappingModule
 *
 * @property        int                                                                    $id
 * @property        int|null                                                               $managed_database_id
 * @property        int|null                                                               $mapping_module_id
 * @property        bool|null                                                              $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                        $created_at
 * @property        \Illuminate\Support\Carbon|null                                        $updated_at
 * @property        \Illuminate\Support\Carbon|null                                        $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule newQuery()
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseMappingModule    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule query()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule whereManagedDatabaseId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule whereMappingModuleId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseMappingModule whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseMappingModule    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseMappingModule    withoutTrashed()
 * @mixin \Eloquent
 */
class DestinationDatabaseMappingModule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_destination_database_mapping_modules';
}
