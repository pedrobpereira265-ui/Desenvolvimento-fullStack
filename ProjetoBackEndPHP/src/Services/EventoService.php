<?php

namespace App\Services;

use App\Common\AppRoles;
use App\Config\Database;
use App\DTOs\Eventos\CreateEventoDto;
use App\DTOs\Eventos\UpdateEventoDto;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Models\Evento;
use PDO;

class EventoService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getAll(): array
    {
        $sql = '
            SELECT 
                e.*,
                c.NOME_COORDENADOR AS COORD_NOME,
                c.EMAIL_COORDENADOR AS COORD_EMAIL,
                c.SENHA_COORDENADOR AS COORD_SENHA,
                c.CRIADO_EM AS COORD_CRIADO_EM,
                d.NOME_DIRETOR AS DIR_NOME,
                d.EMAIL_DIRETOR AS DIR_EMAIL,
                d.SENHA_DIRETOR AS DIR_SENHA,
                d.CRIADO_EM AS DIR_CRIADO_EM
            FROM `EVENTO` e
            LEFT JOIN `COORDENADOR` c ON e.`ID_COORDENADOR` = c.`ID_COORDENADOR`
            LEFT JOIN `DIRETOR` d ON e.`ID_DIRETOR` = d.`ID_DIRETOR`
            ORDER BY e.`ID_EVENTO` ASC
        ';

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Evento::fromDatabase($row), $rows);
    }

    public function getById(int $id): Evento
    {
        $sql = '
            SELECT 
                e.*,
                c.NOME_COORDENADOR AS COORD_NOME,
                c.EMAIL_COORDENADOR AS COORD_EMAIL,
                c.SENHA_COORDENADOR AS COORD_SENHA,
                c.CRIADO_EM AS COORD_CRIADO_EM,
                d.NOME_DIRETOR AS DIR_NOME,
                d.EMAIL_DIRETOR AS DIR_EMAIL,
                d.SENHA_DIRETOR AS DIR_SENHA,
                d.CRIADO_EM AS DIR_CRIADO_EM
            FROM `EVENTO` e
            LEFT JOIN `COORDENADOR` c ON e.`ID_COORDENADOR` = c.`ID_COORDENADOR`
            LEFT JOIN `DIRETOR` d ON e.`ID_DIRETOR` = d.`ID_DIRETOR`
            WHERE e.`ID_EVENTO` = :id
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new NotFoundException("Evento com ID {$id} não encontrado.");
        }

        return Evento::fromDatabase($row);
    }

    public function getByDiretor(int $diretorId): array
    {
        $sql = '
            SELECT 
                e.*,
                c.NOME_COORDENADOR AS COORD_NOME,
                c.EMAIL_COORDENADOR AS COORD_EMAIL,
                c.SENHA_COORDENADOR AS COORD_SENHA,
                c.CRIADO_EM AS COORD_CRIADO_EM,
                d.NOME_DIRETOR AS DIR_NOME,
                d.EMAIL_DIRETOR AS DIR_EMAIL,
                d.SENHA_DIRETOR AS DIR_SENHA,
                d.CRIADO_EM AS DIR_CRIADO_EM
            FROM `EVENTO` e
            LEFT JOIN `COORDENADOR` c ON e.`ID_COORDENADOR` = c.`ID_COORDENADOR`
            LEFT JOIN `DIRETOR` d ON e.`ID_DIRETOR` = d.`ID_DIRETOR`
            WHERE e.`ID_DIRETOR` = :diretorId
            ORDER BY e.`ID_EVENTO` ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':diretorId' => $diretorId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Evento::fromDatabase($row), $rows);
    }

    public function getByCoordenador(int $coordenadorId): array
    {
        $sql = '
            SELECT 
                e.*,
                c.NOME_COORDENADOR AS COORD_NOME,
                c.EMAIL_COORDENADOR AS COORD_EMAIL,
                c.SENHA_COORDENADOR AS COORD_SENHA,
                c.CRIADO_EM AS COORD_CRIADO_EM,
                d.NOME_DIRETOR AS DIR_NOME,
                d.EMAIL_DIRETOR AS DIR_EMAIL,
                d.SENHA_DIRETOR AS DIR_SENHA,
                d.CRIADO_EM AS DIR_CRIADO_EM
            FROM `EVENTO` e
            LEFT JOIN `COORDENADOR` c ON e.`ID_COORDENADOR` = c.`ID_COORDENADOR`
            LEFT JOIN `DIRETOR` d ON e.`ID_DIRETOR` = d.`ID_DIRETOR`
            WHERE e.`ID_COORDENADOR` = :coordenadorId
            ORDER BY e.`ID_EVENTO` ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':coordenadorId' => $coordenadorId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => Evento::fromDatabase($row), $rows);
    }

    public function create(CreateEventoDto $dto, int $userId, string $role): Evento
    {
        $coordenadorId = null;
        $diretorId = null;

        if ($role === AppRoles::Coordenador) {
            $coordenadorId = $userId;
        } elseif ($role === AppRoles::Diretor) {
            $diretorId = $userId;
        } else {
            throw new ForbiddenException('Perfil sem permissão para criar eventos.');
        }

        $criadoEm = date('Y-m-d H:i:s');

        $sql = '
            INSERT INTO `EVENTO` (
                `NOME_EVENTO`, `DATA_EVENTO`, `LOCAL_EVENTO`, `VAGAS_MAXIMAS`, `CRIADO_EM`, `ID_COORDENADOR`, `ID_DIRETOR`
            ) VALUES (
                :nome, :dataEvento, :local, :vagas, :criadoEm, :coordenadorId, :diretorId
            )
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome'          => $dto->nomeEvento,
            ':dataEvento'    => $dto->dataEvento,
            ':local'         => $dto->local,
            ':vagas'         => $dto->vagasMaximas,
            ':criadoEm'      => $criadoEm,
            ':coordenadorId' => $coordenadorId,
            ':diretorId'     => $diretorId,
        ]);

        $id = (int)$this->db->lastInsertId();
        return $this->getById($id);
    }

    public function update(int $id, UpdateEventoDto $dto, int $userId, string $role): Evento
    {
        $evento = $this->getById($id);

        $this->ensureCanManage($evento, $userId, $role);

        $fields = [];
        $params = [':id' => $id];

        if ($dto->nomeEvento !== null) {
            $fields[] = '`NOME_EVENTO` = :nome';
            $params[':nome'] = $dto->nomeEvento;
        }
        if ($dto->dataEvento !== null) {
            $fields[] = '`DATA_EVENTO` = :dataEvento';
            $params[':dataEvento'] = $dto->dataEvento;
        }
        if ($dto->local !== null) {
            $fields[] = '`LOCAL_EVENTO` = :local';
            $params[':local'] = $dto->local;
        }
        if ($dto->vagasMaximas !== null) {
            $fields[] = '`VAGAS_MAXIMAS` = :vagas';
            $params[':vagas'] = $dto->vagasMaximas;
        }

        if (!empty($fields)) {
            $sql = 'UPDATE `EVENTO` SET ' . implode(', ', $fields) . ' WHERE `ID_EVENTO` = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }

        return $this->getById($id);
    }

    public function delete(int $id, int $userId, string $role): void
    {
        $evento = $this->getById($id);

        $this->ensureCanManage($evento, $userId, $role);

        $stmt = $this->db->prepare('DELETE FROM `EVENTO` WHERE `ID_EVENTO` = :id');
        $stmt->execute([':id' => $id]);
    }

    private function ensureCanManage(Evento $evento, int $userId, string $role): void
    {
        $canManage = match ($role) {
            AppRoles::Diretor     => ($evento->diretorId === $userId),
            AppRoles::Coordenador => ($evento->coordenadorId === $userId),
            default               => false,
        };

        if (!$canManage) {
            throw new ForbiddenException('Você não tem permissão para gerenciar este evento.');
        }
    }
}
