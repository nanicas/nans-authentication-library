<?php

namespace Nanicas\Auth\Frameworks\Laravel\Guards\CustomToken;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Nanicas\Auth\Contracts\AuthenticationClient;

class CustomUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials)) {
            return null;
        }

        $authService = app()->make(AuthenticationClient::class);
        $authResponse = $authService->retrieveByToken($credentials['api_token']);

        if (!$authResponse['status']) {
            return null;
        }

        $user = new User($authResponse['body']);
        $user->exists = true;

        return $user;
    }
}
