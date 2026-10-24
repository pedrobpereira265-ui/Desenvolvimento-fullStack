<?php

namespace App\Models;

use JsonSerializable;

class Coordenador implements JsonSerializable
{
    public int $id;
    public string $nome;
    public string $email;
    public ?string $senhaHash = null;
    public string $criadoEm;
    public array $eventos = [];

    public static function fromDatabase(array $row): self
    {
        $coordenador = new self();
        $coordenador->id = (int)$row['ID_COORDENADOR'];
        $coordenador->nome = $row['NOME_COORDENADOR'];
        $coordenador->email = $row['EMAIL_COORDENADOR'];
        $coordenador->senhaHash = $row['SENHA_COORDENADOR'];
        $coordenador->criadoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($row['CRIADO_EM']));
        return $coordenador;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'        => $this->id,
            'nome'      => $this->nome,
            'email'     => $this->email,
            'criadoEm'  => $this->criadoEm,
            'eventos'   => $this->eventos,
        ];
    }
}
