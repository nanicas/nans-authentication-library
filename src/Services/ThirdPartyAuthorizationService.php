<?php

namespace Nanicas\Auth\Services;

use Nanicas\Auth\Core\HTTPRequest;
use Nanicas\Auth\Helpers\LaravelAuthHelper;
use Nanicas\Auth\Services\AbstractClient;

class ThirdPartyAuthorizationService extends AbstractClient
{
    public function __construct()
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        $this->baseAPI = $config['AUTHORIZATION_API_URL'];
    }

    /**
     * @param array $credentials
     * @return array
     */
    public function joinContractor(array $data)
    {
        $token = $this->getToken();

        return HTTPRequest::do(function () use ($token, $data) {

            $client = HTTPRequest::client();
            $data = [
                'form_params' => $data,
                'headers' => array_merge(
                    $this->defaultHeaders(),
                    $this->authorizationHeader($token),
                )
            ];
            $url = $this->handleUrl('contractor/join');

            $response = $client->post($this->baseAPI . $url, $data);

            if ($response->getStatusCode() == 200) {
                return HTTPRequest::getDefaultSuccess($response);
            }

            return HTTPRequest::getDefaultFail($response->getStatusCode());
        });
    }

    /**
     * @return string
     */
    protected function getPersonalToken(): string
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        return $config['AUTHORIZATION_PERSONAL_TOKEN'];
    }
}
