<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Response::macro('success', function ($data = [], string $message = NULL, $status_code = 200) {
            return response()->json([
                'status'  => 'ok',
                'message' => $message,
                'data'    => $data,
            ], $status_code);
        });

        Response::macro('error', function (string $message = NULL, array $data = [], $status_code = 500) {
            $status = 'error';

            return response()
                ->json(compact('status', 'message', 'data'), $status_code);
        });

        Response::macro('empty', function ($status_code = 200) {
            return response()
                ->json([], $status_code);
        });
    }
}