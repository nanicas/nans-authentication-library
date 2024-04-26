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
            $this->generateToken($request);
            return $next($request);
        }

        if (!Carbon::now()->greaterThanOrEqualTo($auth['expires_at_datetime'])) {
            return $next($request);
        }

        $this->generateToken($request);
        return $next($request);
    }

    /**
     * @param Request $request
     */
    private function generateToken(Request $request)
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        /**
         * Talvez, no futuro, precisaremos usar as chaves:
         * NANICAS_CLIENT_CLIENT_ID & NANICAS_CLIENT_CLIENT_SECRET
         * para não conflitar com o usuário final.
         */
        $authService = app()->make(ThirdPartyAuthService::class);
        $authResponse = $authService->retrieveByCredentials([
            'grant_type' => 'client_credentials',
            'client_id' => $config['CLIENT_ID'],
            'client_secret' => $config['CLIENT_SECRET'],
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
