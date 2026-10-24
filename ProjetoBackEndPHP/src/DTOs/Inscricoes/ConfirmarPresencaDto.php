<?php

namespace App\DTOs\Inscricoes;

use App\Exceptions\ValidationException;

class ConfirmarPresencaDto
{
    public bool $presencaConfirmada;

    public function __construct(array $data)
    {
        if (!array_key_exists('presencaConfirmada', $data) && !array_key_exists('PresencaConfirmada', $data)) {
            throw new ValidationException('O campo presencaConfirmada é obrigatório.', [
                'presencaConfirmada' => 'O campo presencaConfirmada é obrigatório.'
            ]);
        }

        $val = $data['presencaConfirmada'] ?? $data['PresencaConfirmada'];
        $this->presencaConfirmada = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool)$val;
    }
}
