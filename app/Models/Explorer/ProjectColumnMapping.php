<?php

namespace App\Models\Explorer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

/**
 * App\Models\Explorer\ProjectColumnMapping
 *
 * @property        int                                                        $id
 * @property        int                                                        $project_id
 * @property        string                                                     $user_id
 * @property        string|null                                                $notes
 * @property        string|null                                                $source_table_name
 * @property        string|null                                                $source_column_name
 * @property        string|null                                                $destination_table_name
 * @property        string|null                                                $destination_column_name
 * @property        int|null                                                   $mapping_module_id
 * @property        bool|null                                                  $is_deleted
 * @property        \Illuminate\Support\Carbon|null                            $created_at
 * @property        \Illuminate\Support\Carbon|null                            $updated_at
 * @property        bool|null                                                  $is_programmed
 * @property        string|null                                                $schema_name
 * @property        \Illuminate\Support\Carbon|null                            $deleted_at
 * @property        \App\Models\Explorer\ProjectColumnMappingCondition|null    $condition
 * @property        \App\Models\Explorer\MappingModule|null                    $module
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping newQuery()
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnMapping    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping query()
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereDestinationColumnName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereDestinationTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereIsProgrammed($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereMappingModuleId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereNotes($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereProjectId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereSchemaName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereSourceColumnName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereSourceTableName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|ProjectColumnMapping whereUserId($value)
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnMapping    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|ProjectColumnMapping    withoutTrashed()
 * @mixin \Eloquent
 */
class ProjectColumnMapping extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'bp_project_column_mappings';

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($mapping) {
            ProjectColumnMappingCondition::where('project_column_mapping_id', $mapping->id)->delete();
        });
    }

    public function condition()
    {
        return $this->hasOne(ProjectColumnMappingCondition::class);
    }

    public function module()
    {
        return $this->belongsTo(MappingModule::class, 'mapping_module_id');
    }

    public function fields()
    {
        return $this->hasManyThrough(
            MappingModuleField::class,
            MappingModuleFieldValue::class,
            'mapping_id',
            'id',
            'id',
            'mapping_module_field_id'
        );
    }

    public static function fullTableMap(Project $project, string $table_schema, string $table_name, array $ordinals)
    {
        $subselect = <<<SQL
            (SELECT string_agg(comment_text, '<br>') comment_text FROM (
            SELECT project_id, table_name, column_name, comment_text, created_at
            FROM public.bp_project_column_comments
            WHERE project_id = pcm.project_id 
            AND table_name=pcm.source_table_name 
            AND schema_name=pcm.schema_name 
            AND column_name=pcm.source_column_name 
            AND deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT 2) as a
            GROUP BY project_id, table_name, column_name) AS comment_text
            SQL;

        $sql = <<<SQL
            SELECT pcm.id, 
                pcm.source_table_name, 
                pcm.source_column_name AS source_column_name, 
                pcm.destination_table_name, 
                pcm.destination_column_name, 
                pcm.mapping_module_id,  
                pcm.created_at, pcm.notes, 
                pcm.is_programmed, 
                pcm.is_deleted, 
                pcmc.condition, 
                mm.name AS module_name, 
                $subselect
            FROM bp_project_column_mappings AS pcm
            LEFT JOIN bp_project_column_mapping_conditions AS pcmc ON pcm.id = pcmc.project_column_mapping_id AND pcmc.deleted_at IS NULL
            LEFT JOIN bp_mapping_modules mm ON mm.id = pcm.mapping_module_id AND pcmc.deleted_at IS NULL
            WHERE 
                (pcm.is_programmed = true OR pcm.deleted_at IS NULL)  
                AND pcm.project_id = $project->id 
                AND pcm.schema_name = '$table_schema'
                AND pcm.source_table_name = '$table_name'
            ORDER BY source_column_name
            SQL;

        $column_mappings = collect(DB::select($sql))
            ->map(function ($column_mapping) use ($ordinals) {
                if (! empty($ordinals[$column_mapping->source_column_name])) {
                    $column_mapping->ordinal_position = $ordinals[$column_mapping->source_column_name];
                } else {
                    $column_mapping->ordinal_position = '';
                }

                if (! empty($column_mapping->mapping_module_id)) {
                    $column_mapping->module_data = self::getModuleMappings($column_mapping->id, $column_mapping->mapping_module_id);
                }

                $column_mapping->created_at_formatted = '';

                return $column_mapping;
            });

        return $column_mappings;
    }

    public static function getModuleMappings(int $mapping_id, int $mapping_module_id)
    {
        $sql = <<<SQL
            SELECT
                fv.id AS mapping_module_value_id,
                f.id AS mapping_module_field_id,
                f.name AS mapping_module_field_name,
                fv.value
            FROM bp_mapping_module_fields AS f
            LEFT JOIN bp_mapping_module_field_values AS fv
                ON f.id = fv.mapping_module_field_id
                AND fv.mapping_id = '$mapping_id'
                AND fv.is_deleted = false
            WHERE
                f.is_deleted = false and f.mapping_module_id = '$mapping_module_id'
            SQL;

        return DB::select($sql);
    }
}
