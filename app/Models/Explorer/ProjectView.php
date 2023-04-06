<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Manager\ViewDefinitionHistory;
use App\Models\Explorer\ProjectPublishingSchedule;
use App\Models\Explorer\ProjectTableNote;
use DB;

/**
 * App\Models\Explorer\ProjectView
 *
 * @property        int                                               $id
 * @property        int                                               $project_id
 * @property        string|null                                       $view_name
 * @property        string|null                                       $last_created_at
 * @property        string|null                                       $user_id
 * @property        bool|null                                         $is_deleted
 * @property        \Illuminate\Support\Carbon|null                   $created_at
 * @property        \Illuminate\Support\Carbon|null                   $updated_at
 * @property        object|null                                       $view_definition
 * @property        string|null                                       $view_type
 * @property        string|null                                       $view_schema
 * @property        string|null                                       $view_history_guid
 * @property        string|null                                       $view_definition_sql
 * @property        string|null                                       $jenkins_build_id
 * @property        string|null                                       $view_message
 * @property        \Illuminate\Support\Carbon|null                   $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectView    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereJenkinsBuildId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereLastCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereUserId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereViewDefinition($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereViewDefinitionSql($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereViewHistoryGuid($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereViewMessage($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereViewName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereViewSchema($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectView whereViewType($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectView    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectView    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectView extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_views';

    protected $casts = [
        'view_definition' => 'object',
    ];

    public static function boot()
    {
        parent::boot();

        static::updating(function ($view) {
            $old_view = (object) $view->getOriginal();

            if ($old_view->view_name != $view->view_name) {
                ProjectTableNote::where('project_id', $old_view->project_id)
                    ->where('table', $old_view->view_name)
                    ->update([
                        'table' => $view->view_name
                    ]);
            }

            ViewDefinitionHistory::create([
                'view_history_guid'    => $old_view->view_history_guid,
                'view_type'            => $old_view->view_type,
                'view_schema'          => $old_view->view_schema,
                'view_name'            => $old_view->view_name,
                'view_message'         => $old_view->view_message,
                'view_definition_sql'  => $old_view->view_definition_sql,
                'view_definition_json' => $old_view->view_definition,
                'view_created_by'      => $old_view->user_id,
                'view_created_at'      => $old_view->created_at,
                'created_at'           => DB::raw('now()')
            ]);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_handle');
    }

    public function publisher()
    {
        $destination = PublishingDestination::className('View');

        return ProjectPublishingSchedule::where('publisher_id', $this->id)
            ->where('destination_id', $destination->id)
            ->first();
    }
}
