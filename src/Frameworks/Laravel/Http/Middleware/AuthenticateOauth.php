<?php

namespace Nanicas\Auth\Frameworks\Laravel\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nanicas\Auth\Traits\Permissionable;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;
use Nanicas\Auth\Contracts\AuthorizationClient;
use Nanicas\Auth\Contracts\AuthenticationClient;

class AuthenticateOauth
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);

        $auth = $request->session()->get($config['SESSION_AUTH_KEY']);
        if (empty($auth) || !isset($auth['access_token'])) {
            return $this->logout($request);
        }

        if (!Carbon::now()->greaterThanOrEqualTo($auth['expires_at_datetime'])) {
            return $next($request);
        }

        $authenticationClient = app()->make(AuthenticationClient::class);
        $authResponse = $authenticationClient->refreshToken([
            'grant_type' => 'refresh_token',
            'client_id' => $config['AUTHENTICATION_OAUTH_CLIENT_ID'],
            'client_secret' => $config['AUTHENTICATION_OAUTH_CLIENT_SECRET'],
            'refresh_token' => $auth['refresh_token'],
            'scope' => '',
        ]);

        if (!$authResponse['status']) {
            return $this->logout($request);
        }

        /**
         * @issue: It was clearing the session and the user was logged out
         * $request->session()->regenerate();
         */

        AuthHelper::putAuthInfoInSession(
            $request->session(),
            $authResponse['body']
        );

        $user = $request->user();
        if (in_array(Permissionable::class, class_uses_recursive($user))) {
            $authorizationClient = app()->make(AuthorizationClient::class);
            $user->forceGetACLPermissions($request, $authorizationClient);
        }

        return $next($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    private function logout(Request $request)
    {
        AuthHelper::forgetAuthInfoFromSession($request->session());

        Auth::logout();

        $request->session()->invalidate();

        return $request->expectsJson()
            ? response()->json(['message' => 'Token expirado'], 401)
            : redirect()->route('login');
    }
}
