<?php

namespace Nanicas\Auth\Core;

use Closure;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

class HTTPRequest
{
    private static $client;

    public static function client()
    {
        return self::$client = new Client();
    }

    public static function do(Closure $request)
    {
        try {
            return $request();
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : null;

            return self::getDefaultFail($statusCode);
        } catch (Exception $e) {
            return [
                'status' => false,
                'body' => null,
                'message' => [$e->getMessage()]
            ];
        }
    }

    public static function getDefaultFail(int $statusCode)
    {
        return [
            'status' => false,
            'body' => null,
            'message' => ["Erro na requisição: " . $statusCode]
        ];
    }
}
