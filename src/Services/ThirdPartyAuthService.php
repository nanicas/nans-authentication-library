<?php

namespace Nanicas\Auth\Services;

use Nanicas\Auth\Core\HTTPRequest;
use Nanicas\Auth\Helpers\LaravelAuthHelper;
use Nanicas\Auth\Services\AbstractClient;
use Nanicas\Auth\Contracts\AuthenticationClient;

class ThirdPartyAuthService extends AbstractClient implements AuthenticationClient
{
    public function __construct()
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        $this->baseAPI = $config['AUTHENTICATION_API_URL'];
    }

    /**
     * @param array $filters
     * @return array
     */
    public function users(array $filters = [])
    {
        $token = $this->getToken();

        return HTTPRequest::do(function () use ($token, $filters) {

            $client = HTTPRequest::client();
            $url = $this->handleUrl('user/filter');

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
     * @param array $credentials
     * @return array
     */
    public function retrieveByCredentials(array $credentials)
    {
        return HTTPRequest::do(function () use ($credentials) {

            $client = HTTPRequest::client();
            $data = [
                'form_params' => $credentials,
                'headers' => $this->defaultHeaders(),
            ];

            $url = 'oauth/token';
            $response = $client->post($this->baseAPI . $url, $data);

            if ($response->getStatusCode() == 200) {
                return HTTPRequest::getDefaultSuccess($response);
            }

            return HTTPRequest::getDefaultFail($response->getStatusCode());
        });
    }

    /**
     * @param array $credentials
     * @return array
     */
    public function refreshToken(array $credentials)
    {
        return HTTPRequest::do(function () use ($credentials) {

            $client = HTTPRequest::client();
            $data = [
                'form_params' => $credentials,
                'headers' => $this->defaultHeaders(),
            ];

            $url = 'oauth/token';
            $response = $client->post($this->baseAPI . $url, $data);

            if ($response->getStatusCode() == 200) {
                return HTTPRequest::getDefaultSuccess($response);
            }

            return HTTPRequest::getDefaultFail($response->getStatusCode());
        });
    }

    /**
     * @param string $token
     * @return array
     */
    public function checkAccessToken(string $token)
    {
        return HTTPRequest::do(function () use ($token) {

            $client = HTTPRequest::client();

            $url = 'api/user/check';
            $response = $client->get(
                $this->baseAPI . $url,
                [
                    'headers' => array_merge(
                        $this->defaultHeaders(),
                        $this->authorizationHeader('Bearer ' . $token),
                    )
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
     * @param string $token
     * @return array
     */
    public function retrieveByToken(string $token)
    {
        return HTTPRequest::do(function () use ($token) {

            $client = HTTPRequest::client();

            $url = 'api/user';
            $response = $client->get(
                $this->baseAPI . $url,
                [
                    'headers' => array_merge(
                        $this->defaultHeaders(),
                        $this->authorizationHeader('Bearer ' . $token),
                    )
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
     * @return string
     */
    protected function getClientAuthToken(): string
    {
        $token = '';
        $sessionKey = LaravelAuthHelper::getClientAuthSessionKey();

        if (session()->has($sessionKey)) {
            $token = session()->get($sessionKey)['access_token'];
        }
        if (empty($token)) {
            $token = $this->generateClientAuthToken();
        }

        return $token;
    }

    /**
     * @return null|string
     */
    private function generateClientAuthToken()
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        $authResponse = $this->retrieveByCredentials([
            'grant_type' => 'client_credentials',
            'client_id' => $config['AUTHENTICATION_CLIENT_ID'],
            'client_secret' => $config['AUTHENTICATION_CLIENT_SECRET'],
            'scope' => '',
        ]);

        if (!$authResponse['status']) {
            return null;
        }

        return $authResponse['body']['access_token'];
    }

    protected function getPersonalToken(): string
    {
        return 'It is not allowed to use personal token in this service. All requests must be made with oauth token.';
    }
}
