<?php

namespace App\Models\Manager;

use App\Models\PartnerIntegration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;
use DateTime;

/**
 * App\Models\Manager\JenkinsBuild
 *
 * @property        int                                                $id
 * @property        int|null                                           $jenkins_build_id
 * @property        string|null                                        $job_name
 * @property        array|null                                         $parameters
 * @property        string|null                                        $result
 * @property        int|null                                           $build_timestamp
 * @property        int|null                                           $estimated_duration
 * @property        string|null                                        $message
 * @property        string|null                                        $started_at
 * @property        string|null                                        $finished_at
 * @property        bool|null                                          $is_deleted
 * @property        \Illuminate\Support\Carbon|null                    $created_at
 * @property        \Illuminate\Support\Carbon|null                    $updated_at
 * @property        string|null                                        $jenkins_home
 * @property        string|null                                        $job_path
 * @property        \Illuminate\Support\Carbon|null                    $deleted_at
 * @property        PartnerIntegration|null                            $database
 * @method   static \Database\Factories\Manager\JenkinsBuildFactory    factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild newQuery()
 * @method   static \Illuminate\Database\Query\Builder|JenkinsBuild    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild query()
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild unfinished()
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereBuildTimestamp($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereEstimatedDuration($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereFinishedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereJenkinsBuildId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereJenkinsHome($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereJobName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereJobPath($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereMessage($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereParameters($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereResult($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereStartedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|JenkinsBuild whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|JenkinsBuild    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|JenkinsBuild    withoutTrashed()
 * @mixin \Eloquent
 */
class JenkinsBuild extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'dw_jenkins_builds';

    protected $casts = [
        'parameters' => 'json',
        'started_at' => 'date'
    ];

    public function scopeUnfinished($query)
    {
        return $query->select(['id', 'job_path', 'started_at', 'jenkins_build_id', 'jenkins_home', 'parameters->3 as job_type', 'parameters->4 as database_id'])
            ->where('jenkins_build_id', '!=', 0)
            ->where('jenkins_home', '!=', '')
            ->where('job_path', '!=', '')
            ->whereNotNull('parameters->3')
            ->where(function ($query) {
                return $query->whereNull('result')
                    ->orWhereNotIn('result', ['SUCCESS', 'FAILURE', 'ABORTED']);
            });
    }

    public function database()
    {
        return $this->belongsTo(PartnerIntegration::class, 'database_id', 'id');
    }

    public function getBuildTimestampFormattedAttribute()
    {
        return date('Y-m-d H:i:s', $this->build_timestamp / 1000);
    }

    /**
     * Gets the data for a monitor
     *
     * @param  int    $jenkins_build_id The id of the jenkins build
     * @param  string $jenkins_home     The path to the root jenkins directory
     * @param  string $job_path         The path to the job information that is being ran
     * @param  bool   $ignore_sleep     If this method should sleep or not
     * @return object
     */
    public static function monitorData(int $jenkins_build_id, string $jenkins_home, string $job_path, bool $ignore_sleep)
    {
        $job_path_arr = explode('/', $job_path);

        $path = $jenkins_home . "/jobs/";
        foreach ($job_path_arr as $index => $value) {
            if ($index + 1 == count($job_path_arr)) {
                $job_name = $value;
                $path .= $value;
            } else {
                $path .= $value . "/jobs/";
            }
        }
        $path .= "/builds/" . $jenkins_build_id;

        $result = "";
        $timestamp = 0;
        $duration = 0;

        // Loop till the job ends then get the build details
        while (empty($result)) {
            if (file_exists($path . "/build.xml")) {
                $xml = @simplexml_load_file($path . "/build.xml");
                $xml = json_decode(json_encode($xml), TRUE);
                $result = $xml["result"];
                $timestamp = $xml["timestamp"];
                $duration = $xml["duration"];
            } else {
                if (! $ignore_sleep) {
                    sleep(5);
                } else {
                    break;
                }
            }
        }

        return (object) [
            "result"    => $result,
            "timestamp" => $timestamp,
            "duration"  => $duration,
            "job_name"  => $job_name,
            "path"      => $path
        ];
    }

    /**
     * Handles all jobs when a database is deleted mid sync/test
     *
     * @param PartnerIntegration $database The database that is being deleted
     */
    public static function handleIntegrationDelete(PartnerIntegration $database)
    {
        $sql = <<<SQL
            update dw_jenkins_builds
            set 
                result = 'ABORTED',
                finished_at = now()
            where
                result = 'false'
                and parameters->>4 = '$database->id'
            SQL;

        DB::statement($sql);

        // $this->counts->getJobCounts(); // todo
    }

    /**
     * Handles all jobs when a table is deleted mid sync/test
     *
     * @param PartnerIntegration $database   The database for the table being deleted
     * @param string             $table_name The table being deleted
     */
    public static function handleTableDelete(PartnerIntegration $database, string $table_name)
    {
        $sql = <<<SQL
            update dw_jenkins_builds
            set 
                result = 'ABORTED',
                finished_at = now()
            where
                result = 'false'
                and parameters->>4 = '$database->id'
                and parameters->>5 = '$table_name'
            SQL;

        DB::statement($sql);

        // $this->counts->getJobCounts(); // todo
    }
}
