<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectHyperlink;
use App\Models\Explorer\ProjectAttachment;
use App\Models\Explorer\ProjectColumnFlag;
use App\Models\Explorer\ProjectColumnMapping;
use App\Classes\Postmark;
use App\Classes\Database\Table;

class ExportProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:project {project_id} {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports project data to a csv';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $project = Project::find($this->argument('project_id'));
        $tables = collect(Table::list($project->primary_database, [$project->name]))
            ->filter(function ($table) use ($project) {
                if ($table->table_type == 'Table' || $table->table_type == 'Custom Table') {
                    $snapshot_check = $project->snapshots->filter(function ($snapshot) use ($table) {
                        return $snapshot->source_schema == $table->table_schema && $snapshot->source_table == $table->table_name;
                    });

                    if ($snapshot_check->count() == 0) {
                        return $table;
                    }
                }
            });

        $path = rtrim(config('app.attach_directory'), '/') . "/schema_{$project->id}_" . uniqid() . ".csv";

        $fp = fopen($path, 'w');

        fputcsv($fp, ['Table', 'Column', 'Sample Value 1', 'Sample Value 2', 'Sample Value 3', 'Flag Owner', 'Flag Comment', 'Mapping Table & Column', 'Mapping Module', 'Mapping Module Data', 'Mapping Notes', 'Mapping Condition']);

        foreach ($tables as $table) {
            $table_name = $table->table_name;
            $schema_name = $table->table_schema;
            $columns = Table::columns($project->primary_database, $schema_name, $table_name);
            foreach ($columns as $column) {
                $item = [];
                $column_name = $column->column_name;

                $item['table'] = $table_name;
                $item['column'] = $column_name;

                $records = Table::randomColumnValues($project->primary_database, $schema_name, $table_name, $column_name, 3);

                $item['sample1'] = "";
                $item['sample2'] = "";
                $item['sample3'] = "";
                if (! empty($records)) {
                    for ($i = 0; $i < 3; ++$i) {
                        if (isset($records[$i])) {
                            $item['sample' . ($i + 1)] = $records[$i]->$column_name;
                        } else {
                            $item['sample' . ($i + 1)] = '';
                        }
                    }
                }

                $item['flag_owner'] = "";
                $item['flag_comment'] = "";
                $flag = ProjectColumnFlag::where('project_id', $project->id)
                    ->where('schema_name', $schema_name)
                    ->where('table_name', $table_name)
                    ->where('column_name', $column_name)
                    ->first();

                if (! empty($flag)) {
                    $item['flag_owner'] = $flag->user_id;
                    $item['flag_comment'] = strip_tags($flag->flag_reason);
                }

                $item['mapping_tc'] = "";
                $item['mapping_mod'] = "";
                $item['mapping_mod_data'] = "";
                $item['mapping_notes'] = "";
                $item['mapping_condition'] = "";

                $mapping = ProjectColumnMapping::where('project_id', $project->id)
                    ->where('schema_name', $schema_name)
                    ->where('source_table_name', $table_name)
                    ->where('source_column_name', $column_name)
                    ->first();

                if (! empty($mapping)) {
                    if ($mapping->destination_table_name != "") {
                        $item['mapping_tc'] = $mapping->destination_table_name . " -> " . $mapping->destination_column_name;
                    }

                    if (! empty($mapping->module)) {
                        $item['mapping_mod'] = $mapping->module->name;
                        $count = 0;
                        foreach ($mapping->getModuleMappings($mapping->id, $mapping->mapping_module_id) as $data) {
                            if ($count > 0) {
                                $item['mapping_mod_data'] .= "\n";
                            }
                            $item['mapping_mod_data'] .= $data->mapping_module_field_name . ": " . $data->value;
                            ++$count;
                        }
                    }

                    $item['mapping_notes'] = $mapping->notes;
                    if (! empty($mapping->condition)) {
                        $item['mapping_condition'] = $mapping->condition->condition;
                    }
                }

                fputcsv($fp, $item);
            }
        }

        $attachment = ProjectAttachment::create([
            "project_id" => $project->id,
            "user_id"    => $this->argument('username'),
            "path"       => $path,
            "file_name"  => basename($path)
        ]);

        $hyperlink = rtrim(config('app.url'), '/') . "/studio/projects/$project->id/attachments/$attachment->id";

        ProjectHyperlink::create([
            "project_id"  => $project->id,
            "user_id"     => $this->argument('username'),
            "url"         => $hyperlink,
            "name"        => $attachment->file_name,
            "description" => 'Schema Export',
            "type"        => 'file'
        ]);

        $user = User::handle($this->argument('username'));
        Postmark::send(
            $user->email,
            "schema-export",
            [
                "comment_text"    => "Hi " . $user->first_name . " " . $user->last_name . "!",
                "hyper_link_text" => $hyperlink
            ]
        );
    }
}
