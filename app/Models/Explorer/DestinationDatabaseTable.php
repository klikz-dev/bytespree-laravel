<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\DestinationDatabaseTable
 *
 * @property        int                                                                                            $id
 * @property        int|null                                                                                       $managed_database_id
 * @property        string|null                                                                                    $name
 * @property        bool|null                                                                                      $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                                $created_at
 * @property        \Illuminate\Support\Carbon|null                                                                $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                                $deleted_at
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\Explorer\DestinationDatabaseTableColumn[] $columns
 * @property        int|null                                                                                       $columns_count
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 newQuery()
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseTable                                    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 query()
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 whereManagedDatabaseId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DestinationDatabaseTable                                 whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseTable                                    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|DestinationDatabaseTable                                    withoutTrashed()
 * @mixin \Eloquent
 */
class DestinationDatabaseTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_destination_database_tables';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($destination_database_table) {
            DestinationDatabaseTableColumn::where('managed_database_table_id', $destination_database_table->id)->delete();
        });
    }

    public function columns()
    {
        return $this->hasMany(DestinationDatabaseTableColumn::class, 'managed_database_table_id');
    }
}
