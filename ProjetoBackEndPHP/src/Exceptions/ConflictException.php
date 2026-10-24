<?php

namespace App\Exceptions;

use Exception;

class ConflictException extends Exception
{
    public function __construct(string $message = 'Conflito na requisição.')
    {
        parent::__construct($message, 409);
    }
}
