<?php

namespace Tests\Feature\Http\Controllers\InternalApi\V1\Admin;

use Tests\AdminInternalApiTestCase;
use App\Models\Explorer\ManagedDatabase;
use App\Models\Explorer\DestinationDatabaseTable;

class SchemaTableControllerTest extends AdminInternalApiTestCase
{
    public function test_admin_schema_table_list()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);
        $table = DestinationDatabaseTable::create(['managed_database_id' => $schema->id, 'name' => 'test_table']);

        $this->actingAs($this->getAdminUser())
            ->get('/internal-api/v1/admin/schema-tables/' . $schema->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'test_table']);
    }

    public function test_admin_schema_table_create()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);

        $this->actingAs($this->getAdminUser())
            ->postJson('/internal-api/v1/admin/schema-tables', [
                'managed_database_id' => $schema->id,
                'name'                => 'test_schema_table'
            ])
            ->assertStatus(200);
        
        $this->assertNotNull(DestinationDatabaseTable::where('name', 'test_schema_table')->first());
    }

    public function test_admin_schema_table_update()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);
        $table = DestinationDatabaseTable::create(['managed_database_id' => $schema->id, 'name' => 'test_table']);

        $this->actingAs($this->getAdminUser())
            ->putJson('/internal-api/v1/admin/schema-tables/' . $table->id, [
                'name'                => 'test_schema_table_updated',
                'managed_database_id' => $schema->id
            ])
            ->assertStatus(200);
        
        $this->assertEquals(
            'test_schema_table_updated',
            DestinationDatabaseTable::find($table->id)->name
        );
    }

    public function test_admin_schema_table_delete()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);
        $table = DestinationDatabaseTable::create(['managed_database_id' => $schema->id, 'name' => 'test_table']);

        $this->actingAs($this->getAdminUser())
            ->delete('/internal-api/v1/admin/schema-tables/' . $table->id)
            ->assertStatus(200);

        $this->assertNull(DestinationDatabaseTable::find($schema->id));
    }
}
