<?php

namespace Nanicas\Auth\Services;

use Nanicas\Auth\Helpers\LaravelAuthHelper;

abstract class ThirdPartyClient
{
    protected string $baseAPI;
    protected bool $personal = false;

    protected bool $client = false; // client credentials

    /**
     * @param bool $personal
     */
    public function setPersonal(bool $personal)
    {
        $this->personal = $personal;
    }

    /**
     * @param bool $personal
     */
    public function setClient(bool $client)
    {
        $this->client = $client;
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
        } elseif ($this->client) {
            $token = $this->getClientAuthToken();
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
     * @return string
     */
    protected function getClientAuthToken(): string
    {
        return session()->get(LaravelAuthHelper::getClientAuthSessionKey())['access_token'];
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
