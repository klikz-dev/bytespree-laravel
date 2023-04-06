<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Manager\ImportLog
 *
 * @property        int                                             $id
 * @property        int|null                                        $control_id
 * @property        int|null                                        $table_id
 * @property        int|null                                        $user_id
 * @property        string|null                                     $file_name
 * @property        string|null                                     $file_size
 * @property        string|null                                     $table_name
 * @property        string|null                                     $type
 * @property        string|null                                     $status
 * @property        int|null                                        $records_imported
 * @property        int|null                                        $records_in_error
 * @property        array|null                                      $settings
 * @property        array|null                                      $columns
 * @property        array|null                                      $mappings
 * @property        string|null                                     $ip_address
 * @property        bool|null                                       $is_deleted
 * @property        \Illuminate\Support\Carbon|null                 $created_at
 * @property        \Illuminate\Support\Carbon|null                 $updated_at
 * @property        \Illuminate\Support\Carbon|null                 $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ImportLog    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereColumns($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereFileName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereFileSize($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereIpAddress($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereMappings($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereRecordsImported($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereRecordsInError($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereSettings($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereStatus($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereTableId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ImportLog whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ImportLog    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ImportLog    withoutTrashed()
 * @mixin \Eloquent
 */
class ImportLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_import_logs';

    protected $casts = [
        'settings' => 'json',
        'columns'  => 'json',
        'mappings' => 'json',
    ];
}
