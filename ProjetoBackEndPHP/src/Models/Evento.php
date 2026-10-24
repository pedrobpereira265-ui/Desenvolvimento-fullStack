<?php

namespace App\Models;

use JsonSerializable;

class Evento implements JsonSerializable
{
    public int $id;
    public string $nomeEvento;
    public string $dataEvento;
    public string $local;
    public int $vagasMaximas;
    public string $criadoEm;
    public ?int $coordenadorId = null;
    public ?Coordenador $coordenador = null;
    public ?int $diretorId = null;
    public ?Diretor $diretor = null;

    public static function fromDatabase(array $row): self
    {
        $evento = new self();
        $evento->id = (int)$row['ID_EVENTO'];
        $evento->nomeEvento = $row['NOME_EVENTO'];
        $evento->dataEvento = gmdate('Y-m-d\TH:i:s\Z', strtotime($row['DATA_EVENTO']));
        $evento->local = $row['LOCAL_EVENTO'];
        $evento->vagasMaximas = (int)$row['VAGAS_MAXIMAS'];
        $evento->criadoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($row['CRIADO_EM']));
        $evento->coordenadorId = $row['ID_COORDENADOR'] !== null ? (int)$row['ID_COORDENADOR'] : null;
        $evento->diretorId = $row['ID_DIRETOR'] !== null ? (int)$row['ID_DIRETOR'] : null;

        // Se os dados do coordenador foram carregados no JOIN
        if (isset($row['COORD_NOME'])) {
            $coord = new Coordenador();
            $coord->id = (int)$row['ID_COORDENADOR'];
            $coord->nome = $row['COORD_NOME'];
            $coord->email = $row['COORD_EMAIL'];
            $coord->senhaHash = $row['COORD_SENHA'] ?? '';
            $coord->criadoEm = isset($row['COORD_CRIADO_EM']) ? gmdate('Y-m-d\TH:i:s\Z', strtotime($row['COORD_CRIADO_EM'])) : '';
            $evento->coordenador = $coord;
        }

        // Se os dados do diretor foram carregados no JOIN
        if (isset($row['DIR_NOME'])) {
            $dir = new Diretor();
            $dir->id = (int)$row['ID_DIRETOR'];
            $dir->nome = $row['DIR_NOME'];
            $dir->email = $row['DIR_EMAIL'];
            $dir->senhaHash = $row['DIR_SENHA'] ?? '';
            $dir->criadoEm = isset($row['DIR_CRIADO_EM']) ? gmdate('Y-m-d\TH:i:s\Z', strtotime($row['DIR_CRIADO_EM'])) : '';
            $evento->diretor = $dir;
        }

        return $evento;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'nomeEvento'    => $this->nomeEvento,
            'dataEvento'    => $this->dataEvento,
            'local'         => $this->local,
            'vagasMaximas'  => $this->vagasMaximas,
            'criadoEm'      => $this->criadoEm,
            'coordenadorId' => $this->coordenadorId,
            'coordenador'   => $this->coordenador,
            'diretorId'     => $this->diretorId,
            'diretor'       => $this->diretor,
        ];
    }
}
