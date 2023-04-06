<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Permission;
use App\Models\Product;

return new class extends Migration
{
    public function up()
    {
        Product::where('name', 'warehouse')->update(['name' => 'datalake']);
        Permission::where('name', 'warehouse_access')->update(['name' => 'datalake_access']);
        Permission::where('name', 'warehouse_create')->update(['name' => 'datalake_create']);
    }

    public function down()
    {
        Product::where('name', 'datalake')->update(['name' => 'warehouse']);
        Permission::where('name', 'datalake_access')->update(['name' => 'warehouse_access']);
        Permission::where('name', 'datalake_create')->update(['name' => 'warehouse_create']);
    }
};
