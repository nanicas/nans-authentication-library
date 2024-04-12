<?php

namespace Nanicas\Frameworks\Laravel\Guards\CustomSession;

use Illuminate\Auth\SessionGuard;
use Nanicas\Auth\Helpers\LaravelAuthHelper;

class CustomGuard extends SessionGuard
{
    public function attempt(array $credentials = [], $remember = false)
    {
        if (!parent::attempt($credentials, $remember)) {
            return;
        }

        $authResponse = $this->provider->getResponse('retrieveByCredentials.authResponse');

        LaravelAuthHelper::putAuthInfoInSession(
            parent::getRequest()->session(), $authResponse['body']
        );
    }

    protected function clearUserDataFromStorage()
    {
        parent::clearUserDataFromStorage();

        LaravelAuthHelper::forgetAuthInfoFromSession(
            parent::getRequest()->session()
        );
    }
}
