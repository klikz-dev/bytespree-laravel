<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\MssqlPublisherScope;
use App\Casts\OldCryptJson;

/**
 * App\Models\Explorer\MicrosoftSqlServer
 *
 * @property        int                                                      $id
 * @property        string|null                                              $name
 * @property        int                                                      $destination_id
 * @property        mixed|null                                               $data
 * @property        bool|null                                                $is_deleted
 * @property        \Illuminate\Support\Carbon|null                          $created_at
 * @property        \Illuminate\Support\Carbon|null                          $updated_at
 * @property        \Illuminate\Support\Carbon|null                          $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer newQuery()
 * @method   static \Illuminate\Database\Query\Builder|MicrosoftSqlServer    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer query()
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer whereData($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer whereDestinationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|MicrosoftSqlServer whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|MicrosoftSqlServer    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|MicrosoftSqlServer    withoutTrashed()
 * @mixin \Eloquent
 */
class MicrosoftSqlServer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_publishers';

    protected $casts = [
        'data' => OldCryptJson::class
    ];

    protected static function booted()
    {
        static::addGlobalScope(new MssqlPublisherScope);
    }
    
    public static function create(array $attributes = [])
    {
        $destination = PublishingDestination::where('class_name', 'Mssql')->first();
        
        $record = [
            "destination_id" => $destination->id,
            "data"           => $attributes
        ];

        return static::query()->create($record);
    }
}
