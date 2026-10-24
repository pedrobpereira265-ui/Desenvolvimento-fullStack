<?php

namespace App\Models;

use JsonSerializable;

class Aluno implements JsonSerializable
{
    public int $id;
    public string $nome;
    public string $email;
    public string $ra;
    public ?string $senhaHash = null;
    public string $criadoEm;
    public array $inscricoes = [];

    public static function fromDatabase(array $row): self
    {
        $aluno = new self();
        $aluno->id = (int)$row['ID_ALUNO'];
        $aluno->nome = $row['NOME_ALUNO'];
        $aluno->email = $row['EMAIL_ALUNO'];
        $aluno->ra = $row['RA_ALUNO'];
        $aluno->senhaHash = $row['SENHA_ALUNO'];
        $aluno->criadoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($row['CRIADO_EM']));
        return $aluno;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'         => $this->id,
            'nome'       => $this->nome,
            'email'      => $this->email,
            'ra'         => $this->ra,
            'criadoEm'   => $this->criadoEm,
            'inscricoes' => $this->inscricoes,
        ];
    }
}
