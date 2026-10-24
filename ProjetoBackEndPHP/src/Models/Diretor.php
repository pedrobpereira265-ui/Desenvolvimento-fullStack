<?php

namespace App\Models;

use JsonSerializable;

class Diretor implements JsonSerializable
{
    public int $id;
    public string $nome;
    public string $email;
    public ?string $senhaHash = null;
    public string $criadoEm;
    public array $eventos = [];

    public static function fromDatabase(array $row): self
    {
        $diretor = new self();
        $diretor->id = (int)$row['ID_DIRETOR'];
        $diretor->nome = $row['NOME_DIRETOR'];
        $diretor->email = $row['EMAIL_DIRETOR'];
        $diretor->senhaHash = $row['SENHA_DIRETOR'];
        $diretor->criadoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($row['CRIADO_EM']));
        return $diretor;
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
