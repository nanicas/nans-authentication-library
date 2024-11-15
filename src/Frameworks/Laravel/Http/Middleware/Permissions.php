<?php

namespace Nanicas\Auth\Frameworks\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Nanicas\Auth\Contracts\AuthorizationClient;

class Permissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$guards
    ): Response {
        if (!auth()->check()) {
            return $next($request);
        }

        $client = app()->make(AuthorizationClient::class);

        Gate::before(function ($user, $ability) use ($request, $client) {

            $acl = $user->getACLPermissions($request, $client);
            if (!array_key_exists('permissions', $acl)) {
                return false;
            }

            return (in_array($ability, $acl['permissions']));
        });

        return $next($request);
    }
}
