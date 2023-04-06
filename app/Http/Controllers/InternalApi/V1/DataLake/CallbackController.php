<?php

namespace App\Http\Controllers\InternalApi\V1\DataLake;

use App\Http\Controllers\Controller;
use App\Models\IntegrationCallback;
use App\Models\PartnerIntegration;
use Illuminate\Http\Request;
use App\Attributes\Can;

class CallbackController extends Controller
{
    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function store(Request $request, PartnerIntegration $database)
    {
        $callback = IntegrationCallback::create([
            'control_id'   => $database->id,
            'callback_url' => $request->callback_url,
            'key'          => $request->callback_key
        ]);

        return response()->success($callback, 'Callback created');
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function destroy(Request $request)
    {
        IntegrationCallback::where('id', $request->callback_id)->delete();

        return response()->success([], 'Callback deleted.');
    }

    #[Can(permission: 'manage_settings', product: 'datalake', id: 'database.id')]
    public function update(Request $request, PartnerIntegration $database)
    {
        $callback = IntegrationCallback::find($request->callback_id);

        $callback->update([
            'callback_url' => $request->callback_url,
            'key'          => $request->callback_key
        ]);

        return response()->success([], 'Callback updated.');
    }
}
