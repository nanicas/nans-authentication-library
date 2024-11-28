<?php

namespace Nanicas\Auth\Helpers;

use DateTime;
use DateInterval;

class LaravelAuthHelper
{
    const CONFIG_FILE_NAME = 'nanicas_auth';

    /**
     * @param object $session
     * @return void
     */
    public static function forgetAuthInfoFromSession(object $session): void
    {
        $session->forget(self::getAuthSessionKey());
    }

    /**
     * @return string
     */
    public static function getAuthSessionKey(): string
    {
        $config = config(self::CONFIG_FILE_NAME);

        return $config['SESSION_AUTH_KEY'];
    }

    /**
     * @return string
     */
    public static function getClientAuthSessionKey(): string
    {
        $config = config(self::CONFIG_FILE_NAME);

        return $config['SESSION_CLIENT_AUTH_KEY'];
    }

    /**
     * @param object $session
     * @param string $newSessionKey
     * @param array $body
     * @param string $currentSessionKey
     * @return void
     */
    public static function attachInSession(
        object $session,
        string $newSessionKey = '',
        array $body,
        string $currentSessionKey = '',
    ): void {
        $currentSessionKey = (empty($currentSessionKey)) ? self::getAuthSessionKey() : $currentSessionKey;
        $currentBody = $session->get($currentSessionKey, []);

        $currentBody[$newSessionKey] = $body;
        $session->put($currentSessionKey, $currentBody);
    }

    /**
     * @param object $session
     * @param string $sessionKey
     * @return bool
     */
    public static function existsInSession(object $session, string $sessionKey = ''): bool
    {
        $sessionKey = (empty($sessionKey)) ? self::getAuthSessionKey() : $sessionKey;
        return $session->has($sessionKey);
    }

    /**
     * @param object $session
     * @param array $body
     * @param string $sessionKey
     */
    public static function putAuthInfoInSession(
        object $session,
        array $body,
        string $sessionKey = ''
    ) {
        $expiresAt = self::defineExpiresAt($body['expires_in']);
        $body['expires_at_datetime'] = $expiresAt;

        $sessionKey = (empty($sessionKey)) ? self::getAuthSessionKey() : $sessionKey;

        if (self::existsInSession($session, $sessionKey)) {
            $currentBody = $session->get($sessionKey);
            $body = array_merge($currentBody, $body);
        }

        $session->put($sessionKey, $body);
    }

    /**
     * @param object $session
     * @param string $sessionKey
     * @return array
     */
    public static function getAuthInfoFromSession(object $session, string $sessionKey = ''): array
    {
        $sessionKey = (empty($sessionKey)) ? self::getAuthSessionKey() : $sessionKey;
        return $session->get($sessionKey);
    }

    /**
     * @param int $seconds
     * @return DateTime
     */
    public static function defineExpiresAt(int $seconds): DateTime
    {
        $date = new DateTime();
        $interval = new DateInterval('PT' . $seconds . 'S');
        $date->add($interval);

        return $date;
    }
}
