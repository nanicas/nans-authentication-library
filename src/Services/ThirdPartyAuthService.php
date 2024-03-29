<?php

namespace Nanicas\Auth\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ThirdPartyAuthService
{
    protected $client;
    protected $authUrlApi;
    protected $authToken;
    protected $config = [];

    public function __construct(
        Client $client,
        array $config = [],
    )
    {
        $this->client = $client;
        $this->config = $config;

        $this->authUrlApi = getenv('AUTH_URL_API');
        $this->authToken = getenv('AUTH_TOKEN');
    }

    public function retrieveByCredentials(array $credentials)
    {
        try {
            // Montar os dados da requisição
            $data = [
                'form_params' => $credentials,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->authToken
                ]
            ];

            // Fazer a requisição POST para o serviço de autenticação
            $response = $this->client->post($this->authUrlApi, $data);

            // Verificar se a requisição foi bem-sucedida (código de status 200)
            if ($response->getStatusCode() === 200) {
                // Retornar os dados da resposta como array associativo
                return $response->json();
            } else {
                // Se o código de status não for 200, retornar uma mensagem de erro
                return [
                    'status' => false,
                    'body' => null,
                    'message' => ["Erro na requisição: " . $response->getStatusCode()]
                ];
            }
        } catch (RequestException $e) {
            // Tratar erros de requisição
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : null;

            return [
                'status' => false,
                'body' => null,
                'message' => ["Erro na requisição: " . $statusCode]
            ];
        } catch (Exception $e) {
            // Tratar outros erros
            return [
                'status' => false,
                'body' => null,
                'message' => [$e->getMessage()]
            ];
        }
    }
}
