<?php

namespace App\Controllers;

use App\Common\AppRoles;
use App\DTOs\Inscricoes\ConfirmarPresencaDto;
use App\Middleware\AuthMiddleware;
use App\Services\InscricaoService;

class InscricoesController
{
    private InscricaoService $inscricaoService;

    public function __construct(?InscricaoService $inscricaoService = null)
    {
        $this->inscricaoService = $inscricaoService ?? new InscricaoService();
    }

    public function inscrever(int $eventoId): void
    {
        $user = AuthMiddleware::authenticate(AppRoles::Aluno);
        $inscricao = $this->inscricaoService->inscrever($user['userId'], $eventoId);

        header('Location: /api/inscricoes/minhas?id=' . $inscricao->id);
        $this->jsonResponse($inscricao, 201);
    }

    public function getMinhasInscricoes(): void
    {
        $user = AuthMiddleware::authenticate(AppRoles::Aluno);
        $inscricoes = $this->inscricaoService->getByAluno($user['userId']);
        $this->jsonResponse($inscricoes, 200);
    }

    public function cancelarInscricao(int $inscricaoId): void
    {
        $user = AuthMiddleware::authenticate(AppRoles::Aluno);
        $this->inscricaoService->cancelarInscricao($inscricaoId, $user['userId']);

        http_response_code(204);
        exit;
    }

    public function getAlunosByEvento(int $eventoId): void
    {
        AuthMiddleware::authenticate(AppRoles::Gestores);
        $inscritos = $this->inscricaoService->getByEvento($eventoId);
        $this->jsonResponse($inscritos, 200);
    }

    public function getPresencaConfirmada(int $eventoId): void
    {
        AuthMiddleware::authenticate(AppRoles::Gestores);
        $inscritos = $this->inscricaoService->getPresencaConfirmada($eventoId);
        $this->jsonResponse($inscritos, 200);
    }

    public function atualizarPresenca(int $inscricaoId, array $body): void
    {
        AuthMiddleware::authenticate(AppRoles::Gestores);
        $dto = new ConfirmarPresencaDto($body);

        $inscricao = $this->inscricaoService->atualizarPresenca($inscricaoId, $dto->presencaConfirmada);
        $this->jsonResponse($inscricao, 200);
    }

    private function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
