<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ServerProvider
 *
 * @property        int                                                                                $id
 * @property        string|null                                                                        $name
 * @property        bool|null                                                                          $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                                    $created_at
 * @property        \Illuminate\Support\Carbon|null                                                    $updated_at
 * @property        \Illuminate\Support\Carbon|null                                                    $deleted_at
 * @property        \Illuminate\Database\Eloquent\Collection|\App\Models\ServerProviderConfiguration[] $configurations
 * @property        int|null                                                                           $configurations_count
 * @method   static \Database\Factories\ServerProviderFactory                                          factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ServerProvider                                  onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               whereName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerProvider                               whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ServerProvider                                  withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ServerProvider                                  withoutTrashed()
 * @mixin \Eloquent
 */
class ServerProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_server_providers';

    public function configurations()
    {
        return $this->hasMany(ServerProviderConfiguration::class, 'server_provider_id')
            ->orderBy('nodes', 'asc')
            ->orderBy('group_hierarchy', 'asc');
    }
}
