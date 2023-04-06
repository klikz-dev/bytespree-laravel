<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class RequestMacroServiceProvider extends ServiceProvider
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
        Request::macro('validateWithErrors', function (array $validation_rules = []) {
            $validator = Validator::make(
                request()->all(),
                $validation_rules
            );

            if ($validator->fails()) {
                response()->error('', ['errors' => $validator->errors()])->throwResponse();
            }
        });
    }
}
