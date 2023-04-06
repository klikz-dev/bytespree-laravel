<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Manager\ImportedTable
 *
 * @property        int                                                 $id
 * @property        int|null                                            $control_id
 * @property        string|null                                         $user_id
 * @property        string|null                                         $table_name
 * @property        bool|null                                           $is_deleted
 * @property        \Illuminate\Support\Carbon|null                     $created_at
 * @property        \Illuminate\Support\Carbon|null                     $updated_at
 * @property        \Illuminate\Support\Carbon|null                     $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ImportedTable    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable whereTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportedTable whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ImportedTable    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ImportedTable    withoutTrashed()
 * @mixin \Eloquent
 */
class ImportedTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_imported_tables';
}
