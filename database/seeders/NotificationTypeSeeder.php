<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\NotificationType;
use App\Models\NotificationTypeSetting;

class NotificationTypeSeeder extends Seeder
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
                'name' => 'Webhook',
                'class' => 'Webhook',
                'descriptor_setting' => 'payload_url',
                'settings' => [
                    [
                        'key' => 'payload_url',
                        'name' => 'Webhook URL',
                        'sort_order' => 1,
                        'is_secure' => FALSE,
                        'is_required' => TRUE,
                        'input_validation' => '^https:\/\/(.{5,})',
                        'input_placeholder' => 'https://mywebsite.com/my-webhook-endpoint',
                        'input_type' => 'text',
                        'input_options' => NULL,
                        'input_default' => NULL,
                        'input_description' => 'A secure, public facing URL is required. The URL should be configured to accept POST requests.',
                    ],
                    [
                        'key' => 'content_type',
                        'name' => 'Content Type',
                        'sort_order' => 2,
                        'is_secure' => FALSE,
                        'is_required' => TRUE,
                        'input_validation' => NULL,
                        'input_placeholder' => NULL,
                        'input_type' => 'select',
                        'input_options' => [
                            'application/json',
                            'application/x-www-form-urlencoded',
                        ],
                        'input_default' => 'application/json',
                        'input_description' => '<a href="https://intercom.help/bytespree/en/articles/6496827-webhook-system-notifications" target="_blank">Read more about webhooks and their content types</a>',
                    ],
                    [
                        'key' => 'secret',
                        'name' => 'Secret',
                        'sort_order' => 3,
                        'is_secure' => TRUE,
                        'is_required' => FALSE,
                        'input_validation' => '^\s*(?:[\w\.]\s*){6,32}$',
                        'input_placeholder' => NULL,
                        'input_type' => 'text',
                        'input_options' => NULL,
                        'input_default' => NULL,
                        'input_description' => 'Providing a secret allows you to <a href="https://intercom.help/bytespree/en/articles/6496827-webhook-system-notifications#h_fef57d91ec" target="_blank">secure your webhook</a> endpoint by validating a payload hash. Secrets should be 6-32 characters long.',
                    ],
                ],
            ],
        ];

        foreach ($types as $type) {
            $notification_type = NotificationType::updateOrCreate(
                ['name' => $type['name']],
                [
                    'class' => $type['class'],
                    'descriptor_setting' => $type['descriptor_setting'],
                ]
            );

            foreach ($type['settings'] as $setting) {
                NotificationTypeSetting::updateOrCreate(
                    [
                        'type_id' => $notification_type->id,
                        'key' => $setting['key'],
                    ],
                    $setting
                );
            }
        }

    }
}
