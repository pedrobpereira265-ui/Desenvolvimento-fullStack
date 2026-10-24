<?php

namespace App\Exceptions;

use Exception;

class ForbiddenException extends Exception
{
    public function __construct(string $message = 'Você não tem permissão para acessar este recurso.')
    {
        parent::__construct($message, 403);
    }
}
