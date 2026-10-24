<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Lançada quando a conexão ou uma operação no banco de dados falha.
 * Tratada de forma centralizada pelo Router (HTTP 503).
 */
class DatabaseException extends Exception
{
    public function __construct(string $message = 'Serviço de banco de dados indisponível.', ?Throwable $previous = null)
    {
        parent::__construct($message, 503, $previous);
    }
}
