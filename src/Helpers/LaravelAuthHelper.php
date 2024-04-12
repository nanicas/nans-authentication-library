<?php

namespace Nanicas\Auth\Helpers;

use DateTime;
use DateInterval;

class LaravelAuthHelper
{
    const CONFIG_FILE_NAME = 'nanicas_authorization';

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
     * @param object $session
     * @param array $body
     */
    public static function putAuthInfoInSession(
        object $session, array $body
    )
    {
        $expiresAt = self::defineExpiresAt($body['expires_in']);
        $body['expires_at_datetime'] = $expiresAt;

        $session->put(self::getAuthSessionKey(), $body);
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
