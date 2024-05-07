<?php

namespace Nanicas\Auth\Services;

use Nanicas\Auth\Core\HTTPRequest;
use Nanicas\Auth\Helpers\LaravelAuthHelper;

class ThirdPartyPainelClient
{
    private string $baseAPI;
    private bool $personal;

    /**
     * @param array $config
     */
    public function __construct()
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        $this->baseAPI = $config['PAINEL_API_URL'];
        $this->personal = false;
    }

    /**
     * @param array $filters
     * @return array
     */
    public function contracts(array $filters = [])
    {
        $token = $this->getToken();

        return HTTPRequest::do(function () use ($token, $filters) {

            $client = HTTPRequest::client();
            $url = $this->handleUrl('contract/filter');

            $response = $client->get(
                $this->baseAPI . $url,
                [
                    'headers' => array_merge(
                        $this->defaultHeaders(),
                        $this->authorizationHeader($token),
                    ),
                    'query' => $filters
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode == 200) {
                return HTTPRequest::getDefaultSuccess($response);
            }

            $fail = $response->getBody()->getContents();
            return HTTPRequest::getDefaultFail($statusCode, $fail);
        });
    }

    /**
     * @param array $filters
     * @return array
     */
    public function applications(array $filters = [])
    {
        $token = $this->getPersonalToken();

        return HTTPRequest::do(function () use ($token, $filters) {

            $client = HTTPRequest::client();
            $url = $this->handleUrl('application/filter');

            $response = $client->get(
                $this->baseAPI . $url,
                [
                    'headers' => array_merge(
                        $this->defaultHeaders(),
                        $this->authorizationHeader($token),
                    ),
                    'query' => $filters
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode == 200) {
                return HTTPRequest::getDefaultSuccess($response);
            }

            $fail = $response->getBody()->getContents();
            return HTTPRequest::getDefaultFail($statusCode, $fail);
        });
    }

    public function setPersonal(bool $personal)
    {
        $this->personal = $personal;
    }

    private function handleUrl(string $url)
    {
        if ($this->personal) {
            return 'api/personal/' . $url;
        }

        return 'api/' . $url;
    }

    /**
     * @return string
     */
    private function getToken()
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
    private function getAuthToken(): string
    {
        return 'Bearer ' . session()->get(LaravelAuthHelper::getAuthSessionKey())['access_token'];
    }

    /**
     * @return string
     */
    private function getPersonalToken(): string
    {
        return env('NANICAS_PAINEL_API_PERSONAL_TOKEN');
    }

    /**
     * @return array
     */
    private function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }

    /**
     * @param string $token
     * @return array
     */
    private function authorizationHeader(string $token): array
    {
        return [
            'Authorization' => $token,
        ];
    }
}
