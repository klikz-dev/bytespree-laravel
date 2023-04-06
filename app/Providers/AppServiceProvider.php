<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Classes\Orchestration;
use App\Classes\Environment;
use App\Classes\Networking;
use App\Classes\Jenkins;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        // Load up Orchestration API class. app('orchestration')->myMethod()
        $this->app->singleton('orchestration', function () {
            return new Orchestration(config('orchestration.url'), config('orchestration.api_key'));
        });

        // Load up Environment class. app('environment')->myMethod()
        $this->app->singleton('environment', function () {
            return new Environment();
        });

        // Load up Networking class. app('networking')->myMethod()
        $this->app->singleton('networking', function () {
            return new Networking();
        });

        // Load up Jenkins class. app('jenkins')->myMethod()
        $this->app->singleton('jenkins', function () {
            return new Jenkins();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
