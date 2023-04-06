<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $tables = ['dw_view_definitions', 'dw_view_definition_history'];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach($this->tables as $name) {
            Schema::table($name, function ($table) {
                $table->text('view_user_sql')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach($this->tables as $name) {
            Schema::table($name, function ($table) {
                $table->dropColumn('view_user_sql');
            });
        }
    }
};
