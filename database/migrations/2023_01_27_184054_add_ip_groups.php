<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Server;
use App\Models\ServerIp;
use App\Models\ServerIpGroup;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('di_server_ips', function ($table) {
            $table->bigInteger('group_id')->nullable();
        });

        Schema::create('di_server_groups', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('server_id');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $servers = Server::with('ips')->get();
        $dmi_ips = collect(app('orchestration')->getAllowedIPs())->pluck('ip');

        foreach ($servers as $server) {
            $server->ips = $server->ips->filter(function ($ip) use ($dmi_ips) {
                return $dmi_ips->search($ip->ip) === FALSE;
            });

            if($server->ips->count() > 0) {
                $group = ServerIpGroup::create([
                    'server_id' => $server->id
                ]);

                foreach($server->ips as $ip) {
                    $ip->update([
                        'group_id' => $group->id
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("di_server_ips", function ($table) {
            $table->dropColumn('group_id');
        });

        Schema::drop('di_server_groups');
    }
};
