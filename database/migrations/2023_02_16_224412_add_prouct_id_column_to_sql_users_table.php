<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('di_partner_integration_sql_users', function (Blueprint $table) {
            $table->integer('product_id')->nullable();
            $table->integer('project_id')->nullable();
        });

        $product = Product::where('name', 'datalake')->first();
        if (! empty($product)) {
            // Assume all existing sql users are of the data lake product
            DB::table('di_partner_integration_sql_users')
                ->whereNull('deleted_at')
                ->update(['product_id' => $product->id]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('di_partner_integration_sql_users', function (Blueprint $table) {
            $table->dropColumn('product_id');
            $table->dropColumn('project_id');
        });
    }
};
