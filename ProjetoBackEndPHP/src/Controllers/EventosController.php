<?php

namespace App\Controllers;

use App\Common\AppRoles;
use App\DTOs\Eventos\CreateEventoDto;
use App\DTOs\Eventos\UpdateEventoDto;
use App\Middleware\AuthMiddleware;
use App\Services\EventoService;

class EventosController
{
    private EventoService $eventoService;

    public function __construct(?EventoService $eventoService = null)
    {
        $this->eventoService = $eventoService ?? new EventoService();
    }

    public function getAll(): void
    {
        AuthMiddleware::authenticate();
        $eventos = $this->eventoService->getAll();
        $this->jsonResponse($eventos, 200);
    }

    public function getById(int $id): void
    {
        AuthMiddleware::authenticate();
        $evento = $this->eventoService->getById($id);
        $this->jsonResponse($evento, 200);
    }

    public function getMeusEventosDiretor(): void
    {
        $user = AuthMiddleware::authenticate(AppRoles::Diretor);
        $eventos = $this->eventoService->getByDiretor($user['userId']);
        $this->jsonResponse($eventos, 200);
    }

    public function getMeusEventosCoordenador(): void
    {
        $user = AuthMiddleware::authenticate(AppRoles::Coordenador);
        $eventos = $this->eventoService->getByCoordenador($user['userId']);
        $this->jsonResponse($eventos, 200);
    }

    /**
     * Resolução da Inconsistência Documentada (Opção B):
     * Tanto Coordenador quanto Diretor têm autorização para criar eventos.
     * O EventoService vinculará automaticamente o ID ao respectivo perfil.
     */
    public function create(array $body): void
    {
        $user = AuthMiddleware::authenticate(AppRoles::Gestores);
        $dto = new CreateEventoDto($body);

        $evento = $this->eventoService->create($dto, $user['userId'], $user['role']);

        header('Location: /api/eventos/' . $evento->id);
        $this->jsonResponse($evento, 201);
    }

    /**
     * Resolução da Inconsistência Documentada (Opção B):
     * Gestores (Coordenador ou Diretor) podem atualizar seus respectivos eventos (checado por dono).
     */
    public function update(int $id, array $body): void
    {
        $user = AuthMiddleware::authenticate(AppRoles::Gestores);
        $dto = new UpdateEventoDto($body);

        $evento = $this->eventoService->update($id, $dto, $user['userId'], $user['role']);
        $this->jsonResponse($evento, 200);
    }

    /**
     * Resolução da Inconsistência Documentada (Opção B):
     * Gestores (Coordenador ou Diretor) podem excluir seus respectivos eventos (checado por dono).
     */
    public function delete(int $id): void
    {
        $user = AuthMiddleware::authenticate(AppRoles::Gestores);
        $this->eventoService->delete($id, $user['userId'], $user['role']);

        http_response_code(204);
        exit;
    }

    private function jsonResponse(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
