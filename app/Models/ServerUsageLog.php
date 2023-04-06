<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ServerUsageLog
 *
 * @property        int                                                  $id
 * @property        int|null                                             $server_id
 * @property        string|null                                          $last_sent
 * @property        string|null                                          $usage
 * @property        bool|null                                            $is_deleted
 * @property        \Illuminate\Support\Carbon|null                      $created_at
 * @property        \Illuminate\Support\Carbon|null                      $updated_at
 * @property        \Illuminate\Support\Carbon|null                      $deleted_at
 * @property        \App\Models\Server|null                              $server
 * @method   static \Database\Factories\ServerUsageLogFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ServerUsageLog    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog whereLastSent($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog whereServerId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ServerUsageLog whereUsage($value)
 * @method   static \Illuminate\Database\Query\Builder|ServerUsageLog    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ServerUsageLog    withoutTrashed()
 * @mixin \Eloquent
 */
class ServerUsageLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_server_usage_logs';

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }
}
