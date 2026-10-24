<?php

namespace App\Services;

use App\Config\Database;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Models\Inscricao;
use PDO;

class InscricaoService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function inscrever(int $alunoId, int $eventoId): Inscricao
    {
        // 1. Verifica se o evento existe
        $stmt = $this->db->prepare('SELECT * FROM `EVENTO` WHERE `ID_EVENTO` = :eventoId LIMIT 1');
        $stmt->execute([':eventoId' => $eventoId]);
        $evento = $stmt->fetch();

        if (!$evento) {
            throw new NotFoundException("Evento com ID {$eventoId} não encontrado.");
        }

        // 2. Verifica se o aluno já está inscrito
        if ($this->isAlunoInscrito($alunoId, $eventoId)) {
            throw new ConflictException('Aluno já está inscrito neste evento.');
        }

        // 3. Verifica lotação do evento
        if ($this->isEventoLotado($eventoId, (int)$evento['VAGAS_MAXIMAS'])) {
            throw new ConflictException('Vagas esgotadas para este evento.');
        }

        // 4. Cria a inscrição
        $dataIncricao = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare('
            INSERT INTO `INSCRICAO` (`ID_ALUNO`, `ID_EVENTO`, `PRESENCA_CONFIRMADA`, `DATA_INSCRICAO`)
            VALUES (:alunoId, :eventoId, 0, :dataInscricao)
        ');
        $stmt->execute([
            ':alunoId'        => $alunoId,
            ':eventoId'       => $eventoId,
            ':dataInscricao'  => $dataIncricao,
        ]);

        $id = (int)$this->db->lastInsertId();

        $inscricao = new Inscricao();
        $inscricao->id = $id;
        $inscricao->alunoId = $alunoId;
        $inscricao->eventoId = $eventoId;
        $inscricao->presencaConfirmada = false;
        $inscricao->inscritoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($dataIncricao));
        return $inscricao;
    }

    public function getByAluno(int $alunoId): array
    {
        $sql = '
            SELECT 
                i.*,
                e.`NOME_EVENTO`,
                e.`DATA_EVENTO`,
                e.`LOCAL_EVENTO`,
                e.`VAGAS_MAXIMAS`,
                e.`CRIADO_EM`,
                e.`ID_COORDENADOR`,
                e.`ID_DIRETOR`
            FROM `INSCRICAO` i
            JOIN `EVENTO` e ON i.`ID_EVENTO` = e.`ID_EVENTO`
            WHERE i.`ID_ALUNO` = :alunoId
            ORDER BY i.`ID_INSCRICAO` ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':alunoId' => $alunoId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Inscricao::fromDatabase($row), $rows);
    }

    public function getByEvento(int $eventoId): array
    {
        $this->ensureEventoExists($eventoId);

        $sql = '
            SELECT 
                i.*,
                a.`NOME_ALUNO` AS ALUNO_NOME,
                a.`EMAIL_ALUNO` AS ALUNO_EMAIL,
                a.`RA_ALUNO` AS ALUNO_RA,
                a.`CRIADO_EM` AS ALUNO_CRIADO_EM
            FROM `INSCRICAO` i
            JOIN `ALUNO` a ON i.`ID_ALUNO` = a.`ID_ALUNO`
            WHERE i.`ID_EVENTO` = :eventoId
            ORDER BY i.`ID_INSCRICAO` ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eventoId' => $eventoId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Inscricao::fromDatabase($row), $rows);
    }

    public function cancelarInscricao(int $inscricaoId, int $alunoId): void
    {
        $stmt = $this->db->prepare('SELECT * FROM `INSCRICAO` WHERE `ID_INSCRICAO` = :id LIMIT 1');
        $stmt->execute([':id' => $inscricaoId]);
        $inscricao = $stmt->fetch();

        if (!$inscricao) {
            throw new NotFoundException("Inscrição com ID {$inscricaoId} não encontrada.");
        }

        // Garante que o aluno só cancele a própria inscrição
        if ((int)$inscricao['ID_ALUNO'] !== $alunoId) {
            throw new ForbiddenException('Você não tem permissão para cancelar esta inscrição.');
        }

        $stmt = $this->db->prepare('DELETE FROM `INSCRICAO` WHERE `ID_INSCRICAO` = :id');
        $stmt->execute([':id' => $inscricaoId]);
    }

    public function getPresencaConfirmada(int $eventoId): array
    {
        $sql = '
            SELECT 
                i.*,
                a.`NOME_ALUNO` AS ALUNO_NOME,
                a.`EMAIL_ALUNO` AS ALUNO_EMAIL,
                a.`RA_ALUNO` AS ALUNO_RA,
                a.`CRIADO_EM` AS ALUNO_CRIADO_EM
            FROM `INSCRICAO` i
            JOIN `ALUNO` a ON i.`ID_ALUNO` = a.`ID_ALUNO`
            WHERE i.`ID_EVENTO` = :eventoId AND i.`PRESENCA_CONFIRMADA` = 1
            ORDER BY i.`ID_INSCRICAO` ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eventoId' => $eventoId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Inscricao::fromDatabase($row), $rows);
    }

    public function atualizarPresenca(int $inscricaoId, bool $presenca): Inscricao
    {
        $stmt = $this->db->prepare('SELECT * FROM `INSCRICAO` WHERE `ID_INSCRICAO` = :id LIMIT 1');
        $stmt->execute([':id' => $inscricaoId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new NotFoundException("Inscrição com ID {$inscricaoId} não encontrada.");
        }

        $stmt = $this->db->prepare('
            UPDATE `INSCRICAO`
            SET `PRESENCA_CONFIRMADA` = :presenca
            WHERE `ID_INSCRICAO` = :id
        ');
        $stmt->execute([
            ':presenca' => $presenca ? 1 : 0,
            ':id'       => $inscricaoId,
        ]);

        $row['PRESENCA_CONFIRMADA'] = $presenca ? 1 : 0;
        return Inscricao::fromDatabase($row);
    }

    private function isAlunoInscrito(int $alunoId, int $eventoId): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM `INSCRICAO`
            WHERE `ID_ALUNO` = :alunoId AND `ID_EVENTO` = :eventoId
        ');
        $stmt->execute([
            ':alunoId'  => $alunoId,
            ':eventoId' => $eventoId,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function isEventoLotado(int $eventoId, int $vagasMaximas): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM `INSCRICAO`
            WHERE `ID_EVENTO` = :eventoId
        ');
        $stmt->execute([':eventoId' => $eventoId]);
        $totalInscritos = (int)$stmt->fetchColumn();

        return $totalInscritos >= $vagasMaximas;
    }

    private function ensureEventoExists(int $eventoId): void
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `EVENTO` WHERE `ID_EVENTO` = :eventoId');
        $stmt->execute([':eventoId' => $eventoId]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new NotFoundException("Evento com ID {$eventoId} não encontrado.");
        }
    }
}
