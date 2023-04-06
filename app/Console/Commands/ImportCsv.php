<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SavedData;
use App\Classes\Csv;

class ImportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {data_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports a csv into a table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $saved_data = SavedData::where('guid', $this->argument('data_id'))->first();

        return Csv::import($saved_data->data);
    }
}
