<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use App\Classes\IntegrationJenkins;
use App\Models\IntegrationScheduleType;
use App\Models\PartnerIntegration;
use App\Models\PartnerIntegrationSchedule;
use App\Models\PartnerIntegrationSchedulePropertyValue;
use App\Attributes\Can;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function store(Request $request, PartnerIntegration $database)
    {
        if (! $database->integration_id) {
            return response()->success();
        }

        $input_properties = collect($request->properties)->mapWithKeys(function ($property) {
            return [$property['id'] => $property['value']];
        })->toArray();

        $schedule_type = IntegrationScheduleType::find($request->schedule_type_id);

        if (! $schedule_type) {
            return response()->error('Invalid schedule type');
        }

        $schedule = PartnerIntegrationSchedule::create([
            'partner_integration_id' => $database->id,
            'schedule_type_id'       => $schedule_type->id
        ]);

        $schedule_type->properties->each(function ($property) use ($input_properties, $schedule) {
            if (array_key_exists($property->id, $input_properties)) {
                PartnerIntegrationSchedulePropertyValue::create([
                    'schedule_id'               => $schedule->id,
                    'schedule_type_property_id' => $property->id,
                    'value'                     => $input_properties[$property->name]
                ]);
            }
        });

        // $this->IntegrationJenkinsModel->updateSchedule($control_id, $schedule); // todo

        return response()->success([], 'Schedule has been saved');
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function update(Request $request, PartnerIntegration $database)
    {
        // $check_perms = $this->checkPerms("manage_settings", $control_id, 'warehouse');
        // if (! $check_perms) {
        //     return;
        // }

        // $schedule = $this->input->json_array();
        // if (empty($schedule)) {
        //     $this->_sendAjax('error', 'Body was empty', [], 400);

        //     return;
        // }

        if ($request->id == '0') {
            return $this->store($request, $database);
        }

        $schedule = PartnerIntegrationSchedule::find($request->id);

        $input_properties = collect($request->properties)->mapWithKeys(function ($property) {
            return [$property['id'] => $property['value']];
        })->toArray();

        if ($schedule->schedule_type_id != $request->schedule_type_id) {
            $schedule->update(['schedule_type_id' => $request->schedule_type_id]);

            PartnerIntegrationSchedulePropertyValue::where('schedule_id', $schedule->id)->delete();
        }
        
        $schedule->scheduleType->properties->each(function ($property) use ($input_properties, $schedule) {
            if (array_key_exists($property->id, $input_properties)) {
                PartnerIntegrationSchedulePropertyValue::updateOrCreate(
                    [
                        'schedule_id'               => $schedule->id,
                        'schedule_type_property_id' => $property->id,
                    ],
                    [
                        'value' => $input_properties[$property->id]
                    ]
                );
            }
        });
    
        $jenkins_schedule = [
            'name'             => $schedule->scheduleType->name,
            'schedule_type_id' => $schedule->schedule_type_id,
            'properties'       => $request->properties
        ];
        app(IntegrationJenkins::class)->updateSchedule($database, $jenkins_schedule);

        return response()->success([], 'Schedule has been saved');
    }

    public function values(Request $request, PartnerIntegration $database, PartnerIntegrationSchedule $schedule)
    {
        $values = $schedule->values->mapWithKeys(function ($value) {
            return [$value->schedule_type_property_id => $value->value];
        })->toArray();

        $properties = $schedule->scheduleType->properties->map(function ($property) use ($values) {
            if (array_key_exists($property->id, $values)) {
                $property->value = $values[$property->id];
            } else {
                $property->value = NULL;
            }

            return $property;
        });

        return response()->success($properties);
    }
}
