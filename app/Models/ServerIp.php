<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ServerIp
 *
 * @property        int                                            $id
 * @property        int|null                                       $server_id
 * @property        string|null                                    $ip
 * @property        bool|null                                      $is_dmi
 * @property        bool|null                                      $is_deleted
 * @property        \Illuminate\Support\Carbon|null                $created_at
 * @property        \Illuminate\Support\Carbon|null                $updated_at
 * @property        \Illuminate\Support\Carbon|null                $deleted_at
 * @property        \App\Models\Server|null                        $server
 * @method   static \Database\Factories\ServerIpFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ServerIp    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp whereIp($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp whereIsDmi($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp whereServerId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerIp whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|ServerIp    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ServerIp    withoutTrashed()
 * @mixin \Eloquent
 */
class ServerIp extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_server_ips';

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function group()
    {
        return $this->belongsTo(ServerIpGroup::class, 'group_id');
    }
}
