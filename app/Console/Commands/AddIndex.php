<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PartnerIntegration;
use App\Classes\Database\Table;

class AddIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:add {database_id} {table} {column}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds an index to a table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $database = PartnerIntegration::find($this->argument('database_id'));
        Table::createIndex($database, $this->argument('table'), base64_decode($this->argument('column')));
    }
}
