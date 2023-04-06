<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * App\Models\Manager\ViewDefinitionHistory
 *
 * @property        int                                                         $id
 * @property        string|null                                                 $view_history_guid
 * @property        string|null                                                 $view_type
 * @property        string|null                                                 $view_schema
 * @property        string|null                                                 $view_name
 * @property        string|null                                                 $view_definition_sql
 * @property        array|null                                                  $view_definition_json
 * @property        string|null                                                 $view_created_by
 * @property        string|null                                                 $view_created_at
 * @property        string|null                                                 $created_at
 * @property        string|null                                                 $view_message
 * @property        \Illuminate\Support\Carbon|null                             $deleted_at
 * @property        User|null                                                   $user
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ViewDefinitionHistory    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewCreatedBy($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewDefinitionJson($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewDefinitionSql($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewHistoryGuid($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewMessage($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewSchema($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinitionHistory whereViewType($value)
 * @method   static \Illuminate\Database\Query\Builder|ViewDefinitionHistory    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ViewDefinitionHistory    withoutTrashed()
 * @mixin \Eloquent
 */
class ViewDefinitionHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_view_definition_history';

    public $timestamps = FALSE;

    protected $casts = [
        'view_definition_json' => 'json'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'view_created_by', 'user_handle');
    }
}
