<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;

class CrumbController extends Controller
{
    public function __invoke()
    {
        return response()->success(session()->get('breadcrumbs') ?? [], '');
    }
}
