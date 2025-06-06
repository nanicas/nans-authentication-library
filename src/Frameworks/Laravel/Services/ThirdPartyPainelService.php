<?php

namespace Nanicas\Auth\Frameworks\Laravel\Services;

use Nanicas\Auth\Core\HTTPRequest;
use Nanicas\Auth\Frameworks\Laravel\Helpers\AuthHelper;
use Nanicas\Auth\Frameworks\Laravel\Services\ThirdPartyClient;
use Nanicas\Auth\Contracts\PainelClient;

class ThirdPartyPainelService extends ThirdPartyClient implements PainelClient
{
    public function __construct()
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);

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

    /**
     * @return string
     */
    protected function getPersonalToken(): string
    {
        $config = config(AuthHelper::CONFIG_FILE_NAME);

        return $config['PAINEL_PERSONAL_TOKEN'];
    }
}
