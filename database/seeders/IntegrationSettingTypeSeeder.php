<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IntegrationSettingType;

class IntegrationSettingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            ['type' => 'integration'],
            ['type' => 'table'],
        ];

        foreach ($types as $type) {
            IntegrationSettingType::updateOrCreate($type);
        }
    }
}
