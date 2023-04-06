<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\DatabaseLog
 *
 * @property        int                                               $id
 * @property        int|null                                          $user_id
 * @property        string|null                                       $table_name
 * @property        array|null                                        $old_data
 * @property        array|null                                        $new_data
 * @property        \Illuminate\Support\Carbon|null                   $deleted_at
 * @method   static \Database\Factories\DatabaseLogFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog newQuery()
 * @method   static \Illuminate\Database\Query\Builder|DatabaseLog    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog query()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog whereNewData($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog whereOldData($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog whereTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseLog whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|DatabaseLog    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|DatabaseLog    withoutTrashed()
 * @mixin \Eloquent
 */
class DatabaseLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'database_logs';

    protected $casts = [
        'old_data' => 'json',
        'new_data' => 'json',
    ];

    public $timestamps = FALSE;
}
