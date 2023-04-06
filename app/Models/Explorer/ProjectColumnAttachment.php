<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectColumnAttachment
 *
 * @property        int                                                           $id
 * @property        int                                                           $project_id
 * @property        string|null                                                   $table_name
 * @property        string|null                                                   $column_name
 * @property        string|null                                                   $path
 * @property        string|null                                                   $file_name
 * @property        string|null                                                   $user_id
 * @property        bool|null                                                     $is_deleted
 * @property        \Illuminate\Support\Carbon|null                               $created_at
 * @property        \Illuminate\Support\Carbon|null                               $updated_at
 * @property        string|null                                                   $schema_name
 * @property        \Illuminate\Support\Carbon|null                               $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnAttachment    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereColumnName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereFileName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment wherePath($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereSchemaName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnAttachment whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnAttachment    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnAttachment    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectColumnAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_column_attachments';

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
