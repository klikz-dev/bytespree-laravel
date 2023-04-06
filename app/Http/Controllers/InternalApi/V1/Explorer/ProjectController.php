<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PartnerIntegration;
use App\Models\{Role, User};
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectSnapshot;
use App\Models\Explorer\ProjectHyperlink;
use App\Models\Explorer\ProjectAttachment;
use App\Models\Explorer\ProjectPublishingSchedule;
use App\Classes\FileUpload;
use App\Classes\IntegrationJenkins;
use App\Classes\Database\Table;
use App\Classes\Database\View;
use App\Classes\Database\ForeignDatabase;
use App\Classes\Database\StudioExplorer;
use App\Attributes\Can;
use Auth;
use Exception;

// Any of these methods outside of list, show, store, or update could easily be in their own controller if that's what we decide to keep bloat down.
class ProjectController extends Controller
{
    public function list(Request $request)
    {
        $user_projects = Auth::user()->studioProjects()->pluck('product_child_id')->toArray();
        $all_foreign_databases = Project::getAllProjectForeignDatabases();

        $projects = Project::with(['tags', 'primary_database', 'primary_database.integration'])
            ->get()
            ->sortBy(['primary_database.integration.name', 'display_name'])
            ->filter(function ($project) use ($user_projects) {
                if (in_array($project->id, $user_projects) || Auth::user()->is_admin) {
                    return $project;
                }
            })
            ->map(function ($project) use ($all_foreign_databases) {
                $foreign_databases = $all_foreign_databases->where('project_id', $project->id)
                    ->map(function ($foreign_database) {
                        return [
                            'database' => $foreign_database->database,
                            'id'       => $foreign_database->id,
                        ];
                    })
                    ->values();

                $project->setRelation('foreign_databases', $foreign_databases);

                return $project;
            });

        if ($request->has('tag')) {
            $projects = $projects->filter(function ($project) use ($request) {
                return $project->tags->where('id', $request->tag)->count() > 0;
            });
        }

        // Grab all admin and users who have access to any projects we have included in our list.
        $all_members = User::getUsersByProductNameAndChildIds('studio', $projects->pluck('id')->toArray());

        // Map the project members based off of the above member list.
        $projects = $projects->map(function ($project) use ($all_members) {
            $members = $all_members->filter(function ($member) use ($project, &$added) {
                return $member->product_child_id === $project->id || $member->is_admin === TRUE;
            })
                ->unique('id')
                ->values();

            $project->setRelation('members', $members);

            return $project;
        });

        return response()->success($projects->values());
    }

    public function show(Request $request, Project $project)
    {
    }

