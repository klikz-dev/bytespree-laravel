<?php

namespace Tests\Feature\Http\Controllers\InternalApi\V1\Explorer;

use Tests\AdminInternalApiTestCase;
use App\Models\Explorer\Project;
use App\Models\Manager\DatabaseTag;
use App\Models\Manager\Tag;
use App\Models\PartnerIntegration;

class ProjectControllerTest extends AdminInternalApiTestCase
{
    public function test_access_with_users()
    {
        $this->actingAs($this->createUserWithPermissions())
            ->get('/internal-api/v1/studio/projects')
            ->assertStatus(403);

        $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/internal-api/v1/studio/projects')
            ->assertStatus(200);
    }

    public function test_suggest_schema_that_doesnt_exist()
    {
        $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->post('/internal-api/v1/studio/projects/suggest-schema', [
                'display_name' => 'Test Project'
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['data' => ['suggested_name' => 'test_project']]);
    }

    public function test_suggest_schema_that_already_exists()
    {
        Project::create(['display_name' => 'Test Project', 'name' => 'test_project']);
    
        $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->post('/internal-api/v1/studio/projects/suggest-schema', [
                'display_name' => 'Test Project'
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['data' => ['suggested_name' => 'test_project_2']]);
    }

    public function test_suggest_schema_with_no_usable_characters()
    {
        $expected = date('\s_Y_m_d');

        $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->post('/internal-api/v1/studio/projects/suggest-schema', [
                'display_name' => '🌮🌮🌮'
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['data' => ['suggested_name' => $expected]]);
    }

    public function test_list_with_no_items()
    {
        $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/internal-api/v1/studio/projects')
            ->assertStatus(200)
            ->assertJsonFragment(['data' => []]);
    }

    public function test_list_with_one_project()
    {
        $project = Project::create(['display_name' => 'Test Project', 'name' => 'test_project']);

        $json = $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/internal-api/v1/studio/projects')
            ->assertStatus(200)
            ->json();
            
        $this->assertCount(1, $json['data']);

        $this->assertEquals($project->id, $json['data'][0]['id']);
        $this->assertEquals($project->display_name, $json['data'][0]['display_name']);
        $this->assertEquals($project->name, $json['data'][0]['name']);
    }

    public function test_list_with_primary_database()
    {
        $database = PartnerIntegration::factory(['database' => 'test_database'])->create();

        $project = Project::create(['display_name' => 'Test Project', 'name' => 'test_project', 'partner_integration_id' => $database->id]);

        $json = $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/internal-api/v1/studio/projects')
            ->assertStatus(200)
            ->json();

        $this->assertCount(1, $json['data']);

        $this->assertEquals($database->database, $json['data'][0]['primary_database']['database']);
    }

    public function test_list_with_primary_database_with_tags()
    {
        $database = PartnerIntegration::factory(['database' => 'test_database'])->create();
        $tag = Tag::create(['name' => 'test_tag', 'color' => '#000000']);
        DatabaseTag::create(['tag_id' => $tag->id, 'control_id' => $database->id]);

        $project = Project::create(['display_name' => 'Test Project', 'name' => 'test_project', 'partner_integration_id' => $database->id]);

        $json = $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/internal-api/v1/studio/projects')
            ->assertStatus(200)
            ->json();

        $this->assertCount(1, $json['data']);

        $this->assertEquals($tag->name, $json['data'][0]['tags'][0]['name']);
    }

    public function test_list_with_tag_filtering()
    {
        $database1 = PartnerIntegration::factory(['database' => 'test_database'])->create();
        $tag1 = Tag::create(['name' => 'test_tag', 'color' => '#000000']);
        DatabaseTag::create(['tag_id' => $tag1->id, 'control_id' => $database1->id]);

        $database2 = PartnerIntegration::factory(['database' => 'test_database'])->create();
        $tag2 = Tag::create(['name' => 'test_tag', 'color' => '#000000']);
        DatabaseTag::create(['tag_id' => $tag2->id, 'control_id' => $database2->id]);

        $project1 = Project::create(['display_name' => 'Test Project 1', 'name' => 'test_project_1', 'partner_integration_id' => $database1->id]);
        $project2 = Project::create(['display_name' => 'Test Project 2', 'name' => 'test_project_2', 'partner_integration_id' => $database2->id]);

        // Filter by our first tag
        $json = $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/internal-api/v1/studio/projects?tag=' . $tag1->id)
            ->assertStatus(200)
            ->json();

        $this->assertCount(1, $json['data']);

        $this->assertEquals($project1->name, $json['data'][0]['name']);

        // Filter by our second tag
        $json = $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->get('/internal-api/v1/studio/projects?tag=' . $tag2->id)
            ->assertStatus(200)
            ->json();

        $this->assertCount(1, $json['data']);

        $this->assertEquals($project2->name, $json['data'][0]['name']);
    }

    // todo foreign databases and what not

    public function test_create_without_permission()
    {
        $this->actingAs($this->createUserWithPermissions(['studio_access']))
            ->post('/internal-api/v1/studio/projects', [
                'display_name'           => 'Test Project',
                'name'                   => 'test_project',
                'partner_integration_id' => 1,
                'foreign_databases'      => [],
            ])
            ->assertStatus(403);
    }

    public function test_create_with_invalid_primary_database()
    {
        $this->actingAs($this->createUserWithPermissions(['studio_access', 'studio_create']))
            ->post('/internal-api/v1/studio/projects', [
                'display_name'           => 'Test Project',
                'name'                   => 'test_project',
                'partner_integration_id' => 10001,
                'foreign_databases'      => [],
            ])
            ->assertStatus(400);
    }

    public function test_create_without_project_name()
    {
        $database = PartnerIntegration::create(['database' => 'test_database']);

        $this->actingAs($this->createUserWithPermissions(['studio_access', 'studio_create']))
            ->post('/internal-api/v1/studio/projects', [
                'display_name'           => 'Test Project',
                'name'                   => '',
                'partner_integration_id' => $database->id,
                'foreign_databases'      => [],
            ])
            ->assertStatus(400);
    }

    public function test_create_project_with_super_long_name()
    {
        $database = PartnerIntegration::create(['database' => 'test_database']);

        $this->actingAs($this->createUserWithPermissions(['studio_access', 'studio_create']))
            ->post('/internal-api/v1/studio/projects', [
                'display_name'           => str_pad('a', 201, 'h'),
                'name'                   => 'test_project',
                'partner_integration_id' => $database->id,
                'foreign_databases'      => [],
            ])
            ->assertStatus(400);
    }

    public function test_create_project_with_duplicate_name()
    {
        $database = PartnerIntegration::create(['database' => 'test_database']);
        Project::create(['name' => 'test_project', 'display_name' => 'foobar']);

        $this->actingAs($this->createUserWithPermissions(['studio_access', 'studio_create']))
            ->post('/internal-api/v1/studio/projects', [
                'display_name'           => 'Test Project',
                'name'                   => 'test_project',
                'partner_integration_id' => $database->id,
                'foreign_databases'      => [],
            ])
            ->assertStatus(400);
    }

    public function test_create_project_with_duplicate_display_name()
    {
        $database = PartnerIntegration::create(['database' => 'test_database']);
        Project::create(['name' => 'foobar', 'display_name' => 'Test Project']);

        $this->actingAs($this->createUserWithPermissions(['studio_access', 'studio_create']))
            ->post('/internal-api/v1/studio/projects', [
                'display_name'           => 'Test Project',
                'name'                   => 'test_project',
                'partner_integration_id' => $database->id,
                'foreign_databases'      => [],
            ])
            ->assertStatus(400);
    }

    public function test_create_project_with_invalid_foreign_database_id()
    {
        $database = PartnerIntegration::create(['database' => 'test_database']);
        $foreign_databases = PartnerIntegration::factory()->count(5)->create()->pluck('id')->toArray();

        // Add an invalid foreign db id
        $foreign_databases[] = 10001;

        $this->actingAs($this->createUserWithPermissions(['studio_access', 'studio_create']))
            ->post('/internal-api/v1/studio/projects', [
                'display_name'           => 'Test Project',
                'name'                   => 'test_project',
                'partner_integration_id' => $database->id,
                'foreign_databases'      => $foreign_databases,
            ])
            ->assertStatus(400);
    }

    public function test_update_invalid_project_id()
    {
        $this->actingAs($this->createUserWithPermissions(['studio_access', 'studio_create']))
            ->put('/internal-api/v1/studio/projects/1001', [
                'display_name'      => 'Test Project',
                'foreign_databases' => [],
            ])
            ->assertStatus(404);
    }

    public function test_update_without_display_name()
    {
        $project = Project::create(['name' => 'foobar', 'display_name' => 'Test Project']);
        $second_project = Project::create(['name' => 'foobar', 'display_name' => 'Test Project2']);

        $this->actingAs($this->createUserWithPermissions(['studio_access', 'studio_create']))
            ->put('/internal-api/v1/studio/projects/' . $project->id, [
                'display_name'      => 'Test Project2',
                'foreign_databases' => [],
            ])
            ->assertStatus(400);
    }
}
