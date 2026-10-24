<?php

namespace App\Models;

use JsonSerializable;

class Inscricao implements JsonSerializable
{
    public int $id;
    public int $alunoId;
    public ?Aluno $aluno = null;
    public int $eventoId;
    public ?Evento $evento = null;
    public bool $presencaConfirmada;
    public string $inscritoEm;

    public static function fromDatabase(array $row): self
    {
        $inscricao = new self();
        $inscricao->id = (int)$row['ID_INSCRICAO'];
        $inscricao->alunoId = (int)$row['ID_ALUNO'];
        $inscricao->eventoId = (int)$row['ID_EVENTO'];
        $inscricao->presencaConfirmada = (bool)$row['PRESENCA_CONFIRMADA'];
        $inscricao->inscritoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($row['DATA_INSCRICAO']));

        // Se os dados do Aluno foram carregados no JOIN
        if (isset($row['ALUNO_NOME'])) {
            $aluno = new Aluno();
            $aluno->id = (int)$row['ID_ALUNO'];
            $aluno->nome = $row['ALUNO_NOME'];
            $aluno->email = $row['ALUNO_EMAIL'];
            $aluno->ra = $row['ALUNO_RA'] ?? '';
            $aluno->senhaHash = $row['ALUNO_SENHA'] ?? '';
            $aluno->criadoEm = isset($row['ALUNO_CRIADO_EM']) ? gmdate('Y-m-d\TH:i:s\Z', strtotime($row['ALUNO_CRIADO_EM'])) : '';
            $inscricao->aluno = $aluno;
        }

        // Se os dados do Evento foram carregados no JOIN
        if (isset($row['NOME_EVENTO'])) {
            $evento = new Evento();
            $evento->id = (int)$row['ID_EVENTO'];
            $evento->nomeEvento = $row['NOME_EVENTO'];
            $evento->dataEvento = gmdate('Y-m-d\TH:i:s\Z', strtotime($row['DATA_EVENTO']));
            $evento->local = $row['LOCAL_EVENTO'];
            $evento->vagasMaximas = (int)$row['VAGAS_MAXIMAS'];
            $evento->criadoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($row['CRIADO_EM']));
            $evento->coordenadorId = $row['ID_COORDENADOR'] !== null ? (int)$row['ID_COORDENADOR'] : null;
            $evento->diretorId = $row['ID_DIRETOR'] !== null ? (int)$row['ID_DIRETOR'] : null;
            $inscricao->evento = $evento;
        }

        return $inscricao;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'                 => $this->id,
            'alunoId'            => $this->alunoId,
            'aluno'              => $this->aluno,
            'eventoId'           => $this->eventoId,
            'evento'             => $this->evento,
            'presencaConfirmada' => $this->presencaConfirmada,
            'inscritoEm'         => $this->inscritoEm,
        ];
    }
}