    #[Can(permission: 'studio_create', product: 'studio')]
    public function store(Request $request)
    {
        if (! Auth::user()->hasPermissionTo('studio_create')) {
            return response()->error('Access denied', [], 403);
        }

        $database = PartnerIntegration::find($request->partner_integration_id);

        if (empty($database)) {
            return response()->error('Database not found', [], 400);
        }

        $project_name = strtolower(trim($request->name ?? ''));

        $project_name = preg_replace(['/[^a-z0-9\s_]/mi', '/\s/'], ['', '_'], $project_name);

        if (empty($project_name)) {
            return response()->error("Provide a project name.", [], 400);
        }

        if (mb_strlen($request->display_name) > 200) {
            return response()->error('Project name cannot be longer than 200 characters.', [], 400);
        }

        if (Project::where('name', $request->name)->exists()) {
            return response()->error('A project with that name already exists.', [], 400);
        }

        if (Project::where('display_name', $request->display_name)->exists()) {
            return response()->error('A project with that display name already exists.', [], 400);
        }

        $requested_foreign_databases = is_array($request->foreign_databases) ? $request->foreign_databases : [];

        $foreign_databases = PartnerIntegration::whereIn('id', $requested_foreign_databases)->get();

        if (count($foreign_databases) !== count($requested_foreign_databases)) {
            return response()->error('There was a problem with one or more of the foreign databases you provided.', [], 400);
        }

        $project = Project::createProject(
            $database,
            $request->display_name,
            $project_name,
            $request->description,
            Auth::user(),
            $foreign_databases
        );

        if (! Auth::user()->is_admin) {
            $role = Role::where("role_name", "Project Admin")->first();

            if (empty($role)) {
                return response()->error("Project created but user was not assigned", [], 400);
            }

            Auth::user()->assignRole($role, $project->id);
        }

        return response()->success(['project_id' => $project->id], "Project created");
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function update(Request $request, Project $project)
    {
        if (! Auth::user()->hasPermissionTo('studio_create')) {
            return response()->error('Access denied', [], 403);
        }

        $request->validateWithErrors([
            'display_name' => 'required|string|min:1|max:200',
            'description'  => 'nullable|string',
        ]);

        if (Project::where('display_name', $request->display_name)->where('id', '!=', $project->id)->exists()) {
            return response()->error('A project with that display name already exists.', [], 400);
        }

        // Checking if the user has access to the databases they are trying to add/delete
        $requested_foreign_databases = is_array($request->foreign_databases) ? $request->foreign_databases : [];

        $foreign_databases = PartnerIntegration::whereIn('id', $requested_foreign_databases)->get();

        $modified_foreign_databases = FALSE;

        // Are we adding any foreign databases?
        foreach ($foreign_databases as $foreign_database) {
            if ($project->foreign_databases->where('id', $foreign_database->id)->count() == 0) {
                logger()->info("Adding foreign db {$foreign_database->id} to project {$project->id}");
                if (! Auth::user()->hasPermissionTo('manage_settings', $foreign_database->id, 'datalake')) {
                    return response()->error('You do not have access to one of the databases selected.', [], 400);
                }
                $modified_foreign_databases = TRUE;
            }
        }

        // Are we removing any foreign databases?
        $project_foreign_databases = $project->getForeignDatabaseQuery()
            ->get();

        foreach ($project_foreign_databases as $foreign_database) {
            if ($foreign_databases->where('id', $foreign_database->id)->count() == 0) {
                logger()->info("Removing foreign db {$foreign_database->id} to project {$project->id}");
                if (! Auth::user()->hasPermissionTo('manage_settings', $foreign_database->id, 'datalake')) {
                    return response()->error('You do not have permission to remove the  to one of the databases being removed', [], 400);
                }
                $modified_foreign_databases = TRUE;
            }
        }

        if ($modified_foreign_databases) {
            if (! $project->updateForeignDatabases($foreign_databases)) {
                return response()->error("Project cannot be saved. You may have removed a database that has dependant views.", [], 400);
            }
        }

        $project->update($request->only(['display_name', 'description']));

        return response()->success([], "Project edited");
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function updateDestinationSchema(Request $request, Project $project)
    {
        if (! Auth::user()->hasPermissionTo('studio_create')) {
            return response()->error('Access denied', [], 403);
        }

        $request->validateWithErrors([
            'destination_schema_id' => 'required'
        ]);

        $project->update([
            "destination_schema_id" => $request->destination_schema_id
        ]);

        return response()->success();
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function completed(Project $project)
    {
        $update_value = ! $project->primary_database->is_complete;

        PartnerIntegration::find($project->primary_database->id)
            ->update(['is_complete' => $update_value]);

        return response()->success(['updated' => $update_value]);
    }

    #[Can(permission: 'project_remove', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project)
    {
        $project->delete();

        return response()->empty();
    }

    #[Can(permission: 'comment_read', product: 'studio', id: 'project.id')]
    public function activity(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'page'  => 'required|int',
            'limit' => 'required|int'
        ]);

        $offset = ($request->page - 1) * $request->limit;
        $activity = $project->activity($request->limit, $offset);
        $total_pages = ceil($activity->counts / $request->limit);

        return response()->success(["activity" => $activity->activity, "pages" => $total_pages]);
    }

    #[Can(permission: 'link_write', product: 'studio', id: 'project.id')]
    public function attach(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'transfer_token' => 'required'
        ]);

        $path = rtrim(config('app.attach_directory'), '/');

        if (! is_dir($path)) {
            if (mkdir($path, 0777) === FALSE) {
                return response()->error('Upload directory does not exist');
            }
        }

