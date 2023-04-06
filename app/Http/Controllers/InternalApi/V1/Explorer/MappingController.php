<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Explorer\DestinationDatabaseTable;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectColumnComment;
use App\Models\Explorer\ProjectColumnMapping;
use App\Models\Explorer\ProjectColumnMappingCondition;
use App\Models\Explorer\MappingModule;
use App\Models\Explorer\MappingModuleField;
use App\Models\Explorer\MappingModuleFieldValue;
use App\Classes\Database\Table;
use App\Attributes\Can;

class MappingController extends Controller
{
    #[Can(permission: 'map_read', product: 'studio', id: 'project.id')]
    public function tables(Request $request, Project $project)
    {
        $tables = DestinationDatabaseTable::where('managed_database_id', $project->destination_schema_id)
            ->get()
            ->filter(function ($table) {
                return ! empty($table->name) && $table->name != 'null';
            })
            ->pluck('name');
            
        return response()->success($tables);
    }

    #[Can(permission: 'map_read', product: 'studio', id: 'project.id')]
    public function tableColumns(Request $request, Project $project, $schema_id, $table_name)
    {
        $table = DestinationDatabaseTable::where('managed_database_id', $schema_id)
            ->where('name', $table_name)
            ->first();

        return response()->success($table->columns);
    }

    #[Can(permission: 'map_write', product: 'studio', id: 'project.id')]
    public function store(Request $request, Project $project, string $schema, string $table)
    {
        $request->validateWithErrors([
            'column' => 'required|string'
        ]);

        if ($project->is_complete) {
            $project->sendCompletedEmail(
                "Mapping",
                "added to",
                $table,
                $request->column,
                $request->user()->name,
                $request->notes
            );
        }

        $mapping = ProjectColumnMapping::create([
            "project_id"              => $project->id,
            "source_table_name"       => $table,
            "source_column_name"      => $request->column,
            "schema_name"             => $schema,
            "destination_table_name"  => $request->destable,
            "destination_column_name" => $request->descol,
            "notes"                   => $request->notes,
            "mapping_module_id"       => $request->module_id,
            "user_id"                 => $request->user()->user_handle,
            "is_programmed"           => FALSE
        ]);

        if (! empty($request->destable)) {
            $comment_text = "Column mapped to {$request->destable} > {$request->descol} was added";
        } elseif ($request->module_id != "-1") {
            $mapping_module = MappingModule::find($request->module_id);
            if (! empty($mapping_module)) {
                $comment_text = "Column mapped to $mapping_module->name";
            } else {
                $comment_text = "Column was mapped";
            }
        } else {
            $comment_text = "Column is mapped";
        }

        // We add a comment to indicate who added the mapping and when
        ProjectColumnComment::create([
            'project_id'   => $project->id,
            'schema_name'  => $schema,
            'table_name'   => $table,
            'column_name'  => $request->column,
            'comment_text' => $comment_text,
            'user_id'      => $request->user()->user_handle,
        ]);

        if (! empty($request->conditions)) {
            ProjectColumnMappingCondition::create([
                'project_column_mapping_id' => $mapping->id,
                'condition'                 => $request->conditions
            ]);
        }

        if ($request->module_id != "-1") {
            $module_fields = MappingModuleField::where('mapping_module_id', $request->module_id)->get();

            if ($module_fields->count() > 0) {
                foreach ($module_fields as $field) {
                    $module_fields_data[] = [
                        "mapping_id"              => $mapping->id,
                        "mapping_module_field_id" => $field->id,
                        "value"                   => $request->module_fields[$field->id]
                    ];
                }
    
                MappingModuleFieldValue::insert($module_fields_data);
            }
        }

        return response()->success();
    }

