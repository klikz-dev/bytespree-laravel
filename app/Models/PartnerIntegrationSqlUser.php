<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Casts\OldCrypt;

/**
 * App\Models\PartnerIntegrationSqlUser
 *
 * @property        int                                                             $id
 * @property        int|null                                                        $partner_integration_id
 * @property        string|null                                                     $username
 * @property        mixed|null                                                      $password
 * @property        bool|null                                                       $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                 $created_at
 * @property        \Illuminate\Support\Carbon|null                                 $updated_at
 * @property        \Illuminate\Support\Carbon|null                                 $deleted_at
 * @property        \App\Models\PartnerIntegration|null                             $database
 * @method   static \Database\Factories\PartnerIntegrationSqlUserFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSqlUser    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser wherePartnerIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser wherePassword($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationSqlUser whereUsername($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSqlUser    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationSqlUser    withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationSqlUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_sql_users';

    protected $casts = [
        'password' => OldCrypt::class,
    ];

    public function database()
    {
        return $this->belongsTo(PartnerIntegration::class, 'partner_integration_id');
    }
}
