<?php

namespace App\DTOs\Eventos;

use App\Exceptions\ValidationException;

class UpdateEventoDto
{
    public ?string $nomeEvento = null;
    public ?string $dataEvento = null;
    public ?string $local = null;
    public ?int $vagasMaximas = null;

    public function __construct(array $data)
    {
        $errors = [];

        if (array_key_exists('nomeEvento', $data) || array_key_exists('NomeEvento', $data)) {
            $rawNome = $data['nomeEvento'] ?? $data['NomeEvento'];
            if ($rawNome !== null) {
                $nome = trim((string)$rawNome);
                if (mb_strlen($nome) > 100) {
                    $errors['nomeEvento'] = 'O nome do evento deve ter no máximo 100 caracteres';
                }
                $this->nomeEvento = $nome;
            }
        }

        if (array_key_exists('dataEvento', $data) || array_key_exists('DataEvento', $data)) {
            $rawDate = $data['dataEvento'] ?? $data['DataEvento'];
            if ($rawDate !== null) {
                $timestamp = strtotime((string)$rawDate);
                if ($timestamp === false) {
                    $errors['dataEvento'] = 'Formato de data inválido';
                } else {
                    $this->dataEvento = date('Y-m-d H:i:s', $timestamp);
                }
            }
        }

        if (array_key_exists('local', $data) || array_key_exists('Local', $data)) {
            $rawLocal = $data['local'] ?? $data['Local'];
            if ($rawLocal !== null) {
                $local = trim((string)$rawLocal);
                if (mb_strlen($local) > 100) {
                    $errors['local'] = 'O local deve ter no máximo 100 caracteres';
                }
                $this->local = $local;
            }
        }

        if (array_key_exists('vagasMaximas', $data) || array_key_exists('VagasMaximas', $data)) {
            $rawVagas = $data['vagasMaximas'] ?? $data['VagasMaximas'];
            if ($rawVagas !== null) {
                if (!is_numeric($rawVagas) || (int)$rawVagas < 1) {
                    $errors['vagasMaximas'] = 'O evento precisa ter pelo menos uma vaga';
                } else {
                    $this->vagasMaximas = (int)$rawVagas;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados de atualização inválidos.', $errors);
        }
    }
}
