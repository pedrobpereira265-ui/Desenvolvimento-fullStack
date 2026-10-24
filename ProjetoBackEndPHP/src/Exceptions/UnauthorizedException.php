<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    public function __construct(string $message = 'Não autorizado.')
    {
        parent::__construct($message, 401);
    }
}