        try {
            $upload = new FileUpload(config('services.file_upload.url'));

            $meta = $upload->getFileMetadata($request->transfer_token);

            if (empty($meta)) {
                logger()->error('Could not get uploaded file from upload service.');
                throw new Exception('Your file could not be uploaded.');
            }

            $result = $upload->downloadFile($request->transfer_token, $path, $meta->filename);

            if (! $result) {
                throw new Exception("Could not upload file.");
            }

            $upload->deleteFile($request->transfer_token);

            $attachment = ProjectAttachment::create([
                "project_id" => $project->id,
                "user_id"    => auth()->user()->user_handle,
                "path"       => "$path/$meta->filename",
                "file_name"  => $meta->filename
            ]);

            $hyperlink = rtrim(config('app.url'), '/') . "/studio/projects/$project->id/attachments/$attachment->id";

            ProjectHyperlink::create([
                "project_id"  => $project->id,
                "user_id"     => auth()->user()->user_handle,
                "url"         => $hyperlink,
                "name"        => $meta->filename,
                "description" => 'File Attachment',
                "type"        => 'file'
            ]);

            return response()->success();
        } catch (Exception $exception) {
            logger()->error('Could not get uploaded file from upload service.', compact('exception', 'transfer_token'));

            return response()->error($exception->getMessage());
        }
    }

    #[Can(permission: 'export_data', product: 'studio', id: 'project.id')]
    public function export(Request $request, Project $project)
    {
        if (! app('jenkins')->launchFunction("exportToCSV", ["TEAM" => app('environment')->getTeam(), "PROJECT_ID" => $project->id, "USERNAME" => auth()->user()->user_handle])) {
            return response()->error("Failed to start export.");
        }

        return response()->success();
    }

    #[Can(permission: 'flag_read', product: 'studio', id: 'project.id')]
    public function flags(Request $request, Project $project)
    {
        $project->load('flags', 'flags.user');

        return response()->success($project->flags);
    }

    public function details(Project $project)
    {
        return response()->success($project);
    }

    #[Can(permission: 'project_grant', product: 'studio', id: 'project.id')]
    public function roles(Request $request, Project $project)
    {
        $roles = Role::where('role_name', '!=', 'Team Admin')
            ->whereHas('product', function ($q) {
                $q->where('name', 'studio');
            })
            ->get();

        return response()->success($roles);
    }

    public function tables(Request $request, Project $project)
    {
        $get_types = filter_var($request->get_types, FILTER_VALIDATE_BOOLEAN) === TRUE;
        $schemas = $project->getSchemas();
        $schemas = array_merge($schemas, [$project->primary_database->database => $project->name]);

        $db_views = View::list($project->primary_database);
        $db_tables = Table::list($project->primary_database, $schemas);
        $bp_views = $project->views;

        $snapshots = $project->snapshots
            ->map(function ($snapshot) use ($project, $db_tables) {
                $table_key = array_search($snapshot->name, array_column($db_tables, 'table_name'));
                $table = $db_tables[$table_key];

                if (! empty($table) && $table->table_schema == $project->name) {
                    $snapshot->num_records = $table->num_records;
                    $snapshot->total_size = $table->total_size;
                    $snapshot->schema = $table->table_schema;
                }

                return $snapshot;
            })
            ->toArray();

        if ($get_types === TRUE && count($schemas) > 1) {
            $db_tables = ForeignDatabase::types($project->primary_database, $schemas, $db_tables);
        }

        $tables = [];

        foreach ($db_tables as $table) {
            if (($table->table_type == "View" || $table->table_type == "Materialized View") && $table->table_schema == $project->name) {
                foreach ($bp_views as $key => $view) {
                    if ($table->table_name == $view->view_name && $table->table_schema == $view->view_schema) {
                        $table->view_definition = $view->view_definition_sql;
                        $table->view_type = $view->view_type ?? 'normal';
                        $table->exists = TRUE;
                        $table->synchronized = FALSE;
                        unset($bp_views[$key]);
                    }
                }

                foreach ($db_views as $view) {
                    if ($table->table_name == $view->view_name && $table->table_schema == $view->view_schema) {
                        $table->synchronized = TRUE;
                        if (property_exists($table, 'view_type') && ($table->view_type != $view->view_type || $table->view_definition != $view->view_definition)) {
                            $table->synchronized = FALSE;
                        }
                    }
                }
            }

            if (! in_array($table->table_name, array_column($snapshots, "name")) || $table->table_schema != $project->name) {
                $tables[] = $table;
            }
        }

        foreach ($bp_views as $view) {
            $table = [
                "table_name"         => $view->view_name,
                "table_schema"       => $view->view_schema,
                "table_type"         => $view->view_type == 'normal' ? 'View' : 'Materialized View',
                "table_catalog"      => $project->primary_database->database,
                "num_records"        => NULL,
                "size_in_bytes_sort" => "0",
                "total_size"         => "0 bytes",
                "view_definition"    => $view->view_definition,
                "view_type"          => $view->view_type,
                "exists"             => FALSE,
                "synchronized"       => FALSE
            ];

            $tables[] = $table;
        }

        return response()->success(["tables" => $tables, "snapshots" => $snapshots]);
    }

    // todo fix this when explorer code is out and getting all columns is already in
    public function searchColumns(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'term' => 'required',
        ]);

        $column = base64_decode($request->term);

        $columns = app(StudioExplorer::class)->searchColumns(
            $project,
            $project->primary_database,
            $column
        );

        if ($columns->count() == 0) {
            return response()->success(["found" => FALSE]);
        }

        return response()->success(["found" => TRUE, "columns" => $columns]);
    }

    #[Can(permission: 'studio_create', product: 'studio')]
    public function suggestSchema(Request $request)
    {
        $display_name = strtolower($request->display_name ?? '');

        // Remove or replace characters...
        $name = trim(preg_replace('/[^a-z0-9\s_]/mi', '', $display_name));

        $name = preg_replace('/\s/', '_', $name);

        // If the name is now only underscores, set it to blank
        if ($name == str_repeat('_', mb_strlen($name))) {
            $name = '';
        }

        // If all characters were removed (e.g. display name of 💰🤓), create s_%Y_%m_%d formatted string
        if (mb_strlen($name) == 0) {
            $name = date('\s_Y_m_d');
        }

        if (! preg_match('/^[a-z]/', $name)) {
            if (mb_substr($name, 0, 1) != '_') {
                $name = '_' . $name;
            }

            $name = 's' . $name; // Start the generated name w/"s_"
        }

        $projects = Project::get()->pluck('name')->toArray();

        $name = mb_substr($name, 0, 50);

        // If project name is taken, append _2, see if it's taken, append _3, see if it's taken, etc.
        if (in_array($name, $projects)) {
            for ($append = 2; $append < 1000; ++$append) {
                $possible_name = $name . '_' . $append;
                if (! in_array($possible_name, $projects)) {
                    $name = $possible_name;
                    break;
                }
            }
        }

        return response()->success(['suggested_name' => $name]);
    }

    // todo: idk about this one. Should probably have its own controller with other snapshot related methods.
    // John - I agree I'll probably move it when I do the publisher stuff
    
    public function deleteSnapshot(Request $request, Project $project, ProjectSnapshot $snapshot)
    {
        // if ($snapshot->user_id != auth()->user()->user_handle) {
        //     // todo What should we do about this?
        //     $check_perms = $this->checkPerms("project_manage", $project_id);
        //     if (! $check_perms) {
        //         return;
        //     }
        // }
        $request->validateWithErrors([
            'ignore_warning' => 'required'
        ]);

        $ignore_warning = filter_var($request->ignore_warning, FILTER_VALIDATE_BOOLEAN);

        $views = View::get($project->primary_database, $project->name, $snapshot->name);
        $schedules = ProjectPublishingSchedule::where('project_id', $project->id)
            ->where('schema_name', $project->name)
            ->where('table_name', $snapshot->name)
            ->get();
        
        if (! empty($views)) {
            return response()->error('Snapshot has dependent views. You must delete these to delete the snapshot.');
        } else if ($schedules->count() > 0 && $ignore_warning == FALSE) {
            return response()->success('warning', "$snapshot->name has a scheduled publish job. Deleting this table will also delete the publishing job. Do you want to continue?");
        }

        if (! Table::drop($project->primary_database, $project->name, $snapshot->name)) {
            return response()->error('Snapshot failed to drop');
        }

        $snapshot->delete();

        if ($schedules->count() > 0) {
            app(IntegrationJenkins::class)->removePublishJobForProject($project, $project->name, $snapshot->name);

            foreach ($schedules as $schedule) {
                $schedule->delete();
            }
        }

        return response()->empty();
    }
}
