<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\EventMetadata
 *
 * @property        int                                                 $id
 * @property        int|null                                            $infrastructure_event_id
 * @property        array|null                                          $data
 * @property        bool|null                                           $is_deleted
 * @property        \Illuminate\Support\Carbon|null                     $created_at
 * @property        \Illuminate\Support\Carbon|null                     $updated_at
 * @property        \Illuminate\Support\Carbon|null                     $deleted_at
 * @method   static \Database\Factories\EventMetadataFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata newQuery()
 * @method   static \Illuminate\Database\Query\Builder|EventMetadata    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata query()
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata whereData($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata whereInfrastructureEventId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventMetadata whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|EventMetadata    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|EventMetadata    withoutTrashed()
 * @mixin \Eloquent
 */
class EventMetadata extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_event_metadata';

    protected $casts = [
        'data' => 'json',
    ];
}
