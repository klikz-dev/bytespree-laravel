<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

/**
 * App\Models\Manager\Tag
 *
 * @property        int                                       $id
 * @property        string|null                               $name
 * @property        string|null                               $color
 * @property        bool|null                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null           $created_at
 * @property        \Illuminate\Support\Carbon|null           $updated_at
 * @property        \Illuminate\Support\Carbon|null           $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Tag    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag whereColor($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Tag whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|Tag    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Tag    withoutTrashed()
 * @mixin \Eloquent
 */
class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_tags';

    public static function getDatabaseTags(int $database_id)
    {
        return DB::table('dw_database_tags as bpt')
            ->select('bpt.id', 'bt.name', 'bt.color', 'bpt.tag_id', 'bpt.control_id')
            ->join('dw_tags as bt', 'bt.id', '=', 'bpt.tag_id')
            ->whereNull('bpt.deleted_at')
            ->whereNull('bt.deleted_at')
            ->where('bpt.control_id', $database_id)
            ->get();
    }

    public static function getAllDatabaseTags()
    {
        return DB::table('dw_database_tags as bpt')
            ->select('bpt.id', 'bt.name', 'bt.color', 'bpt.tag_id', 'bpt.control_id')
            ->join('dw_tags as bt', 'bt.id', '=', 'bpt.tag_id')
            ->whereNull('bpt.deleted_at')
            ->whereNull('bt.deleted_at')
            ->get();
    }
}
