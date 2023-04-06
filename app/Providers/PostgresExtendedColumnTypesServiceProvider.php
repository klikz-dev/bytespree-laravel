<?php

namespace App\Providers;

use Illuminate\Database\Grammar;
use Illuminate\Support\ServiceProvider;

/**
 * This is meant to provide support for unsupported column types with Postgres.
 * 
 * General idea:
 * 
 * Grammar::macro('typeMyType', function() {
 *   return 'my_type';
 * });
 * 
 * Will create a new column, typed 'my_type', when built with Schema Builder's Blueprint via $table->addColumn('my_type', 'arbitrary_column_name');
 * 
 * Add additional types in boot().
 */
class PostgresExtendedColumnTypesServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        Grammar::macro('typeNumeric', function () {
            return 'numeric';
        });

        Grammar::macro('typeReal', function () {
            return 'real';
        });

        Grammar::macro('typeMoney', function () {
            return 'money';
        });

        Grammar::macro('typeDoublePrecision', function () {
            return 'double precision';
        });
    }
}
