<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\OldCryptJson;

/**
 * App\Models\Explorer\Publisher
 *
 * @property        int                                             $id
 * @property        string|null                                     $name
 * @property        int                                             $destination_id
 * @property        mixed|null                                      $data
 * @property        bool|null                                       $is_deleted
 * @property        \Illuminate\Support\Carbon|null                 $created_at
 * @property        \Illuminate\Support\Carbon|null                 $updated_at
 * @property        \Illuminate\Support\Carbon|null                 $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Publisher    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher whereData($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher whereDestinationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Publisher whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|Publisher    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Publisher    withoutTrashed()
 * @mixin \Eloquent
 */
class Publisher extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_publishers';

    protected $casts = [
        'data' => OldCryptJson::class,
    ];
}
