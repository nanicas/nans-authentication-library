<?php

namespace Nanicas\Auth\Exceptions;

class MultiplesContractsByDomainException extends \Exception
{
    protected $message = 'Multiples contracts found by domain';
}
