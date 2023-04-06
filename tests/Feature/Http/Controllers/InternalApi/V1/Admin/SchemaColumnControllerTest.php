<?php

namespace Tests\Feature\Http\Controllers\InternalApi\V1\Admin;

use Tests\AdminInternalApiTestCase;
use App\Models\Explorer\ManagedDatabase;
use App\Models\Explorer\DestinationDatabaseTable;
use App\Models\Explorer\DestinationDatabaseTableColumn;

class SchemaColumnControllerTest extends AdminInternalApiTestCase
{
    public function test_admin_schema_column_list()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);
        $table = DestinationDatabaseTable::create(['managed_database_id' => $schema->id, 'name' => 'test_table']);
        $column = DestinationDatabaseTableColumn::create(['managed_database_table_id' => $table->id, 'name' => 'test_column', 'type' => 'varchar', 'length' => '52']);

        $this->actingAs($this->getAdminUser())
            ->get('/internal-api/v1/admin/schema-columns/' . $table->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'test_column', 'type' => 'varchar', 'length' => 52]);
    }

    public function test_admin_schema_column_create()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);
        $table = DestinationDatabaseTable::create(['managed_database_id' => $schema->id, 'name' => 'test_table']);

        $this->actingAs($this->getAdminUser())
            ->postJson('/internal-api/v1/admin/schema-columns', [
                'managed_database_table_id' => $table->id,
                'name'                      => 'test_column',
                'type'                      => 'varchar',
                'length'                    => 75,
                'precision'                 => '',
            ])
            ->assertStatus(200);
        
        $this->assertNotNull(DestinationDatabaseTableColumn::where('name', 'test_column')->first());
    }

    public function test_admin_schema_column_update()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);
        $table = DestinationDatabaseTable::create(['managed_database_id' => $schema->id, 'name' => 'test_table']);
        $column = DestinationDatabaseTableColumn::create(['managed_database_table_id' => $table->id, 'name' => 'test_column', 'type' => 'varchar', 'length' => '29']);

        $this->actingAs($this->getAdminUser())
            ->putJson('/internal-api/v1/admin/schema-columns/' . $column->id, [
                'name'                      => 'test_column_updated',
                'managed_database_table_id' => $table->id,
                'type'                      => 'date',
                'length'                    => '',
                'precision'                 => '',
            ])
            ->assertStatus(200);
            
        $this->assertEquals(
            'test_column_updated',
            DestinationDatabaseTableColumn::find($column->id)->name
        );
    }

    public function test_admin_schema_column_delete()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);
        $table = DestinationDatabaseTable::create(['managed_database_id' => $schema->id, 'name' => 'test_table']);
        $column = DestinationDatabaseTableColumn::create(['managed_database_table_id' => $table->id, 'name' => 'test_column', 'type' => 'varchar', 'length' => '29']);

        $this->actingAs($this->getAdminUser())
            ->delete('/internal-api/v1/admin/schema-columns/' . $column->id)
            ->assertStatus(200);

        $this->assertNull(DestinationDatabaseTableColumn::find($column->id));
    }
}
