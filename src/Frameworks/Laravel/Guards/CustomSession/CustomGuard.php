<?php

namespace Nanicas\Auth\Frameworks\Laravel\Guards\CustomSession;

use Illuminate\Auth\SessionGuard;
use Nanicas\Auth\Helpers\LaravelAuthHelper;
use App\Models\User;

class CustomGuard extends SessionGuard
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        if (!parent::attempt($credentials, $remember)) {
            return;
        }

        $authResponse = $this->provider
            ->getResponse('retrieveByCredentials.authResponse');

        LaravelAuthHelper::putAuthInfoInSession(
            parent::getRequest()->session(), $authResponse['body']
        );
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // @ref: parent::user
        if ($this->loggedOut) {
            return;
        }

        // @ref: parent::user
        if (!is_null($this->user)) {
            return $this->user;
        }

        $authSession = parent::getRequest()
            ->session()
            ->get(LaravelAuthHelper::getAuthSessionKey());

        if (!empty($authSession)) {
            $id = $this->session->get($this->getName());
            $token = $authSession['access_token'];

            if (!is_null($id)) {
                $user = $this->provider->retrieveByAccessToken($token);
                if ($user instanceof User) {
                    $this->user = $user;
                    $this->fireAuthenticatedEvent($this->user);
                }
            }
        }

        // @ref: parent::user
        if (is_null($this->user) && !is_null($recaller = $this->recaller())) {
            $this->user = $this->userFromRecaller($recaller);

            if ($this->user) {
                $this->updateSession($this->user->getAuthIdentifier());
                $this->fireLoginEvent($this->user, true);
            }
        }

        return $this->user;
    }

    /**
     * Remove the user data from the session and cookies.
     *
     * @return void
     */
    protected function clearUserDataFromStorage()
    {
        parent::clearUserDataFromStorage();

        LaravelAuthHelper::forgetAuthInfoFromSession(
            parent::getRequest()->session()
        );
    }
}
