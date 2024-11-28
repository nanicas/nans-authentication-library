<?php

namespace Nanicas\Auth\Frameworks\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Nanicas\Auth\Helpers\LaravelAuthHelper;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Illuminate\Contracts\Foundation\Application;
use Nanicas\Auth\Contracts\AuthenticationClient;
use Nanicas\Auth\Services\ThirdPartyAuthService;
use Nanicas\Auth\Frameworks\Laravel\Console\Commands\GeneratePersonalToken;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthenticationClient::class, function (Application $app) {
            $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);
            return $app->make($config['DEFAULT_AUTHENTICATION_CLIENT']);
        });

        $this->app->bind(AuthorizationClient::class, function (Application $app) {
            $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);
            return $app->make($config['DEFAULT_AUTHORIZATION_CLIENT']);
        });

        $this->commands([
            GeneratePersonalToken::class,
        ]);
    }
}
