<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use App\Models\IntegrationScheduleType;

class ScheduleController extends Controller
{
    public function types()
    {
        return response()->success(
            IntegrationScheduleType::all()
        );
    }

    public function properties(IntegrationScheduleType $schedule)
    {
        return response()->success(
            $schedule->properties
        );
    }
}
