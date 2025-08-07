<?php

namespace Nanicas\Auth\Frameworks\Laravel\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;
use Nanicas\Auth\Contracts\AuthenticationClient;

class AuthenticateClient
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);

        if ($config['stateless']) {
            $this->generateToken($request, $next);
            return $next($request);
        }

        $auth = $request->session()->get($config['SESSION_CLIENT_AUTH_KEY']);
        if (empty($auth)) {
            $this->generateToken($request, $next);
            return $next($request);
        }

        if (!Carbon::now()->greaterThanOrEqualTo($auth['expires_at_datetime'])) {
            return $next($request);
        }

        $this->generateToken($request, $next);
        return $next($request);
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    private function generateToken(Request $request, Closure $next)
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);

        $response = $this->makeRequest($request)['response'];

        if (!$response['status']) {
            return $response['response'];
        }

        if (!$config['stateless']) {
            AuthHelper::putAuthInfoInSession(
                $request->session(),
                $response['body'],
                AuthHelper::getClientAuthSessionKey()
            );
        } else {            
            $request->attributes->set($config['AUTHENTICATION_RESPONSE_KEY'], $response['body']['access_token']);
        }
    }

    /**
     * @param Request $request
     */
    private function makeRequest(Request $request)
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);

        $authService = app()->make(AuthenticationClient::class);
        $authResponse = $authService->retrieveByCredentials([
            'grant_type' => 'client_credentials',
            'client_id' => $config['AUTHENTICATION_CLIENT_ID'],
            'client_secret' => $config['AUTHENTICATION_CLIENT_SECRET'],
            'scope' => '',
        ]);

        if (!$authResponse['status']) {
            return [
                'status' => false,
                'response' => $this->error($request)
            ];
        }

        return [
            'status' => true,
            'response' => $authResponse,
        ];
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
