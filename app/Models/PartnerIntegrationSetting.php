<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\OldCrypt;

/**
 * App\Models\PartnerIntegrationSetting
 *
 * @property        int                                                             $id
 * @property        int|null                                                        $partner_integration_id
 * @property        int|null                                                        $integration_setting_id
 * @property        mixed|null                                                      $value
 * @property        string|null                                                     $table_name
 * @property        string|null                                                     $setting_type
 * @property        bool|null                                                       $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                 $created_at
 * @property        \Illuminate\Support\Carbon|null                                 $updated_at
 * @property        \Illuminate\Support\Carbon|null                                 $deleted_at
 * @property        \App\Models\PartnerIntegration|null                             $database
 * @property        \App\Models\IntegrationSetting|null                             $setting
 * @method   static \Database\Factories\PartnerIntegrationSettingFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSetting    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereIntegrationSettingId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting wherePartnerIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereSettingType($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSetting whereValue($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSetting    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSetting    withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_settings';

    protected $casts = [
        'value' => OldCrypt::class,
    ];

    public function database()
    {
        return $this->belongsTo(PartnerIntegration::class, 'partner_integration_id');
    }

    public function setting()
    {
        return $this->belongsTo(IntegrationSetting::class, 'integration_setting_id');
    }
}
