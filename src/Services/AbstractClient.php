<?php

namespace Nanicas\Auth\Services;

use Nanicas\Auth\Helpers\LaravelAuthHelper;

abstract class AbstractClient
{
    protected string $baseAPI;
    protected bool $personal;

    /**
     * @param bool $personal
     */
    public function setPersonal(bool $personal)
    {
        $this->personal = $personal;
    }

    abstract protected function getPersonalToken(): string;

    /**
     * @param string $url
     * @return string
     */
    protected function handleUrl(string $url): string
    {
        if ($this->personal) {
            return 'api/personal/' . $url;
        }

        return 'api/' . $url;
    }

    /**
     * @return string
     */
    protected function getToken()
    {
        if ($this->personal) {
            $token = $this->getPersonalToken();
        } else {
            $token = $this->getAuthToken();
        }

        return $token;
    }

    /**
     * @return string
     */
    protected function getAuthToken(): string
    {
        return 'Bearer ' . session()->get(LaravelAuthHelper::getAuthSessionKey())['access_token'];
    }

    /**
     * @return array
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }

    /**
     * @param string $token
     * @return array
     */
    protected function authorizationHeader(string $token): array
    {
        return [
            'Authorization' => $token,
        ];
    }
}
