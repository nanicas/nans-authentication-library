<?php

namespace Nanicas\Auth\Frameworks\Laravel\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Nanicas\Auth\Contracts\AuthenticationClient;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Nanicas\Auth\Frameworks\Laravel\Console\Commands\GeneratePersonalToken;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthenticationClient::class, function (Application $app) {
            $config = config(AuthHelper::CONFIG_FILE_NAME);
            return $app->make($config['DEFAULT_AUTHENTICATION_CLIENT']);
        });

        $this->app->bind(AuthorizationClient::class, function (Application $app) {
            $config = config(AuthHelper::CONFIG_FILE_NAME);
            return $app->make($config['DEFAULT_AUTHORIZATION_CLIENT']);
        });

        $this->commands([
            GeneratePersonalToken::class,
        ]);
    }

    public function boot()
    {
        $src = __DIR__ . '/../../../..';

        $this->publishes([
            $src . '/config' => config_path(),
        ], 'nanicas_auth:config');
    }
}
