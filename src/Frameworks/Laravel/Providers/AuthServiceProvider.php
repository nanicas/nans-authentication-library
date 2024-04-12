<?php

namespace Nanicas\Auth\Frameworks\Laravel\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Nanicas\Auth\Frameworks\Laravel\Guards\CustomSession\CustomGuard;
use Nanicas\Auth\Frameworks\Laravel\Guards\CustomSession\CustomUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Foundation\Application;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::provider('custom_session', function ($app, array $config) {
            return new CustomUserProvider(
                $app->make('hash'),
                $config['model']
            );
        });

        Auth::extend('custom_session', function (Application $app, string $name, array $config) {
            $guard = new CustomGuard(
                $name,
                Auth::createUserProvider($config['provider']),
                $app->make('session.store'),
            );

            /**
             * @ref: https://github.com/illuminate/auth/blob/master/AuthManager.php#L134
             */
            if (method_exists($guard, 'setCookieJar')) {
                $guard->setCookieJar($this->app['cookie']);
            }

            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($this->app['events']);
            }

            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
            }

            if (isset($config['remember'])) {
                $guard->setRememberDuration($config['remember']);
            }
            
            return $guard;
        });
    }
}
