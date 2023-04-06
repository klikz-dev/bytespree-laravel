<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PartnerIntegration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('di_partner_integrations', function ($table) {
            $table->text('tap_version')->nullable();
        });

        $databases = PartnerIntegration::get();

        foreach($databases as $database) {
            if(! empty($database->integration)) {
                $database->update([
                    'tap_version' => $database->integration->version
                ]);
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
        Schema::table('di_partner_integrations', function ($table) {
            $table->dropColumn('tap_version');
        });
    }
};
