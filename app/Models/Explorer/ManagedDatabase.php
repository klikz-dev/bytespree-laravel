<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ManagedDatabase
 *
 * @property        int                                                   $id
 * @property        string                                                $name
 * @property        bool|null                                             $is_deleted
 * @property        int|null                                              $cloned_from
 * @property        \Illuminate\Support\Carbon|null                       $created_at
 * @property        \Illuminate\Support\Carbon|null                       $updated_at
 * @property        \Illuminate\Support\Carbon|null                       $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ManagedDatabase    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase whereClonedFrom($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ManagedDatabase whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ManagedDatabase    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ManagedDatabase    withoutTrashed()
 * @mixin \Eloquent
 */
class ManagedDatabase extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_managed_databases';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($managed_database) {
            DestinationDatabaseTable::where('managed_database_id', $managed_database->id)->delete();
        });
    }
}
