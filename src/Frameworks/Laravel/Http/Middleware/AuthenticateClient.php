<?php

namespace Nanicas\Auth\Frameworks\Laravel\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Nanicas\Auth\Services\ThirdPartyAuthService;
use Illuminate\Http\Request;
use Nanicas\Auth\Helpers\LaravelAuthHelper;

class AuthenticateClient
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        $auth = $request->session()->get($config['SESSION_CLIENT_AUTH_KEY']);
        if (empty($auth)) {
            return $this->generateToken($request, $next);
        }

        if (!Carbon::now()->greaterThanOrEqualTo($auth['expires_at_datetime'])) {
            return $next($request);
        }

        return $this->generateToken($request, $next);
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    private function generateToken(Request $request, Closure $next)
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        $authService = app()->make(ThirdPartyAuthService::class);
        $authResponse = $authService->retrieveByCredentials([
            'grant_type' => 'client_credentials',
            'client_id' => $config['AUTHENTICATION_CLIENT_ID'],
            'client_secret' => $config['AUTHENTICATION_CLIENT_SECRET'],
            'scope' => '',
        ]);

        if (!$authResponse['status']) {
            return $this->error($request);
        }

        LaravelAuthHelper::putAuthInfoInSession(
            $request->session(),
            $authResponse['body'],
            LaravelAuthHelper::getClientAuthSessionKey()
        );

        return $next($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    private function error(Request $request)
    {
        $message = 'Não foi possível criar um token usando as credenciais como cliente';
        return $request->expectsJson()
            ? response()->json(['message' => $message], 401)
            : redirect()->route('error', compact('message'));
    }
}
