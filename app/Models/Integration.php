<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\OldCrypt;
use App\Casts\Bytea;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Exception;

/**
 * App\Models\Integration
 *
 * @property        int                                                                       $id
 * @property        string|null                                                               $name
 * @property        string|null                                                               $description
 * @property        string|null                                                               $instructions
 * @property        bool|null                                                                 $is_active
 * @property        bool|null                                                                 $use_tables
 * @property        mixed|null                                                                $logo
 * @property        string|null                                                               $class_name
 * @property        bool|null                                                                 $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                           $created_at
 * @property        \Illuminate\Support\Carbon|null                                           $updated_at
 * @property        string|null                                                               $version
 * @property        bool|null                                                                 $use_hooks
 * @property        bool|null                                                                 $fully_replace_tables
 * @property        bool|null                                                                 $is_oauth
 * @property        string|null                                                               $oauth_url
 * @property        bool|null                                                                 $is_unified_application
 * @property        mixed|null                                                                $client_id
 * @property        string|null                                                               $client_secret
 * @property        array|null                                                                $known_limitations
 * @property        \Illuminate\Support\Carbon|null                                           $deleted_at
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\PartnerIntegration[] $databases
 * @property        int|null                                                                  $databases_count
 * @property        mixed                                                                     $safe_name
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\IntegrationSetting[] $settings
 * @property        int|null                                                                  $settings_count
 * @method   static \Database\Factories\IntegrationFactory                                    factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Integration                            onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereClassName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereClientId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereClientSecret($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereFullyReplaceTables($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereInstructions($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereIsActive($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereIsOauth($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereIsUnifiedApplication($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereKnownLimitations($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereLogo($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereOauthUrl($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereUseHooks($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereUseTables($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Integration                         whereVersion($value)
 * @method   static \Illuminate\Database\Query\Builder|Integration                            withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Integration                            withoutTrashed()
 * @mixin \Eloquent
 */
class Integration extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_integrations';

    protected $casts = [
        'client_id'         => OldCrypt::class,
        'known_limitations' => 'array',
        'logo'              => Bytea::class,
    ];

    protected $hidden = ['logo'];

    public function databases()
    {
        return $this->hasMany(PartnerIntegration::class, 'integration_id');
    }

    public function getSafeNameAttribute()
    {
        return str_replace(' ', '', $this->name);
    }

    public function settings()
    {
        return $this->hasMany(IntegrationSetting::class, 'integration_id')->orderBy('ordinal_position');
    }
}
