<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Casts\OldCryptJson;

/**
 * App\Models\SavedData
 *
 * @property        int                                             $id
 * @property        string                                          $guid
 * @property        mixed|null                                      $data
 * @property        string|null                                     $controller
 * @property        bool|null                                       $is_deleted
 * @property        \Illuminate\Support\Carbon|null                 $created_at
 * @property        \Illuminate\Support\Carbon|null                 $updated_at
 * @property        \Illuminate\Support\Carbon|null                 $deleted_at
 * @method   static \Database\Factories\SavedDataFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData newQuery()
 * @method   static \Illuminate\Database\Query\Builder|SavedData    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData query()
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData whereController($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData whereData($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData whereGuid($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|SavedData whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|SavedData    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|SavedData    withoutTrashed()
 * @mixin \Eloquent
 */
class SavedData extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_saved_data';

    protected $casts = [
        'data' => OldCryptJson::class,
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($saved_data) {
            $saved_data->guid = (string) Str::uuid();
        });
    }
}
