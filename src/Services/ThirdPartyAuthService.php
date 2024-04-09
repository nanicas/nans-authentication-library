<?php

namespace Nanicas\Auth\Services;

use Nanicas\Auth\Core\HTTPRequest;

class ThirdPartyAuthService
{
    private string $baseAPI;

    public function __construct(
        array $config = [],
    )
    {
        $this->baseAPI = $config['API_URL'];
    }

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
                        $this->authorizationHeader($token),
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

    private function defaultHeaders()
    {
        return [
            'Accept' => 'application/json',
        ];
    }
    
    /**
     * @param string $token
     */
    private function authorizationHeader(string $token)
    {
        return [
            'Authorization' => 'Bearer ' . $token,
        ];
    }
}
