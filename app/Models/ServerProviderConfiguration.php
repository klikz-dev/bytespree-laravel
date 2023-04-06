<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ServerProviderConfiguration
 *
 * @property        int                                                               $id
 * @property        int|null                                                          $server_provider_id
 * @property        int|null                                                          $group_hierarchy
 * @property        string|null                                                       $slug
 * @property        int|null                                                          $memory
 * @property        int|null                                                          $storage
 * @property        int|null                                                          $cpus
 * @property        string|null                                                       $description
 * @property        string|null                                                       $actual_price
 * @property        string|null                                                       $resale_price
 * @property        bool|null                                                         $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                   $created_at
 * @property        \Illuminate\Support\Carbon|null                                   $updated_at
 * @property        int|null                                                          $nodes
 * @property        \Illuminate\Support\Carbon|null                                   $deleted_at
 * @property        \App\Models\ServerProvider|null                                   $provider
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ServerProviderConfiguration    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereActualPrice($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereCpus($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereDescription($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereGroupHierarchy($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereMemory($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereNodes($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereResalePrice($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereServerProviderId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereSlug($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereStorage($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProviderConfiguration whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ServerProviderConfiguration    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ServerProviderConfiguration    withoutTrashed()
 * @mixin \Eloquent
 */
class ServerProviderConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_server_provider_configurations';

    public function provider()
    {
        return $this->belongsTo(ServerProvider::class, 'server_provider_id');
    }
}
