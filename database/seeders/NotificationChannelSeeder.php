<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\NotificationChannel;

class NotificationChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $channels = [
            [ 
                'key' => 'publisher.success',
                'name' => 'Publisher Success',
            ],
            [
                'key' => 'publisher.failure',
                'name' => 'Publisher Failure',
            ],
        ];

        foreach ($channels as $channel) {
            NotificationChannel::updateOrCreate(
                ['key' => $channel['key']],
                $channel
            );
        }
    }
}
