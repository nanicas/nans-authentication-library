<?php

namespace Nanicas\Auth\Frameworks\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Nanicas\Auth\Services\ThirdPartyAuthService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThirdPartyAuthService::class, function (Application $app) {
            return new ThirdPartyAuthService();
        });
    }
}
