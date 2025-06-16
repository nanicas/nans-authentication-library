<?php

namespace Nanicas\Auth\Exceptions;

class RequiredAuthorizationResponseToPermissionateException extends \Exception
{
    protected $message = 'Authorization response is required to permissionate';
}
