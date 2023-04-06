<?php

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
        Schema::dropIfExists('u_actions');
        Schema::dropIfExists('u_user_actions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( !Schema::hasTable('u_actions')) {
            Schema::create('u_actions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_deleted')->default(FALSE);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('u_user_actions')) {
            Schema::create('u_user_actions', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('action_id');
                $table->boolean('is_deleted')->default(FALSE);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
};
