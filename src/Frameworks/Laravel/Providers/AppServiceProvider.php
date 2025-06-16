<?php

namespace Nanicas\Auth\Frameworks\Laravel\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Nanicas\Auth\Contracts\AuthenticationClient;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Nanicas\Auth\Exceptions\UserNotPermissionableException;
use Nanicas\Auth\Frameworks\Laravel\Console\Commands\GeneratePersonalToken;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;
use Nanicas\Auth\Frameworks\Laravel\Traits\PermissionableSession;
use Nanicas\Auth\Frameworks\Laravel\Traits\PermissionableStateless;

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

        $this->bootGate();
    }

    private function bootGate()
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);
        if (!isset($config['gate']) || !$config['gate']['check_acl_permissions']) {
            return;
        }

        Gate::before(function ($user, $ability) use ($config) {
            $request = request();
            $client = app()->make(AuthorizationClient::class);

            $class = ($config['stateless']) ? PermissionableStateless::class : PermissionableSession::class;
            if (!in_array($class, class_uses_recursive($user))) {
                throw new UserNotPermissionableException();
            }

            $acl = $user->getACLPermissions($request, $client);
            if (!array_key_exists('permissions', $acl)) {
                return false;
            }

            return in_array($ability, $acl['permissions']);
        });
    }
}
