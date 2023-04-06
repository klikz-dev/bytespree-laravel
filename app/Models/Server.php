<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Date;
use App\Casts\OldCrypt;
use DateTimeZone;
use Exception;
use DB;

/**
 * App\Models\Server
 *
 * @property        int                                                                       $id
 * @property        string|null                                                               $name
 * @property        string|null                                                               $hostname
 * @property        string|null                                                               $username
 * @property        mixed|null                                                                $password
 * @property        string|null                                                               $port
 * @property        string|null                                                               $driver
 * @property        string|null                                                               $default_database
 * @property        int|null                                                                  $server_provider_configuration_id
 * @property        string|null                                                               $provider_guid
 * @property        bool|null                                                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                           $created_at
 * @property        \Illuminate\Support\Carbon|null                                           $updated_at
 * @property        int|null                                                                  $free_space_percent
 * @property        string|null                                                               $date_deleted
 * @property        string|null                                                               $start_day
 * @property        string|null                                                               $start_time
 * @property        string|null                                                               $end_day
 * @property        string|null                                                               $end_time
 * @property        string|null                                                               $hostname_private
 * @property        string|null                                                               $alert_threshold
 * @property        bool|null                                                                 $is_default
 * @property        \Illuminate\Support\Carbon|null                                           $deleted_at
 * @property        \App\Models\ServerProviderConfiguration|null                              $configuration
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\PartnerIntegration[] $databases
 * @property        int|null                                                                  $databases_count
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\ServerIp[]           $ips
 * @property        int|null                                                                  $ips_count
 * @property        \App\Models\ServerUsageLog|null                                           $usageLog
 * @method   static \Database\Factories\ServerFactory                                         factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Server                                 onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereAlertThreshold($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereDateDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereDefaultDatabase($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereDriver($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereEndDay($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereEndTime($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereFreeSpacePercent($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereHostname($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereHostnamePrivate($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereIsDefault($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              wherePassword($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              wherePort($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereProviderGuid($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereServerProviderConfigurationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereStartDay($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereStartTime($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Server                              whereUsername($value)
 * @method   static \Illuminate\Database\Query\Builder|Server                                 withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Server                                 withoutTrashed()
 * @mixin \Eloquent
 */
class Server extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_servers';

    protected $hidden = ['password'];

    protected $casts = [
        'password' => OldCrypt::class,
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($server) {
            $server->databases->each(function ($database) {
                $database->delete();
            });

            $server->usageLog()->delete();
            $server->ips()->delete();
        });
    }

    public function databases()
    {
        return $this->hasMany(PartnerIntegration::class, 'server_id');
    }

    public function usageLog()
    {
        return $this->hasOne(ServerUsageLog::class, 'id', 'server_id');
    }

    public function ips()
    {
        return $this->hasMany(ServerIp::class, 'server_id');
    }

    public function groups()
    {
        return $this->hasMany(ServerIpGroup::class, 'server_id');
    }

    public function configuration()
    {
        return $this->hasOne(ServerProviderConfiguration::class, 'id', 'server_provider_configuration_id');
    }

    public function updateDefault(bool $is_default)
    {
        if ($is_default) {
            self::where('is_default', TRUE)->update(['is_default' => FALSE]);
        }

        $this->update(['is_default' => $is_default]);
    }

    public function deleteProviderServer()
    {
        $class_name = str_replace(' ', '', $this->configuration->provider->name);
        $provider = app('App\\Classes\\ServerProviders\\' . $class_name);

        $result = $provider->destroy($this);
        if (! $result) {
            $server = $provider->get($this);
            if (is_null($server)) {
                // If server no longer exists on provider, consider it a success
                return TRUE;
            }
        }

        return $result;
    }

    public function inMaintenanceWindow($date = NULL) : bool
    {
        if (empty($this->start_day) || empty($this->start_time) || empty($this->end_day) || empty($this->end_time)) {
            return FALSE;
        }

        $utc = new DateTimeZone("UTC");

        $start_day = Date::parse($this->start_day);
        $start_day->tz = $utc;

        if ($start_day->isFuture()) {
            $start_day = $start_day->previous();
        }

        $start_time = Date::parse($start_day->toDateString() . ' ' . $this->start_time);
        $start_time->tz = $utc;
        $start_time->subMinutes(30);

        $end_day = Date::parse($this->end_day);
        $end_day->tz = $utc;

        if ($start_day->diffInDays($end_day) > 1) {
            $end_day = $end_day->previous();
        }

        $end_time = Date::parse($end_day->toDateString() . ' ' . $this->end_time);
        $end_time->tz = $utc;
        $end_time->addMinutes(30);

        $current_time = now();
        if (! is_null($date)) {
            $current_time = $date;
        }

        return $current_time->between($start_time, $end_time);
    }

    /**
     * Get server disk usage
     *
     * @param  int $server_id ID of the server
     * @return int The amount of space consumed in bytes
     */
    public function getDiskUsage()
    {
        $total_size = 0;

        $config = array_merge(
            config('database.connections.pgsql'),
            [
              'host'     => $this->hostname,
              'username' => $this->username,
              'password' => $this->password,
              'port'     => $this->port,
              'database' => $this->default_database
            ]
        );
        
        $config_key = "server:{$this->id}";

        config(['database.connections.' . $config_key => $config]);

        try {
            DB::connection($config_key)->getPdo();
        } catch ( Exception $e) {
            logger()->error("Could not connect to server ID {$this->id}.");
            throw $e;
        }

        $result = DB::connection($config_key)->select('SELECT sum(pg_database_size(datname)) AS total_size FROM pg_database');

        if ($result) {
            $total_size = $result[0]->total_size;
        }

        return $total_size;
    }

    /**
     * Should we be able to retrieve a connection certificate for this server?
     */
    public function getHasCertificateAttribute(): bool
    {
        return $this->server_provider_configuration_id !== NULL;
    }
}
