<?php

namespace Nanicas\Auth\Core;

use Closure;
use Throwable;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;

class HTTPRequest
{
    private static $client;

    /**
     * @return GuzzleHttp\Client
     */
    public static function client(): Client
    {
        return self::$client = new Client();
    }

    /**
     * @param Closure $request
     * @return array
     */
    public static function do(Closure $request): array
    {
        try {
            return $request();
        } catch (RequestException $e) {

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $message = Psr7\Message::toString($response);
                $statusCode = $response->getStatusCode();

                return self::getDefaultFail($statusCode, $message);
            }

            return self::getDefaultFail(0, get_class($e));
        } catch (Throwable $e) {
            return [
                'status' => false,
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'body' => null,
                'message' => [$e->getMessage()]
            ];
        }
    }

    /**
     * @param int $statusCode
     * @param string $message
     * @return array
     */
    public static function getDefaultFail(int $statusCode, string $message = ''): array
    {
        return [
            'status' => false,
            'code' => $statusCode,
            'body' => null,
            'message' => ["Erro na requisição" . ((empty($message)) ? '' : ': ' . $message)]
        ];
    }

    /**
     * @param object $response
     * @param bool $isJson
     * @return array
     */
    public static function getDefaultSuccess(object $response, bool $isJson = true): array
    {
        $body = $response->getBody()->getContents();
        if ($isJson) {
            $body = json_decode($body, true);
        }

        return [
            'status' => true,
            'code' => $response->getStatusCode(),
            'body' => $body,
        ];
    }
}