    #[Can(permission: 'map_write', product: 'studio', id: 'project.id')]
    public function update(Request $request, Project $project, string $schema, string $table, ProjectColumnMapping $mapping)
    {
        $request->validateWithErrors([
            'column' => 'required|string'
        ]);

        if ($project->is_complete) {
            $project->sendCompletedEmail(
                "Mapping",
                "updated for",
                $table,
                $request->column,
                $request->user()->name,
                $request->notes
            );
        }

        $mapping->update([
            "destination_table_name"  => $request->destable,
            "destination_column_name" => $request->descol,
            "notes"                   => $request->notes,
            "notes"                   => $request->notes,
            "mapping_module_id"       => $request->module_id,
        ]);

        if (! empty($request->destable)) {
            $comment_text = "Column mapped to {$request->destable} > {$request->descol} was edited";
        } elseif ($request->module_id != "-1") {
            $mapping_module = MappingModule::find($request->module_id);
            if (! empty($mapping_module)) {
                $comment_text = "Column mapped to $mapping_module->name was edited";
            } else {
                $comment_text = "Column was mapped was edited";
            }
        } else {
            $comment_text = "Column is mapped was edited";
        }

        // We add a comment to indicate who added the mapping and when
        ProjectColumnComment::create([
            'project_id'   => $project->id,
            'schema_name'  => $schema,
            'table_name'   => $table,
            'column_name'  => $request->column,
            'comment_text' => $comment_text,
            'user_id'      => $request->user()->user_handle,
        ]);

        if (! empty($request->conditions)) {
            ProjectColumnMappingCondition::where('project_column_mapping_id', $mapping->id)->update([
                'condition' => $request->conditions
            ]);
        } else {
            ProjectColumnMappingCondition::where('project_column_mapping_id', $mapping->id)->delete();
        }

        if ($request->module_id != "-1") {
            $module_fields = MappingModuleField::where('mapping_module_id', $request->module_id)->get();

            if ($module_fields->count() > 0) {
                foreach ($module_fields as $field) {
                    MappingModuleFieldValue::where('mapping_id', $mapping->id)
                        ->where('mapping_module_field_id', $field->id)
                        ->update(["value" => $request->module_fields[$field->id]]);
                }
            }
        }

        return response()->success();
    }

    #[Can(permission: 'map_write', product: 'studio', id: 'project.id')]
    public function programming(Request $request, Project $project, string $schema, string $table, ProjectColumnMapping $mapping)
    {
        $request->validateWithErrors([
            'is_programmed' => 'required|boolean'
        ]);

        $result = $mapping->update(["is_programmed" => $request->is_programmed]);

        if ($result) {
            return response()->success();
        }

        return response()->error("Failed to update mapping programming");
    }

    #[Can(permission: 'map_read', product: 'studio', id: 'project.id')]
    public function list(Request $request, Project $project, string $schema, string $table)
    {
        $mappings = [];
        ProjectColumnMapping::where('project_id', $project->id)
            ->where('schema_name', $schema)
            ->where('source_table_name', $table)
            ->with('module', 'fields')
            ->get()
            ->each(function ($mapping) use (&$mappings) {
                $mapping->module_fields = $mapping->fields->mapWithKeys(function ($field) use ($mapping) {
                    return [$field->id => ["name" => $field->name, "value" => $field->value($mapping->id)]];
                });

                $mappings[$mapping->source_table_name . '_' . $mapping->source_column_name][] = $mapping;
            });

        return response()->success($mappings);
    }

    #[Can(permission: 'map_read', product: 'studio', id: 'project.id')]
    public function fullTable(Request $request, Project $project, string $schema, string $table)
    {
        $ordinals = Table::ordinals($project->primary_database, $schema, $table);
        $mappings = ProjectColumnMapping::fullTableMap($project, $schema, $table, $ordinals);

        return response()->success($mappings);
    }

    #[Can(permission: 'map_write', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project, string $schema, string $table, ProjectColumnMapping $mapping)
    {
        $mapping->delete();

        if ($project->is_complete) {
            $project->sendCompletedEmail("Mapping", "removed from", $table, $request->column, $request->user()->name);
        }

        ProjectColumnComment::create([
            'project_id'   => $project->id,
            'schema_name'  => $schema,
            'table_name'   => $table,
            'column_name'  => $request->column,
            'comment_text' => "Column mapping with an id of " . $mapping->id . " soft-deleted.",
            'user_id'      => $request->user()->user_handle,
        ]);

        return response()->success();
    }
}
