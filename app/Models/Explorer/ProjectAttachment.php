<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectAttachment
 *
 * @property        int                                                     $id
 * @property        int                                                     $project_id
 * @property        string|null                                             $path
 * @property        string|null                                             $file_name
 * @property        string|null                                             $user_id
 * @property        bool|null                                               $is_deleted
 * @property        \Illuminate\Support\Carbon|null                         $created_at
 * @property        \Illuminate\Support\Carbon|null                         $updated_at
 * @property        \Illuminate\Support\Carbon|null                         $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectAttachment    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereFileName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment wherePath($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectAttachment    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectAttachment    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_attachments';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if (file_exists($attachment->path)) {
                unlink($attachment->path);
            }
        });
    }
}
