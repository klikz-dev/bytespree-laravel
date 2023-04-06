<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IntegrationScheduleType;
use App\Models\IntegrationScheduleTypeProperty;

class IntegrationScheduleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            [
                'name' => 'Daily',
                'properties' => [
                    [
                        'name' => 'Hour',
                        'options' => [
                            ["label" => "12 AM", "value" => "0"],
                            ["label" => "1 AM", "value" => "1"],
                            ["label" => "2 AM", "value" => "2"],
                            ["label" => "3 AM", "value" => "3"],
                            ["label" => "4 AM", "value" => "4"],
                            ["label" => "5 AM", "value" => "5"],
                            ["label" => "6 AM", "value" => "6"],
                            ["label" => "7 AM", "value" => "7"],
                            ["label" => "8 AM", "value" => "8"],
                            ["label" => "9 AM", "value" => "9"],
                            ["label" => "10 AM", "value" => "10"],
                            ["label" => "11 AM", "value" => "11"],
                            ["label" => "12 PM", "value" => "12"],
                            ["label" => "1 PM", "value" => "13"],
                            ["label" => "2 PM", "value" => "14"],
                            ["label" => "3 PM", "value" => "15"],
                            ["label" => "4 PM", "value" => "16"],
                            ["label" => "5 PM", "value" => "17"],
                            ["label" => "6 PM", "value" => "18"],
                            ["label" => "7 PM", "value" => "19"],
                            ["label" => "8 PM", "value" => "20"],
                            ["label" => "9 PM", "value" => "21"],
                            ["label" => "10 PM", "value" => "22"],
                            ["label" => "11 PM", "value" => "23"],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Weekly',
                'properties' => [
                    [
                        'name' => 'Day of Week',
                        'options' => [
                            ["label" => "Monday", "value" => "M"],
                            ["label" => "Tuesday", "value" => "T"],
                            ["label" => "Wednesday", "value" => "W"],
                            ["label" => "Thursday", "value" => "H"],
                            ["label" => "Friday", "value" => "F"],
                            ["label" => "Saturday", "value" => "S"],
                            ["label" => "Sunday", "value" => "U"],
                        ],
                    ],
                    [
                        'name' => 'Hour',
                        'options' => [
                            ["label" => "12 AM", "value" => "0"],
                            ["label" => "1 AM", "value" => "1"],
                            ["label" => "2 AM", "value" => "2"],
                            ["label" => "3 AM", "value" => "3"],
                            ["label" => "4 AM", "value" => "4"],
                            ["label" => "5 AM", "value" => "5"],
                            ["label" => "6 AM", "value" => "6"],
                            ["label" => "7 AM", "value" => "7"],
                            ["label" => "8 AM", "value" => "8"],
                            ["label" => "9 AM", "value" => "9"],
                            ["label" => "10 AM", "value" => "10"],
                            ["label" => "11 AM", "value" => "11"],
                            ["label" => "12 PM", "value" => "12"],
                            ["label" => "1 PM", "value" => "13"],
                            ["label" => "2 PM", "value" => "14"],
                            ["label" => "3 PM", "value" => "15"],
                            ["label" => "4 PM", "value" => "16"],
                            ["label" => "5 PM", "value" => "17"],
                            ["label" => "6 PM", "value" => "18"],
                            ["label" => "7 PM", "value" => "19"],
                            ["label" => "8 PM", "value" => "20"],
                            ["label" => "9 PM", "value" => "21"],
                            ["label" => "10 PM", "value" => "22"],
                            ["label" => "11 PM", "value" => "23"],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Monthly',
                'properties' => [
                    [
                        'name' => 'Day of Month',
                        'options' => [
                            ["label" => "1", "value" => "1"],
                            ["label" => "2", "value" => "2"],
                            ["label" => "3", "value" => "3"],
                            ["label" => "4", "value" => "4"],
                            ["label" => "5", "value" => "5"],
                            ["label" => "6", "value" => "6"],
                            ["label" => "7", "value" => "7"],
                            ["label" => "8", "value" => "8"],
                            ["label" => "9", "value" => "9"],
                            ["label" => "10", "value" => "10"],
                            ["label" => "11", "value" => "11"],
                            ["label" => "12", "value" => "12"],
                            ["label" => "13", "value" => "13"],
                            ["label" => "14", "value" => "14"],
                            ["label" => "15", "value" => "15"],
                            ["label" => "16", "value" => "16"],
                            ["label" => "17", "value" => "17"],
                            ["label" => "18", "value" => "18"],
                            ["label" => "19", "value" => "19"],
                            ["label" => "20", "value" => "20"],
                            ["label" => "21", "value" => "21"],
                            ["label" => "22", "value" => "22"],
                            ["label" => "23", "value" => "23"],
                            ["label" => "24", "value" => "24"],
                            ["label" => "25", "value" => "25"],
                            ["label" => "26", "value" => "26"],
                            ["label" => "27", "value" => "27"],
                            ["label" => "28", "value" => "28"],
                        ],
                    ],
                    [
                        'name' => 'Hour',
                        'options' => [
                            ["label" => "12 AM", "value" => "0"],
                            ["label" => "1 AM", "value" => "1"],
                            ["label" => "2 AM", "value" => "2"],
                            ["label" => "3 AM", "value" => "3"],
                            ["label" => "4 AM", "value" => "4"],
                            ["label" => "5 AM", "value" => "5"],
                            ["label" => "6 AM", "value" => "6"],
                            ["label" => "7 AM", "value" => "7"],
                            ["label" => "8 AM", "value" => "8"],
                            ["label" => "9 AM", "value" => "9"],
                            ["label" => "10 AM", "value" => "10"],
                            ["label" => "11 AM", "value" => "11"],
                            ["label" => "12 PM", "value" => "12"],
                            ["label" => "1 PM", "value" => "13"],
                            ["label" => "2 PM", "value" => "14"],
                            ["label" => "3 PM", "value" => "15"],
                            ["label" => "4 PM", "value" => "16"],
                            ["label" => "5 PM", "value" => "17"],
                            ["label" => "6 PM", "value" => "18"],
                            ["label" => "7 PM", "value" => "19"],
                            ["label" => "8 PM", "value" => "20"],
                            ["label" => "9 PM", "value" => "21"],
                            ["label" => "10 PM", "value" => "22"],
                            ["label" => "11 PM", "value" => "23"],
                        ],
                    ],
                ],
            ],
            ['name' => 'Manually', 'properties' => []],
            ['name' => 'Every Hour', 'properties' => []],
            ['name' => 'Every 15 minutes', 'properties' => []],
        ];

        foreach ($types as $type)
        {
            $schedule_type = IntegrationScheduleType::updateOrCreate(
                ['name' => $type['name']]
            );

            foreach ($type['properties'] as $property) {
                IntegrationScheduleTypeProperty::updateOrCreate(
                    [
                        'schedule_type_id' => $schedule_type->id,
                        'name' => $property['name'],
                    ],
                    [
                        'options' => $property['options'] ?? null,
                    ]);
            }
        }
    }
}
