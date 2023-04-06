<?php

namespace App\Http\Controllers\InternalApi\V1\Admin;

use App\Http\Controllers\Controller;
use App\Classes\Connector;
use App\Models\Integration;
use Illuminate\Http\Request;
use Exception;

class ConnectorController extends Controller
{
    public function available()
    {
        $available_connectors = app('orchestration')->getTeamConnectors(app('environment')->getTeam());

        $installed_connectors = Integration::get();

        $connectors = [];

        foreach ($available_connectors as $teamConnector) {
            $teamConnector['visible'] = TRUE;
            $teamConnector['installed'] = $installed_connectors->where('name', $teamConnector['name'])->count() > 0;
            
            if (empty($teamConnector['version'])) {
                $teamConnector['version'] = "0.0.0";
            }

            $connectors[$teamConnector['name']] = $teamConnector;
        }

        return response()->success(
            array_values($connectors)
        );
    }

    public function destroy(Integration $connector)
    {
        if ($connector->databases->count() > 0) {
            return response()->error(message: 'This connector is currently in use and cannot be removed.');
        }
        
        $connector->delete();

        return response()->success(message: 'The connector was successfully removed');
    }

    public function store(Request $request)
    {
        try {
            $connector = app(Connector::class)->install($request->id);
        } catch (Exception $e) {
            return response()->error(message: $e->getMessage());
        }

        return response()->success(message: "{$connector->name} installed successfully");
    }

    public function update(int $connector_id)
    {
        try {
            $connector = app(Connector::class)->update($connector_id);
        } catch (Exception $e) {
            return response()->error(message: $e->getMessage(), status_code: 500);
        }

        return response()->success(message: "{$connector->name} updated successfully");
    }
}
