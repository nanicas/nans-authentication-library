<?php

namespace Nanicas\Auth\Exceptions;

class UndefinedContractByDomainException extends \Exception
{
    protected $message = 'Contract not found by domain';
}
