<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

/**
 * App\Models\PartnerIntegrationTable
 *
 * @property        int                                                           $id
 * @property        int|null                                                      $partner_integration_id
 * @property        bool|null                                                     $is_active
 * @property        bool|null                                                     $is_running
 * @property        string|null                                                   $name
 * @property        string|null                                                   $last_started
 * @property        string|null                                                   $last_finished
 * @property        int|null                                                      $last_rows_inserted
 * @property        int|null                                                      $last_run_count
 * @property        int|null                                                      $origin_total_rows
 * @property        int|null                                                      $clone_total_rows
 * @property        bool|null                                                     $is_deleted
 * @property        \Illuminate\Support\Carbon|null                               $created_at
 * @property        \Illuminate\Support\Carbon|null                               $updated_at
 * @property        string|null                                                   $minimum_sync_date
 * @property        \Illuminate\Support\Carbon|null                               $deleted_at
 * @property        \App\Models\PartnerIntegration|null                           $database
 * @property        \App\Models\PartnerIntegrationTableSchedule|null              $schedule
 * @method   static \Database\Factories\PartnerIntegrationTableFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTable    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereCloneTotalRows($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereIsActive($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereIsRunning($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereLastFinished($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereLastRowsInserted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereLastRunCount($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereLastStarted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereMinimumSyncDate($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereOriginTotalRows($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable wherePartnerIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationTable whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTable    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationTable    withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationTable extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_tables';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($table) {
            $table->schedule()->delete();
        });
    }

    public function database()
    {
        return $this->belongsTo(PartnerIntegration::class, 'partner_integration_id');
    }

    public function schedule()
    {
        return $this->hasOne(PartnerIntegrationTableSchedule::class, 'partner_integration_table_id');
    }

    public static function getByDatabaseIdWithBuildStatus($database_id)
    {
        return DB::table('di_partner_integration_tables as t')
            ->leftJoin('__bytespree.v_dw_jenkins_builds__latest_results_by_job as r', function ($join) {
                $join->on( 'r.database_id', '=', 't.partner_integration_id')
                    ->on('t.name', '=', 'r.job_name');
            })
            ->where('partner_integration_id', $database_id)
            ->where('is_deleted', FALSE)
            ->orderBy('name')
            ->select([
                't.*',
                't.name as orig_name',
                'r.result_code',
                DB::raw("COALESCE(r.status_color,'blue') as status_color"),
                'r.is_running'
            ])
            ->get();
    }
}
