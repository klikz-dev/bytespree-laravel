<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\EventType
 *
 * @property        int                                             $id
 * @property        string|null                                     $name
 * @property        string|null                                     $description
 * @property        bool|null                                       $is_deleted
 * @property        \Illuminate\Support\Carbon|null                 $created_at
 * @property        \Illuminate\Support\Carbon|null                 $updated_at
 * @property        \Illuminate\Support\Carbon|null                 $deleted_at
 * @method   static \Database\Factories\EventTypeFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType newQuery()
 * @method   static \Illuminate\Database\Query\Builder|EventType    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType query()
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|EventType whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|EventType    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|EventType    withoutTrashed()
 * @mixin \Eloquent
 */
class EventType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_event_types';
}
