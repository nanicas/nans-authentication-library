<?php

namespace Nanicas\Auth\Services;

use Nanicas\Auth\Core\HTTPRequest;
use Nanicas\Auth\Helpers\LaravelAuthHelper;
use Nanicas\Auth\Services\ThirdPartyClient;
use Nanicas\Auth\Contracts\AuthorizationClient;

class ThirdPartyAuthorizationService extends ThirdPartyClient implements AuthorizationClient
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
     * @param string $token
     * @param int $contractId
     * @return array
     */
    public function retrieveByTokenAndContract(string $token, int $contractId)
    {
        return HTTPRequest::do(function () use ($token, $contractId) {

            $client = HTTPRequest::client();

            $url = 'api/user';
            $response = $client->get(
                $this->baseAPI . $url,
                [
                    'headers' => array_merge(
                        $this->defaultHeaders(),
                        $this->authorizationHeader('Bearer ' . $token),
                        ['X-Contrato-Id' => $contractId]
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
     * @param array $filters
     * @return array
     */
    public function cache(array $filters = [])
    {
        $this->setPersonal(true);
        $token = $this->getPersonalToken();

        return HTTPRequest::do(function () use ($token, $filters) {

            $client = HTTPRequest::client();
            $url = $this->handleUrl('cache');

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
     * @return string
     */
    protected function getPersonalToken(): string
    {
        $config = config(LaravelAuthHelper::CONFIG_FILE_NAME);

        return $config['AUTHORIZATION_PERSONAL_TOKEN'];
    }
}
