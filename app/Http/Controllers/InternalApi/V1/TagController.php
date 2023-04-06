<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use App\Models\Manager\Tag;

class TagController extends Controller
{
    public function list()
    {
        return response()->success(
            Tag::all()
        );
    }
}
