<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\ServerProviderConfiguration;
use App\Models\ServerProvider;

class ServerProviderConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // @todo: !important Verify matrix below
        $provider_matrix = [
            'DigitalOcean' => [
                [
                    "slug"               => "db-s-1vcpu-2gb",
                    "group_hierarchy"    => 1,
                    "memory"             => 2,
                    "storage"            => 25,
                    "cpus"               => 1,
                    "actual_price"       => 50.00,
                    "resale_price"       => 100.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "db-s-2vcpu-4gb",
                    "group_hierarchy"    => 2,
                    "memory"             => 4,
                    "storage"            => 38,
                    "cpus"               => 2,
                    "actual_price"       => 100.00,
                    "resale_price"       => 200.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "db-s-4vcpu-8gb",
                    "group_hierarchy"    => 3,
                    "memory"             => 8,
                    "storage"            => 115,
                    "cpus"               => 4,
                    "actual_price"       => 200.00,
                    "resale_price"       => 400.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "db-s-6vcpu-16gb",
                    "group_hierarchy"    => 4,
                    "memory"             => 16,
                    "storage"            => 270,
                    "cpus"               => 6,
                    "actual_price"       => 400.00,
                    "resale_price"       => 800.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "db-s-8vcpu-32gb",
                    "group_hierarchy"    => 5,
                    "memory"             => 32,
                    "storage"            => 580,
                    "cpus"               => 8,
                    "actual_price"       => 800.00,
                    "resale_price"       => 1600.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "db-s-16vcpu-64gb",
                    "group_hierarchy"    => 6,
                    "memory"             => 64,
                    "storage"            => 1012,
                    "cpus"               => 16,
                    "actual_price"       => 1600.00,
                    "resale_price"       => 3200.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "gd-16vcpu-64gb",
                    "group_hierarchy"    => 7,
                    "memory"             => 64,
                    "storage"            => 325,
                    "cpus"               => 16,
                    "actual_price"       => 1800.00,
                    "resale_price"       => 3600.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "gd-32vcpu-128gb",
                    "group_hierarchy"    => 8,
                    "memory"             => 128,
                    "storage"            => 695,
                    "cpus"               => 32,
                    "actual_price"       => 3640.00,
                    "resale_price"       => 7280.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "gd-40vcpu-160gb",
                    "group_hierarchy"    => 9,
                    "memory"             => 160,
                    "storage"            => 875,
                    "cpus"               => 40,
                    "actual_price"       => 4550.00,
                    "resale_price"       => 9100.00,
                    "nodes"              => 2,
                ],
                [
                    "slug"               => "db-s-1vcpu-2gb",
                    "group_hierarchy"    => 1,
                    "memory"             => 2,
                    "storage"            => 25,
                    "cpus"               => 1,
                    "actual_price"       => 30.00,
                    "resale_price"       => 60.00,
                    "nodes"              => 1,
                ],
                [
                    "slug"               => "db-s-2vcpu-4gb",
                    "group_hierarchy"    => 2,
                    "memory"             => 4,
                    "storage"            => 38,
                    "cpus"               => 2,
                    "actual_price"       => 60.00,
                    "resale_price"       => 120.00,
                    "nodes"              => 1,
                ],
                [
                    "slug"               => "db-s-4vcpu-8gb",
                    "group_hierarchy"    => 3,
                    "memory"             => 8,
                    "storage"            => 115,
                    "cpus"               => 4,
                    "actual_price"       => 120.00,
                    "resale_price"       => 240.00,
                    "nodes"              => 1,
                ],
                [
                    "slug"               => "db-s-6vcpu-16gb",
                    "group_hierarchy"    => 4,
                    "memory"             => 16,
                    "storage"            => 270,
                    "cpus"               => 6,
                    "actual_price"       => 240.00,
                    "resale_price"       => 480.00,
                    "nodes"              => 1,
                ],
                [
                    "slug"               => "db-s-8vcpu-32gb",
                    "group_hierarchy"    => 5,
                    "memory"             => 32,
                    "storage"            => 580,
                    "cpus"               => 8,
                    "actual_price"       => 480.00,
                    "resale_price"       => 960.00,
                    "nodes"              => 1,
                ],
            ],
        ];

        $providers = ServerProvider::all();

        foreach ($provider_matrix as $provider => $configurations) {
            $provider = $providers->where('name', $provider)->first();

            foreach ($configurations as $configuration) {
                ServerProviderConfiguration::updateOrCreate(
                    [
                        'server_provider_id' => $provider->id,
                        'slug' => $configuration['slug'],
                        'nodes' => $configuration['nodes'],
                    ],
                    $configuration
                );
            }
        }

    }
}
