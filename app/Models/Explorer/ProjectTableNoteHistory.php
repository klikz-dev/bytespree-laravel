<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Explorer\ProjectTableNoteHistory
 *
 * @property        int                                                           $id
 * @property        int                                                           $note_id
 * @property        string                                                        $action
 * @property        int                                                           $project_id
 * @property        string|null                                                   $table
 * @property        string|null                                                   $note
 * @property        string|null                                                   $user_id
 * @property        \Illuminate\Support\Carbon|null                               $created_at
 * @property        \Illuminate\Support\Carbon|null                               $updated_at
 * @property        bool|null                                                     $is_deleted
 * @property        string|null                                                   $schema
 * @property        \Illuminate\Support\Carbon|null                               $deleted_at
 * @method   static \Database\Factories\Explorer\ProjectTableNoteHistoryFactory   factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectTableNoteHistory    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereAction($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereNote($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereNoteId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereSchema($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereTable($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectTableNoteHistory whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectTableNoteHistory    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectTableNoteHistory    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectTableNoteHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_table_notes_history';
}
