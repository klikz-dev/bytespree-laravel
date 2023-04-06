<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationSqlUser;
use App\Classes\ServerProviders\DigitalOcean;
use Illuminate\Support\Facades\Storage;
use App\Attributes\Can;

class SqlUserController extends Controller
{
    public function show(Request $request, PartnerIntegration $database)
    {
        $user = $database->sqlUser;
        $exists = TRUE;

        if (! $user) {
            $user = [
                'id'       => 0,
                'username' => '',
                'password' => ''
            ];
            $exists = FALSE;
        }

        return response()->success(
            compact('user', 'exists')
        );
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function create(Request $request, PartnerIntegration $database)
    {
        $username = $database->generateReadOnlyUser();

        if (empty($username)) {
            return response()->error("Username could not be generated", [], 500);
        }

        return response()->success(compact('username'), "Username generated");
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function destroy(Request $request, PartnerIntegration $database)
    {
        // $check_perms = $this->checkPerms("grant_sql_access", $control_id, 'warehouse');
        // if (! $check_perms) {
        //     return;
        // }

        if (! $request->filled('id')) {
            return response()->error("Cannot delete user - user ID was missing", [], 400);
        }

        $sql_user = PartnerIntegrationSqlUser::find($request->id);

        if (! $sql_user) {
            return response()->error("Read-only user not found", [], 400);
        }

        $database->dropSqlUser($sql_user);

        return response()->success([], "Readonly user information has been saved");
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function update(Request $request, PartnerIntegration $database)
    {
        // $check_perms = $this->checkPerms("grant_sql_access", $control_id, 'warehouse');
        // if (! $check_perms) {
        //     return;
        // }

        $sql_user = PartnerIntegrationSqlUser::find($request->id);

        if (! $sql_user) {
            return response()->error("Read-only user not found", [], 400);
        }
        
        $database->updateSqlUserPassword($sql_user, $request->password);

        return response()->success([], "Readonly user information has been saved");
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function store(Request $request, PartnerIntegration $database)
    {
        // $check_perms = $this->checkPerms("grant_sql_access", $control_id, 'warehouse');
        // if (! $check_perms) {
        //     return;
        // }

        $database->createSqlUser($request->username, $request->password);

        return response()->success([], "Readonly user information has been saved");
    }

    #[Can(permission: 'grant_sql_access', product: 'datalake', id: 'database.id')]
    public function certificate(Request $request, PartnerIntegration $database)
    {
        $certificate = app(DigitalOcean::class)->certificate($database->server);

        if (empty($certificate)) {
            return redirect('/data-lake?message=' . urlencode('Could not retrieve certificate.') . '&message_type=danger');
        }

        return response()->streamDownload(function () use ($certificate) {
            echo base64_decode($certificate);
        }, 'ca-certificate.crt');
    }
}
