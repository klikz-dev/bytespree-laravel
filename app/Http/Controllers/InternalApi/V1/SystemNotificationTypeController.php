<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationType;

class SystemNotificationTypeController extends Controller
{
    public function index()
    {
        return response()->success(
            NotificationType::all()
        );
    }
}
