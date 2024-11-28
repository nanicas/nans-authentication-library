<?php

namespace Nanicas\Auth\Frameworks\Laravel\Guards\CustomSession;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Nanicas\Auth\Services\ThirdPartyAuthService;
use App\Models\User;
use RuntimeException;
use Nanicas\Auth\Helpers\LaravelAuthHelper;

class CustomUserProvider extends EloquentUserProvider
{
    private array $responses = [];

    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials)) {
            return null;
        }

        $request = app()->make('request');

        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);
        $auth = $request->session()->get($config['SESSION_AUTH_KEY']);

        $payload = [
            'grant_type' => 'password',
            'client_id' => $config['AUTHENTICATION_OAUTH_CLIENT_ID'],
            'client_secret' => $config['AUTHENTICATION_OAUTH_CLIENT_SECRET'],
            'username' => $credentials['email'],
            'password' => $credentials['password'],
            'scope' => '',
        ];

        if (!empty($auth) && array_key_exists('contract',  $auth)) {
            $payload['contract_id'] = $auth['contract']['id'];
        }

        $authService = app()->make(ThirdPartyAuthService::class);
        $authResponse = $authService->retrieveByCredentials($payload);

        $this->setResponse('retrieveByCredentials.authResponse', $authResponse);
        if (!$authResponse['status']) {
            return null;
        }

        $userResponse = $this->retrieveByAccessToken($authResponse['body']['access_token']);
        if ($userResponse instanceof User) {
            return $userResponse;
        }

        throw new RuntimeException(current($userResponse['message']));
    }

    public function retrieveByAccessToken(string $token): User|array
    {
        $authService = app()->make(ThirdPartyAuthService::class);

        $userResponse = $authService->retrieveByToken($token);
        if ($userResponse['status']) {
            $user = new User($userResponse['body']);
            $user->exists = true;
            return $user;
        }

        return $userResponse;
    }

    public function validateCredentials(UserContract $user, array $credentials)
    {
        return true;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getResponse(string $key): mixed
    {
        return $this->responses[$key] ?? null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private function setResponse(string $key, mixed $value): void
    {
        $this->responses[$key] = $value;
    }
}
