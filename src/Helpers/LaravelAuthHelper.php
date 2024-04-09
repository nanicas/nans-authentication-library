<?php

namespace Nanicas\Auth\Helpers;

use DateTime;
use DateInterval;

class LaravelAuthHelper
{
    public static function forgetAuthInfoFromSession(object $session): void
    {
        $session->forget(self::getAuthSessionKey());
    }

    public static function getAuthSessionKey(): string
    {
        $config = config('nanicas_authorization');

        return $config['SESSION_AUTH_KEY'];
    }

    public static function putAuthInfoInSession(object $session, array $body, string $key = '')
    {
        $expiresAt = self::defineExpiresAt($body['expires_in']);
        $body['expires_at_datetime'] = $expiresAt;

        $session->put(self::getAuthSessionKey(), $body);
        // $session->save();
    }

    public static function defineExpiresAt(int $seconds): DateTime
    {
        $date = new DateTime();
        $interval = new DateInterval('PT' . $seconds . 'S');
        $date->add($interval);

        return $date;
    }
}
