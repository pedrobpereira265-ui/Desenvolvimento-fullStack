<?php

/**
 * Ponto de entrada (Front Controller) da API REST PHP.
 * Suporta execução nativa no XAMPP ou via CLI Server (php -S localhost:5000 -t public).
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\EventosController;
use App\Controllers\InscricoesController;
use App\Router;

$router = new Router();

// Rota de Boas-Vindas e Health Check
$router->get('/', function () {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'online',
        'sistema' => 'API de Gestão de Eventos Escolares (PHP 8.1+ / MySQL)',
        'versao'  => '1.0.0',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

$router->get('/api', function () {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'online',
        'sistema' => 'API de Gestão de Eventos Escolares',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

// =====================================================================
// 1. Autenticação e Usuários (/api/auth)
// =====================================================================
$router->post('/api/auth/register/diretor', [AuthController::class, 'registerDiretor']);
$router->post('/api/auth/register/coordenador', [AuthController::class, 'registerCoordenador']);
$router->post('/api/auth/register/aluno', [AuthController::class, 'registerAluno']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->get('/api/auth/me', [AuthController::class, 'me']);

// =====================================================================
// 2. Eventos (/api/eventos)
// =====================================================================
// Rotas específicas antes de rotas com parâmetro dinâmico {id}
$router->get('/api/eventos/meus-eventos', [EventosController::class, 'getMeusEventosDiretor']);
$router->get('/api/eventos/meus-eventos-coordenador', [EventosController::class, 'getMeusEventosCoordenador']);
$router->get('/api/eventos', [EventosController::class, 'getAll']);
$router->get('/api/eventos/{id}', [EventosController::class, 'getById']);
$router->post('/api/eventos', [EventosController::class, 'create']);
$router->put('/api/eventos/{id}', [EventosController::class, 'update']);
$router->delete('/api/eventos/{id}', [EventosController::class, 'delete']);

// =====================================================================
// 3. Inscrições (/api/inscricoes)
// =====================================================================
$router->post('/api/inscricoes/eventos/{eventoId}', [InscricoesController::class, 'inscrever']);
$router->get('/api/inscricoes/minhas', [InscricoesController::class, 'getMinhasInscricoes']);
$router->delete('/api/inscricoes/{inscricaoId}', [InscricoesController::class, 'cancelarInscricao']);
$router->get('/api/inscricoes/eventos/{eventoId}/alunos', [InscricoesController::class, 'getAlunosByEvento']);
$router->get('/api/inscricoes/eventos/{eventoId}/presenca-confirmada', [InscricoesController::class, 'getPresencaConfirmada']);
$router->patch('/api/inscricoes/{inscricaoId}/confirmar-presenca', [InscricoesController::class, 'atualizarPresenca']);

// Executa o roteamento da requisição
$router->dispatch();
