<?php

namespace App\Services;

use App\Common\AppRoles;
use App\Config\Database;
use App\DTOs\Auth\LoginDto;
use App\DTOs\Auth\RegisterAlunoDto;
use App\DTOs\Auth\RegisterCoordenadorDto;
use App\DTOs\Auth\RegisterDiretorDto;
use App\DTOs\Auth\TokenResponseDto;
use App\Exceptions\ConflictException;
use App\Exceptions\UnauthorizedException;
use App\Models\Aluno;
use App\Models\Coordenador;
use App\Models\Diretor;
use PDO;

class AuthService
{
    private PDO $db;
    private JwtService $jwtService;

    public function __construct(?PDO $db = null, ?JwtService $jwtService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->jwtService = $jwtService ?? new JwtService();
    }

    public function registerAluno(RegisterAlunoDto $dto): Aluno
    {
        $this->ensureEmailIsAvailable($dto->email);

        // Verifica unicidade de RA entre alunos
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `ALUNO` WHERE `RA_ALUNO` = :ra');
        $stmt->execute([':ra' => $dto->ra]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new ConflictException('Este RA já está cadastrado.');
        }

        $senhaHash = password_hash($dto->senha, PASSWORD_BCRYPT);
        $criadoEm = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            INSERT INTO `ALUNO` (`NOME_ALUNO`, `EMAIL_ALUNO`, `RA_ALUNO`, `SENHA_ALUNO`, `CRIADO_EM`)
            VALUES (:nome, :email, :ra, :senha, :criadoEm)
        ');
        $stmt->execute([
            ':nome'     => $dto->nome,
            ':email'    => $dto->email,
            ':ra'       => $dto->ra,
            ':senha'    => $senhaHash,
            ':criadoEm' => $criadoEm,
        ]);

        $id = (int)$this->db->lastInsertId();

        $aluno = new Aluno();
        $aluno->id = $id;
        $aluno->nome = $dto->nome;
        $aluno->email = $dto->email;
        $aluno->ra = $dto->ra;
        $aluno->senhaHash = $senhaHash;
        $aluno->criadoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($criadoEm));
        return $aluno;
    }

    public function registerCoordenador(RegisterCoordenadorDto $dto): Coordenador
    {
        $this->ensureEmailIsAvailable($dto->email);

        $senhaHash = password_hash($dto->senha, PASSWORD_BCRYPT);
        $criadoEm = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            INSERT INTO `COORDENADOR` (`NOME_COORDENADOR`, `EMAIL_COORDENADOR`, `SENHA_COORDENADOR`, `CRIADO_EM`)
            VALUES (:nome, :email, :senha, :criadoEm)
        ');
        $stmt->execute([
            ':nome'     => $dto->nome,
            ':email'    => $dto->email,
            ':senha'    => $senhaHash,
            ':criadoEm' => $criadoEm,
        ]);

        $id = (int)$this->db->lastInsertId();

        $coordenador = new Coordenador();
        $coordenador->id = $id;
        $coordenador->nome = $dto->nome;
        $coordenador->email = $dto->email;
        $coordenador->senhaHash = $senhaHash;
        $coordenador->criadoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($criadoEm));
        return $coordenador;
    }

    public function registerDiretor(RegisterDiretorDto $dto): Diretor
    {
        $this->ensureEmailIsAvailable($dto->email);

        $senhaHash = password_hash($dto->senha, PASSWORD_BCRYPT);
        $criadoEm = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            INSERT INTO `DIRETOR` (`NOME_DIRETOR`, `EMAIL_DIRETOR`, `SENHA_DIRETOR`, `CRIADO_EM`)
            VALUES (:nome, :email, :senha, :criadoEm)
        ');
        $stmt->execute([
            ':nome'     => $dto->nome,
            ':email'    => $dto->email,
            ':senha'    => $senhaHash,
            ':criadoEm' => $criadoEm,
        ]);

        $id = (int)$this->db->lastInsertId();

        $diretor = new Diretor();
        $diretor->id = $id;
        $diretor->nome = $dto->nome;
        $diretor->email = $dto->email;
        $diretor->senhaHash = $senhaHash;
        $diretor->criadoEm = gmdate('Y-m-d\TH:i:s\Z', strtotime($criadoEm));
        return $diretor;
    }

    public function login(LoginDto $dto): TokenResponseDto
    {
        // 1. Tenta autenticar como Aluno
        $stmt = $this->db->prepare('SELECT * FROM `ALUNO` WHERE `EMAIL_ALUNO` = :email LIMIT 1');
        $stmt->execute([':email' => $dto->email]);
        $aluno = $stmt->fetch();
        if ($aluno && password_verify($dto->senha, $aluno['SENHA_ALUNO'])) {
            return $this->generateTokenResponse((int)$aluno['ID_ALUNO'], AppRoles::Aluno, $aluno['NOME_ALUNO']);
        }

        // 2. Tenta autenticar como Coordenador
        $stmt = $this->db->prepare('SELECT * FROM `COORDENADOR` WHERE `EMAIL_COORDENADOR` = :email LIMIT 1');
        $stmt->execute([':email' => $dto->email]);
        $coordenador = $stmt->fetch();
        if ($coordenador && password_verify($dto->senha, $coordenador['SENHA_COORDENADOR'])) {
            return $this->generateTokenResponse((int)$coordenador['ID_COORDENADOR'], AppRoles::Coordenador, $coordenador['NOME_COORDENADOR']);
        }

        // 3. Tenta autenticar como Diretor
        $stmt = $this->db->prepare('SELECT * FROM `DIRETOR` WHERE `EMAIL_DIRETOR` = :email LIMIT 1');
        $stmt->execute([':email' => $dto->email]);
        $diretor = $stmt->fetch();
        if ($diretor && password_verify($dto->senha, $diretor['SENHA_DIRETOR'])) {
            return $this->generateTokenResponse((int)$diretor['ID_DIRETOR'], AppRoles::Diretor, $diretor['NOME_DIRETOR']);
        }

        throw new UnauthorizedException('Email ou senha inválido.');
    }

    private function ensureEmailIsAvailable(string $email): void
    {
        // O login é unificado, logo o email precisa ser globalmente único entre todos os perfis
        $query = '
            SELECT 
                (SELECT COUNT(*) FROM `ALUNO` WHERE `EMAIL_ALUNO` = :email1) +
                (SELECT COUNT(*) FROM `COORDENADOR` WHERE `EMAIL_COORDENADOR` = :email2) +
                (SELECT COUNT(*) FROM `DIRETOR` WHERE `EMAIL_DIRETOR` = :email3) AS total
        ';
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':email1' => $email,
            ':email2' => $email,
            ':email3' => $email,
        ]);
        $total = (int)$stmt->fetchColumn();

        if ($total > 0) {
            throw new ConflictException('Este email já está cadastrado.');
        }
    }

    private function generateTokenResponse(int $userId, string $role, string $nome): TokenResponseDto
    {
        $token = $this->jwtService->generateToken($userId, $role, $nome);
        return new TokenResponseDto($token, $role, $nome);
    }
}
