<?php

namespace Nanicas\Auth\Frameworks\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Nanicas\Auth\Services\ThirdPartyAuthService;
use Nanicas\Auth\Helpers\LaravelAuthHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThirdPartyAuthService::class, function (Application $app) {
            $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);
            $service = new ThirdPartyAuthService($config);
            return $service;
        });
    }
}
