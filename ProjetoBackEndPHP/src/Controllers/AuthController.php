<?php

namespace App\Controllers;

use App\DTOs\Auth\LoginDto;
use App\DTOs\Auth\RegisterAlunoDto;
use App\DTOs\Auth\RegisterCoordenadorDto;
use App\DTOs\Auth\RegisterDiretorDto;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        $this->authService = $authService ?? new AuthService();
    }

    public function registerDiretor(array $body): void
    {
        $dto = new RegisterDiretorDto($body);
        $diretor = $this->authService->registerDiretor($dto);

        $this->jsonResponse($diretor, 201);
    }

    public function registerCoordenador(array $body): void
    {
        $dto = new RegisterCoordenadorDto($body);
        $coordenador = $this->authService->registerCoordenador($dto);

        $this->jsonResponse($coordenador, 201);
    }

    public function registerAluno(array $body): void
    {
        $dto = new RegisterAlunoDto($body);
        $aluno = $this->authService->registerAluno($dto);

        $this->jsonResponse($aluno, 201);
    }

    public function login(array $body): void
    {
        $dto = new LoginDto($body);
        $tokenResponse = $this->authService->login($dto);

        $this->jsonResponse($tokenResponse->toArray(), 200);
    }

    public function me(): void
    {
        $user = AuthMiddleware::authenticate();

        $this->jsonResponse([
            'message' => 'Token válido.',
            'userId'  => (string)$user['userId'],
            'role'    => (string)$user['role'],
            'nome'    => (string)$user['nome'],
        ], 200);
    }

    private function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
