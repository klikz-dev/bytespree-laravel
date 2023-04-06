<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServerIpGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_server_groups';

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function ips()
    {
        return $this->hasMany(ServerIp::class, 'group_id');
    }
}
