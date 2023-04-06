<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Product;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = Product::all();
        $roles = [
            [
                'role_name' => 'Team Admin',
                'product_id' => $products->where('name', 'studio')->first()->id,
            ],
            [
                'role_name' => 'Project Admin',
                'product_id' => $products->where('name', 'studio')->first()->id,
            ],
            [
                'role_name' => 'Collaborator',
                'product_id' => $products->where('name', 'studio')->first()->id,
            ],
            [
                'role_name' => 'Observer',
                'product_id' => $products->where('name', 'studio')->first()->id,
            ],
            [
                'role_name' => 'Database Admin',
                'product_id' => $products->where('name', 'datalake')->first()->id,
            ],
            [
                'role_name' => 'Database Observer',
                'product_id' => $products->where('name', 'datalake')->first()->id,
            ],
        ];

        foreach ($roles as $role) {
            $role = Role::create($role);
        }
    }
}
