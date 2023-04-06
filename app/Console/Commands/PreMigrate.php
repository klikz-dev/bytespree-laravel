<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Illuminate\Support\Facades\Schema;

class PreMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pre-migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle necessary migration pre-work';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (Schema::hasTable('migrations')) {
            if (Schema::hasColumn('migrations', 'batch')) {
                $this->info('The migrations table already exists.  This command should only be run on an unconverted database.');

                return Command::SUCCESS;
            }
        }

        DB::statement("DROP TABLE IF EXISTS migrations");

        $create_sql = <<<SQL
                CREATE TABLE public.migrations (
                    id serial4 NOT NULL,
                    migration varchar(255) NOT NULL,
                    batch int4 NOT NULL,
                    CONSTRAINT migrations_pkey PRIMARY KEY (id)
                );
            SQL;

        DB::statement($create_sql);

        DB::table('migrations')->insert([
            'migration' => '2000_01_01_initial_laravel_pre_migration',
            'batch'     => 1,
        ]);
        
        return Command::SUCCESS;
    }
}
