<?php

namespace App\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public function __construct(string $message = 'Recurso não encontrado.')
    {
        parent::__construct($message, 404);
    }
}
