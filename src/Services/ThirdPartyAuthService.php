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
            ];

            $url = 'oauth/token';
            $response = $client->post($this->baseAPI . $url, $data);

            if ($response->getStatusCode() == 200) {
                $body = $response->getBody()->getContents();
                return json_decode($body, true);
            }

            return HTTPRequest::getDefaultFail($response->getStatusCode());
        });
    }

    public function configureAuthorizationToken(string $token)
    {
        HTTPRequest::client()->setDefaultOption(
            'headers/Authorization', 'Bearer ' . $token
        );
    }
}
