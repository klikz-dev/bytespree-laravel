<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\IntegrationJenkins;
use App\Models\SftpSite;
use App\Models\Explorer\PublishingDestination;
use App\Models\Explorer\ProjectPublishingSchedule;
use DB;

class SftpController extends Controller
{
    public function list()
    {
        $destination = PublishingDestination::where('class_name', 'Sftp')->first();

        $sites = SftpSite::get()->map(function ($site) use ($destination) {
            if (! empty($site->data->password)) {
                unset($site->data->password);
            }
            $site->publishers = ProjectPublishingSchedule::getCountsForDestination($destination, $site->id, 'site_id');

            return $site;
        });

        return response()->success($sites);
    }

    public function create(Request $request)
    {
        $request->validate([
            'hostname' => 'required',
            'port'     => 'required',
            'username' => 'required',
            'password' => 'required'
        ]);

        $test = $this->testSite($request->hostname, $request->port, $request->username, $request->password, $request->default_path);
        if ($test !== TRUE) {
            return response()->error($test);
        }
        
        SftpSite::create($request->all());

        return response()->success();
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'hostname' => 'required',
            'port'     => 'required',
            'username' => 'required'
        ]);

        $site = SftpSite::find($id);

        $data = [
            "hostname"     => $request->hostname,
            "port"         => $request->port,
            "username"     => $request->username,
            "default_path" => $request->default_path
        ];

        $data["password"] = empty($request->password) ? $site->password : $request->password;

        $test = $this->testSite($request->hostname, $request->port, $request->username, $data['password'], $request->default_path);
        if ($test !== TRUE) {
            return response()->error($test);
        }

        $site->update($request->all());

        return response()->success();
    }

    public function destroy(int $id)
    {
        $destination = PublishingDestination::where('class_name', 'Sftp')->first();
        $schedules = ProjectPublishingSchedule::where(DB::raw("destination_options->>'site_id'"), $id)
            ->where('destination_id', $destination->id)
            ->get();

        foreach ($schedules as $schedule) {
            app(IntegrationJenkins::class)->deletePublisherJob($schedule, $schedule->project, $schedule->schema_name, $schedule->table_name);
            $schedule->delete();
        }

        SftpSite::find($id)->delete();

        return response()->empty();
    }

    public function testSite(string $hostname, string $port, string $username, string $password, string|NULL $default_path)
    {
        $connection = @ssh2_connect($hostname, $port);

        if ($connection === FALSE) {
            return "A connection to $hostname could not be established.";
        }

        $auth = @ssh2_auth_password($connection, $username, $password);

        if (! $auth) {
            return "Authentication failed. Double check username and password.";
        }

        $sftp = @ssh2_sftp($connection);

        if ($sftp === FALSE) {
            return "There was a problem establishing an SFTP connection.";
        }

        $statinfo = @ssh2_sftp_stat($sftp, $default_path);

        if ($statinfo == FALSE) {
            return "Failed to find path";
        }

        // if (! is_writable('ssh2.sftp://' . $sftp . $default_path)) {
        //     return "Directory exists but is not writable";
        // }

        return TRUE;
    }
}
