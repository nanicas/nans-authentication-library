<?php

namespace Nanicas\Auth\Frameworks\Laravel\Http\Middleware;

use Closure;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;

class ValidatePersonalToken
{
    public function handle($request, Closure $next)
    {
        $personalToken = $request->header('Authorization');

        $config = config(AuthHelper::CONFIG_FILE_NAME);
        $model = app($config['DEFAULT_PERSONAL_TOKEN_MODEL']);

        $last = $model->where('token', $personalToken)->first();
        if (!$last) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        if ($last->getAttribute('expires_at') < date('Y-m-d H:i:s')) {
            return response()->json(['message' => 'Token expirado'], 401);
        }

        return $next($request);
    }
}
