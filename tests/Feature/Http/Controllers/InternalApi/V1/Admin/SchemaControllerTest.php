<?php

namespace Tests\Feature\Http\Controllers\InternalApi\V1\Admin;

use Tests\AdminInternalApiTestCase;
use App\Models\Explorer\ManagedDatabase;

class SchemaControllerTest extends AdminInternalApiTestCase
{
    public function test_admin_schema_list()
    {
        ManagedDatabase::create(['name' => 'test_schema']);

        $this->actingAs($this->getAdminUser())
            ->get('/internal-api/v1/admin/schemas')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_schema_create()
    {
        $this->actingAs($this->getAdminUser())
            ->postJson('/internal-api/v1/admin/schemas', [
                'name' => 'test_schema'
            ])
            ->assertStatus(200);
        
        $this->assertNotNull(ManagedDatabase::where('name', 'test_schema')->first());
    }

    public function test_admin_schema_update()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);

        $this->actingAs($this->getAdminUser())
            ->putJson('/internal-api/v1/admin/schemas/' . $schema->id, [
                'name' => 'test_schema_updated'
            ])
            ->assertStatus(200);
        
        $this->assertEquals(
            'test_schema_updated',
            ManagedDatabase::find($schema->id)->name
        );
    }

    public function test_admin_schema_delete()
    {
        $schema = ManagedDatabase::create(['name' => 'test_schema']);

        $this->actingAs($this->getAdminUser())
            ->delete('/internal-api/v1/admin/schemas/' . $schema->id)
            ->assertStatus(200);

        $this->assertNull(ManagedDatabase::find($schema->id));
    }
}
