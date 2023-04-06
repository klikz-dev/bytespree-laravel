<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Attachment
 *
 * @property        int                                              $id
 * @property        int                                              $control_id
 * @property        string|null                                      $path
 * @property        string|null                                      $file_name
 * @property        string|null                                      $user_id
 * @property        bool|null                                        $is_deleted
 * @property        \Illuminate\Support\Carbon|null                  $created_at
 * @property        \Illuminate\Support\Carbon|null                  $updated_at
 * @property        \Illuminate\Support\Carbon|null                  $deleted_at
 * @method   static \Database\Factories\AttachmentFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Attachment    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment whereFileName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment wherePath($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Attachment whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|Attachment    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Attachment    withoutTrashed()
 * @mixin \Eloquent
 */
class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_attachments';
}
