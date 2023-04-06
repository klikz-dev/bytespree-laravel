<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PartnerIntegrationMigration
 *
 * @property        int                                                               $id
 * @property        int|null                                                          $partner_integration_id
 * @property        string|null                                                       $description
 * @property        string|null                                                       $target_version
 * @property        string|null                                                       $migration_hash
 * @property        bool|null                                                         $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                   $created_at
 * @property        \Illuminate\Support\Carbon|null                                   $updated_at
 * @property        \Illuminate\Support\Carbon|null                                   $deleted_at
 * @method   static \Database\Factories\PartnerIntegrationMigrationFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationMigration    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration whereMigrationHash($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration wherePartnerIntegrationId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration whereTargetVersion($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationMigration whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationMigration    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationMigration    withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationMigration extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_migrations';
}
