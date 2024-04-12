<?php

namespace Nanicas\Auth\Frameworks\Laravel\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Nanicas\Auth\Services\ThirdPartyAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Nanicas\Auth\Helpers\LaravelAuthHelper;

class Authenticate
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        $auth = $request->session()->get($config['SESSION_AUTH_KEY']);
        if (empty($auth)) {
            return $this->logout($request);
        }

        if (!Carbon::now()->greaterThanOrEqualTo($auth['expires_at_datetime'])) {
            return $next($request);
        }

        $authService = app()->make(ThirdPartyAuthService::class);
        $authResponse = $authService->refreshToken([
            'grant_type' => 'refresh_token',
            'client_id' => $config['CLIENT_ID'],
            'client_secret' => $config['CLIENT_SECRET'],
            'refresh_token' => $auth['refresh_token'],
            'scope' => '',
        ]);

        if (!$authResponse['status']) {
            return $this->logout($request);
        }

        /**
         * @ref: https://laravel.com/docs/10.x/session#regenerating-the-session-id
         * @question: https://stackoverflow.com/questions/78303780/laravel-10-session-regeneration-in-middleware-not-updating-values
         * @question: https://stackoverflow.com/questions/78305978/laravel-get-old-session-id-on-refresh
         * 
         * Quando descomenta essa linha abaixo, por algum motivo, o framework 
         * recupera os valores antigos da sessão, fazendo com que caia na condição:
         * (!$authResponse['status']).
         * 
         * $request->session()->regenerate();
         */

        LaravelAuthHelper::putAuthInfoInSession(
            $request->session(), $authResponse['body']
        );

        return $next($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    private function logout(Request $request)
    {
        LaravelAuthHelper::forgetAuthInfoFromSession($request->session());

        Auth::logout();

        $request->session()->invalidate();

        return $request->expectsJson() 
            ? response()->json(['message' => 'Token expirado'], 401) 
            : redirect()->route('login');
    }
}
