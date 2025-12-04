<?php

namespace Nanicas\Auth\Frameworks\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;
use Illuminate\Support\Facades\Gate;
use App\Providers\AuthServiceProvider;

class AuthorizateWithDynamicContract
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $contractId = $request->header('x-contrato-id');

        if (!$contractId) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Contract ID is required.',
            ], 401);
        }

        $request = request();
        $config = config(AuthHelper::CONFIG_FILE_NAME);
        $token = $request->bearerToken();

        $authorizator = app()->make(AuthorizationClient::class);
        $response = $authorizator->retrieveByTokenAndContract($token, $contractId);

        if (!$response['status']) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => (isset($response['message'])) ? $response['message'] : 'Invalid token or contract not found.',
            ], 401);
        }

        $request->attributes->set($config['AUTHORIZATION_RESPONSE_KEY'], $response);

        $this->bootGate($response['body']['response']);

        return $next($request);
    }

    /**
     * @param array $response
     * @return void
     */
    private function bootGate(array $response)
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);
        if (!isset($config['gate']) || !$config['gate']['check_acl_permissions']) {
            return;
        }

        if (!array_key_exists('permissions', $response)) {
            return;
        }

        $mapPolicies = AuthServiceProvider::getPolicyPermissionMap();

        foreach ($response['permissions'] as $permission) {
            list($resource, $ability) = explode('.', $permission);
            if (!array_key_exists($resource, $mapPolicies)) {
                continue;
            }

            Gate::define($permission, [$mapPolicies[$resource], $ability]);
        }
    }
}
