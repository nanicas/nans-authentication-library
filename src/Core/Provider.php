<?php

namespace Nanicas\Auth\Core;

use Psr\Container\ContainerInterface;

class Provider
{
    public static function register(ContainerInterface $container): void
    {
        //$container->bind(
        //   \Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyAuthService::class,
        //   \Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyAuthService::class
        //);
    }
}
