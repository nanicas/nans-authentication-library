<?php

namespace Nanicas\Auth\Frameworks\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Nanicas\Auth\Frameworks\Laravel\Traits\PermissionableSession;

class AuthorizateWithRequestUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $user = $request->user();
        if (in_array(PermissionableSession::class, class_uses_recursive($user))) {
            $authorizationClient = app()->make(AuthorizationClient::class);
            $user->getACLPermissions($request, $authorizationClient);
        }

        // @todo: call bootGate

        return $next($request);
    }

    /**
     * @param array $response
     * @return void
     */
    private function bootGate(array $response)
    {
        // @ref: src/Frameworks/Laravel/Http/Middleware/AuthorizateWithDynamicContract.php
    }
}
