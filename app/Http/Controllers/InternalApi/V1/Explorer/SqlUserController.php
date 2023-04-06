<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Attributes\Can;
use App\Classes\Database\SqlUser;
use App\Http\Controllers\Controller;
use App\Models\Explorer\Project;
use App\Models\PartnerIntegrationSqlUser;
use Exception;
use Illuminate\Http\Request;

class SqlUserController extends Controller
{
    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function show (Request $request, Project $project)
    {
        return response()->success(
            data: [
                'user' => [
                    'username' => $project->sql_user?->username,
                    'password' => $project->sql_user?->password,
                ],
                'connectivity_info' => [
                    'database_id' => $project->primary_database->id,
                    'database'    => $project->primary_database->database,
                    'host'        => $project->primary_database->server->hostname,
                    'port'        => $project->primary_database->server->port,
                ],
                'server_has_certificate' => $project->primary_database->server->has_certificate,
            ],
            message: 'SQL user retrieved'
        );
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function store(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'password' => 'required|string|min:8'
        ]);

        try {
            $username = $project->generateSqlUsername();

            $sql_user = $project->createSqlUser($username, $request->password);

            return response()->success(
                data: [
                    'user' => [
                        'username' => $sql_user->username,
                        'password' => $sql_user->password,
                    ],
                    'connectivity_info' => [
                        'database_id' => $project->primary_database->id,
                        'database'    => $project->primary_database->database,
                        'host'        => $project->primary_database->server->hostname,
                        'port'        => $project->primary_database->server->port,
                    ]
                ],
                message: 'SQL user created successfully'
            );
        } catch (Exception $e) {
            return response()->error(message: 'SQL user creation failed');
        }
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function destroy(Request $request, Project $project)
    {
        if (! empty($project->sql_user)) {
            PartnerIntegrationSqlUser::where('project_id', $project->id)->each(function ($user) use ($project) {
                $project->primary_database->dropSqlUser($user);
                $user->delete();
            });
        }

        return response()->success(message: 'SQL user deleted');
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function update(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'password' => 'required|string|min:8'
        ]);

        try {
            app(SqlUser::class)->setPassword($project->primary_database, $project->sql_user->username, $request->password);
            
            $project->sql_user->password = $request->password;
            $project->sql_user->save();

            return response()->success(
                data: [
                    'user' => [
                        'username' => $project->sql_user->username,
                        'password' => $project->sql_user->password,
                    ],
                    'connectivity_info' => [
                        'database_id' => $project->primary_database->id,
                        'database'    => $project->primary_database->database,
                        'host'        => $project->primary_database->server->hostname,
                        'port'        => $project->primary_database->server->port,
                    ]
                ],
                message: 'SQL user updated'
            );
        } catch (Exception $e) {
            return response()->error(message: 'SQL user update failed');
        }
    }
}
