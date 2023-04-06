<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure every existing table has soft deletes added. If a table has is_deleted, update all deleted_rows to have a deleted_at matching its updated_at value
     */
    public function up()
    {
        collect(Schema::getAllTables())
            ->filter(fn($tbl) => $tbl->tablename != 'migrations')
            ->filter(fn($tbl) => ! Schema::hasColumn($tbl->tablename, 'deleted_at'))
            ->each(function($tbl) {
                Schema::table($tbl->tablename, function (Blueprint $table) {
                    $table->softDeletes();
                });
            })->filter(fn($tbl) => Schema::hasColumn($tbl->tablename, 'is_deleted'))
            ->each(function($tbl) {
                if (Schema::hasColumn($tbl->tablename, 'updated_at')) {
                    DB::table($tbl->tablename)->where('is_deleted', TRUE)
                        ->update(['deleted_at' => DB::raw('updated_at')]);
                } else {
                    DB::table($tbl->tablename)->where('is_deleted', TRUE)
                        ->update(['deleted_at' => now()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        collect(Schema::getAllTables())
            ->filter(fn($tbl) => $tbl->tablename != 'migrations')
            ->filter(fn($tbl) => Schema::hasColumn($tbl->tablename, 'deleted_at'))
            ->each(function($tbl) {
                Schema::table($tbl->tablename, function (Blueprint $table) {
                    $table->dropColumn('deleted_at');
                });
            });
    }
};
