<?php

namespace Nanicas\Auth\Frameworks\Laravel\Traits;

trait PolicyPermissionMapeable
{
    /**
     * The policy permission map.
     * 
     * @var array<string, class-string>
     * @example [
     *  'charge' => ChargePolicy::class,
     * ]
     * 
     * public static $mapPermissions = [];
     */

    /**
     * Get the policy permission map.
     */
    public static function getPolicyPermissionMap()
    {
        return static::$mapPermissions;
    }
}
