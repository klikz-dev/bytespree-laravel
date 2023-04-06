<?php

namespace App\Models\Manager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

/**
 * App\Models\Manager\ViewDefinition
 *
 * @property        int                                                  $id
 * @property        int|null                                             $partner_integration_id
 * @property        string|null                                          $view_history_guid
 * @property        string|null                                          $view_type
 * @property        string|null                                          $view_schema
 * @property        string|null                                          $view_name
 * @property        string|null                                          $view_definition_sql
 * @property        string|null                                          $created_by
 * @property        string|null                                          $updated_by
 * @property        bool|null                                            $is_deleted
 * @property        \Illuminate\Support\Carbon|null                      $created_at
 * @property        \Illuminate\Support\Carbon|null                      $updated_at
 * @property        string|null                                          $build_on
 * @property        int|null                                             $upstream_build_id
 * @property        \Illuminate\Support\Carbon|null                      $deleted_at
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ViewDefinition    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereBuildOn($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereCreatedBy($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition wherePartnerIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereUpdatedBy($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereUpstreamBuildId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereViewDefinitionSql($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereViewHistoryGuid($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereViewName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereViewSchema($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ViewDefinition whereViewType($value)
 * @method   static \Illuminate\Database\Query\Builder|ViewDefinition    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ViewDefinition    withoutTrashed()
 * @mixin \Eloquent
 */
class ViewDefinition extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_view_definitions';

    public static function boot()
    {
        parent::boot();

        static::updating(function ($view) {
            if ($view->isDirty()) {
                ViewDefinitionHistory::create([
                    'view_history_guid'   => $view->getOriginal('view_history_guid'),
                    'view_type'           => $view->getOriginal('view_type'),
                    'view_schema'         => $view->getOriginal('view_schema'),
                    'view_name'           => $view->getOriginal('view_name'),
                    'view_definition_sql' => $view->getOriginal('view_definition_sql'),
                    'view_user_sql'       => $view->getOriginal('view_user_sql'),
                    'view_created_by'     => $view->getOriginal('created_by'),
                    'view_created_at'     => $view->getOriginal('created_at'),
                    'created_at'          => DB::raw('NOW()'),
                    'updated_at'          => $view->getOriginal('updated_at'),
                ]);
            }
        });
    }

    public function schedule()
    {
        return $this->hasOne(ViewSchedule::class, 'view_name', 'view_name')
            ->where('view_schema', $this->view_schema)
            ->where('control_id', $this->partner_integration_id);
    }

    public function downstreamBuilds()
    {
        return $this->hasMany(ViewDefinition::class, 'upstream_build_id', 'id');
    }
}
