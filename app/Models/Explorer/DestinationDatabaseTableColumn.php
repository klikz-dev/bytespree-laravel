<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\DestinationDatabaseTableColumn
 *
 * @property        int                                                                  $id
 * @property        string|null                                                          $name
 * @property        string|null                                                          $type
 * @property        int|null                                                             $managed_database_table_id
 * @property        int|null                                                             $length
 * @property        int|null                                                             $precision
 * @property        bool|null                                                            $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                      $created_at
 * @property        \Illuminate\Support\Carbon|null                                      $updated_at
 * @property        \Illuminate\Support\Carbon|null                                      $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn newQuery()
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseTableColumn    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn query()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereLength($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereManagedDatabaseTableId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn wherePrecision($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTableColumn whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseTableColumn    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseTableColumn    withoutTrashed()
 * @mixin \Eloquent
 */
class DestinationDatabaseTableColumn extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_destination_database_table_columns';
}
