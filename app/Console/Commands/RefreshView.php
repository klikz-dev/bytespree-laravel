<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PartnerIntegration;
use App\Classes\Database\View;

class RefreshView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:refresh {database_id} {view_schema} {view_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes a materialized view';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $database = PartnerIntegration::find($this->argument('database_id'));

        $result = View::refresh($database, $this->argument('view_schema'), $this->argument('view_name'));

        if (! $result) {
            throw new Exception("View refresh failed");
        }
    }
}
