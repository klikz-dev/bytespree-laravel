<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Manager\DatabaseTag
 *
 * @property        int                                               $id
 * @property        int                                               $tag_id
 * @property        int                                               $control_id
 * @property        bool|null                                         $is_deleted
 * @property        \Illuminate\Support\Carbon|null                   $created_at
 * @property        \Illuminate\Support\Carbon|null                   $updated_at
 * @property        \Illuminate\Support\Carbon|null                   $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag newQuery()
 * @method   static \Illuminate\Database\Query\Builder|DatabaseTag    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag query()
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag whereTagId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|DatabaseTag whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|DatabaseTag    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|DatabaseTag    withoutTrashed()
 * @mixin \Eloquent
 */
class DatabaseTag extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_database_tags';
}
