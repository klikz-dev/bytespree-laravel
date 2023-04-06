<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->success(
            app('orchestration')->getUserNotifications(session()->get('username'), $request->last_id ?? 0)
        );
    }

    public function dismiss(Request $request)
    {
        if ($request->has('id')) {
            return response()->success(
                app('orchestration')->dismissNotification(session()->get('username'), $request->id)
            );
        }
  
        return response()->success(
            app('orchestration')->dismissAllNotifications(session()->get('username'))
        );
    }

    public function read(Request $request)
    {
        if ($request->has('id')) {
            return response()->success(
                app('orchestration')->markNotificationRead(session()->get('username'), $request->id)
            );
        }
  
        return response()->success(
            app('orchestration')->markAllNotificationsRead(session()->get('username'))
        );
    }
}
