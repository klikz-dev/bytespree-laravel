<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Classes\IntegrationJenkins;
use App\Http\Controllers\Controller;
use App\Models\Manager\ImportedTable;
use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationSchedule;
use App\Models\PartnerIntegrationSetting;
use Illuminate\Http\Request;
use Auth;
use DB;
use Exception;
use App\Attributes\Can;

class ConversionController extends Controller
{
    #[Can(permission: 'datalake_create', product: 'datalake')]
    public function convertToBasic(Request $request, PartnerIntegration $database)
    {
        try {
            DB::transaction(function () use ($database) {
                $tables = $database->tables;

                if ($database->use_tables) {
                    $database->tables->each(function ($table) {
                        $table->delete();
                    });
                } else {
                    PartnerIntegrationSetting::where('partner_integration_id', $database->id)->delete();
                    PartnerIntegrationSchedule::where('partner_integration_id', $database->id)->delete();
                }

                // Removes the database from jenkins
                app(IntegrationJenkins::class)->deleteIntegration($database);

                // Add the tables as imported tables.
                foreach ($tables as $table) {
                    ImportedTable::updateOrCreate([
                        'control_id' => $database->id,
                        'table_name' => $table->name,
                        'user_id'    => Auth::user()->user_handle,
                    ]);
                }

                // Finally, set our database's integration_id to 0.
                $database->update(['integration_id' => 0]);
            });
        } catch (Exception $e) {
            logger()->error(
                'There was a problem converting a database to a basic database.',
                [
                    'partner_integration_id' => $database->id,
                    'exception_message'      => $e->getMessage(),
                    'user'                   => Auth::user()->user_handle
                ]
            );

            return response()->error('There was a problem converting this database to a basic database.');
        }

        return response()->success(message: 'The database has been successfully converted to a basic database.');
    }
}
