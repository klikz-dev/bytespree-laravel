<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\EventType;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            'build start',
            'build end',
            'integration start',
            'integration end',
            'reconcile start',
            'reconcile end',
            'convert start',
            'convert end',
            'reverse integration start',
            'reverse integration end',
            'download',
            'upload',
            'compute',
            'convert'
        ];

        foreach ($types as $type) {
            EventType::updateOrCreate([
                'name' => $type
            ]);
        }
    }
}
