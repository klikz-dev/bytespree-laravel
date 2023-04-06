<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use App\Models\Explorer\MappingModule;
use App\Attributes\Can;

class MappingModuleController extends Controller
{
    #[Can(permission: 'map_read', product: 'studio', id: 'project.id')]
    public function list()
    {
        return response()->success(MappingModule::with('fields')->get());
    }
}
