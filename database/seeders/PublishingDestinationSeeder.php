<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Explorer\PublishingDestination;

class PublishingDestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // @todo: Destination class names will be invalid. They're no longer models. Update this once Publishers are ready.
        $destinations = [
            ['name' => 'Microsoft SQL Server', 'class_name' => 'Mssql'],
            ['name' => 'CSV', 'class_name' => 'Csv'],
            ['name' => 'Snapshot', 'class_name' => 'Snapshot'],
            ['name' => 'SFTP Site', 'class_name' => 'Sftp'],
            ['name' => 'View', 'class_name' => 'View'],
        ];

        foreach ($destinations as $destination) {
            PublishingDestination::updateOrCreate(
                ['name' => $destination['name']],
                $destination
            );
        }
    }
}
