<?php

namespace Nanicas\Frameworks\Laravel\Providers;

use Illuminate\Support\ServiceProvider;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $src = __DIR__ . '/../../../..';

        $this->publishes([
            $src . '/config' => config_path(),
        ], 'nanicas_auth:config');
    }
}
