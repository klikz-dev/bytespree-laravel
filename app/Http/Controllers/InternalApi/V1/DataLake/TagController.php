<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerIntegration;
use App\Models\Manager\DatabaseTag;
use App\Attributes\Can;

class TagController extends Controller
{
    #[Can(permission: 'tag_write', product: 'datalake', id: 'database.id')]
    public function store(Request $request, PartnerIntegration $database)
    {
        DatabaseTag::updateOrCreate([
            'control_id' => $database->id,
            'tag_id'     => $request->tag_id,
        ]);

        return response()->success(
            $database->tags,
            'Added tag'
        );
    }

    #[Can(permission: 'tag_write', product: 'datalake', id: 'database.id')]
    public function destroy(Request $request, PartnerIntegration $database)
    {
        DatabaseTag::where([
            'control_id' => $database->id,
            'tag_id'     => $request->tag_id,
        ])->delete();

        return response()->success(
            $database->tags,
            'Removed tag'
        );
    }
}
