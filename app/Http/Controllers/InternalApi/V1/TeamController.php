<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;

class TeamController extends Controller
{
    public function index()
    {
        return response()->success(
            app('orchestration')->getTeamByDomain(session()->get('team'))
        );
    }
}
