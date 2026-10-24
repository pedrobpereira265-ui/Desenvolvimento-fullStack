<?php

namespace App\DTOs\Eventos;

use App\Exceptions\ValidationException;
use DateTime;

class CreateEventoDto
{
    public string $nomeEvento;
    public string $dataEvento;
    public string $local;
    public int $vagasMaximas;

    public function __construct(array $data)
    {
        $errors = [];

        $nomeEvento = trim($data['nomeEvento'] ?? $data['NomeEvento'] ?? '');
        if ($nomeEvento === '') {
            $errors['nomeEvento'] = 'O nome do evento é obrigatório';
        } elseif (mb_strlen($nomeEvento) > 100) {
            $errors['nomeEvento'] = 'O nome do evento deve ter no máximo 100 caracteres';
        }

        $rawDate = $data['dataEvento'] ?? $data['DataEvento'] ?? null;
        if (!$rawDate) {
            $errors['dataEvento'] = 'A data do evento é obrigatória';
            $dataEvento = '';
        } else {
            $timestamp = strtotime((string)$rawDate);
            if ($timestamp === false) {
                $errors['dataEvento'] = 'Formato de data inválido';
                $dataEvento = '';
            } else {
                $dataEvento = date('Y-m-d H:i:s', $timestamp);
            }
        }

        $local = trim($data['local'] ?? $data['Local'] ?? '');
        if ($local === '') {
            $errors['local'] = 'O local é obrigatório';
        } elseif (mb_strlen($local) > 100) {
            $errors['local'] = 'O local deve ter no máximo 100 caracteres';
        }

        $rawVagas = $data['vagasMaximas'] ?? $data['VagasMaximas'] ?? null;
        if ($rawVagas === null || !is_numeric($rawVagas) || (int)$rawVagas < 1) {
            $errors['vagasMaximas'] = 'O evento precisa ter pelo menos uma vaga';
            $vagasMaximas = 0;
        } else {
            $vagasMaximas = (int)$rawVagas;
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados do evento inválidos.', $errors);
        }

        $this->nomeEvento = $nomeEvento;
        $this->dataEvento = $dataEvento;
        $this->local = $local;
        $this->vagasMaximas = $vagasMaximas;
    }
}
