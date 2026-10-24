<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Common\AppClaims;
use App\Common\AppRoles;
use App\DTOs\Auth\LoginDto;
use App\DTOs\Auth\RegisterAlunoDto;
use App\DTOs\Eventos\CreateEventoDto;
use App\DTOs\Inscricoes\ConfirmarPresencaDto;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Services\JwtService;

echo "======================================================\n";
echo " TESTES UNITÁRIOS E DE VALIDAÇÃO DO BACKEND PHP \n";
echo "======================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(string $description, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] " . $description . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $description . "\n";
        $failed++;
    }
}

// ---------------------------------------------------------------------
// 1. Testes do JwtService
// ---------------------------------------------------------------------
echo "--- Testando JwtService ---\n";
$jwt = new JwtService();

$userId = 42;
$role = AppRoles::Coordenador;
$nome = 'Coordenador Teste';

$token = $jwt->generateToken($userId, $role, $nome);
assertTest('Token JWT gerado com sucesso (não vazio)', !empty($token) && substr_count($token, '.') === 2);

$payload = $jwt->validateToken($token);
assertTest('Payload contém claim userId = 42', (int)$payload[AppClaims::UserId] === 42);
assertTest('Payload contém claim role = Coordenador', $payload[AppClaims::Role] === 'Coordenador');
assertTest('Payload contém claim nome = Coordenador Teste', $payload[AppClaims::Nome] === 'Coordenador Teste');

// Teste de alteração de token (assinatura adulterada)
try {
    $tamperedToken = $token . 'bad';
    $jwt->validateToken($tamperedToken);
    assertTest('Token adulterado deveria falhar', false);
} catch (UnauthorizedException $e) {
    assertTest('Token adulterado rejeitado corretamente com UnauthorizedException', true);
}

// ---------------------------------------------------------------------
// 2. Testes de DTOs e Validação
// ---------------------------------------------------------------------
echo "\n--- Testando DTOs e Validações ---\n";

// Sucesso Aluno
$validAluno = new RegisterAlunoDto([
    'nome'  => 'Pedro Silva',
    'email' => 'pedro@teste.com',
    'ra'    => 'RA12345',
    'senha' => '123456',
]);
assertTest('RegisterAlunoDto criado com dados válidos', $validAluno->email === 'pedro@teste.com');

// Falha Aluno - senha curta
try {
    new RegisterAlunoDto([
        'nome'  => 'Pedro Silva',
        'email' => 'pedro@teste.com',
        'ra'    => 'RA12345',
        'senha' => '123',
    ]);
    assertTest('RegisterAlunoDto com senha < 6 deveria falhar', false);
} catch (ValidationException $e) {
    assertTest('RegisterAlunoDto com senha curta lançou ValidationException', isset($e->getErrors()['senha']));
}

// Falha Aluno - email inválido
try {
    new RegisterAlunoDto([
        'nome'  => 'Pedro Silva',
        'email' => 'email_invalido',
        'ra'    => 'RA12345',
        'senha' => '123456',
    ]);
    assertTest('RegisterAlunoDto com email inválido deveria falhar', false);
} catch (ValidationException $e) {
    assertTest('RegisterAlunoDto com email inválido lançou ValidationException', isset($e->getErrors()['email']));
}

// Sucesso Evento
$validEvento = new CreateEventoDto([
    'nomeEvento'   => 'Workshop de IA',
    'dataEvento'   => '2026-11-20 14:00:00',
    'local'        => 'Auditório B',
    'vagasMaximas' => 100,
]);
assertTest('CreateEventoDto criado com sucesso', $validEvento->vagasMaximas === 100);

// Falha Evento - vagas zero ou negativas
try {
    new CreateEventoDto([
        'nomeEvento'   => 'Workshop',
        'dataEvento'   => '2026-11-20 14:00:00',
        'local'        => 'Auditório B',
        'vagasMaximas' => 0,
    ]);
    assertTest('CreateEventoDto com vagas <= 0 deveria falhar', false);
} catch (ValidationException $e) {
    assertTest('CreateEventoDto com vagas <= 0 lançou ValidationException', isset($e->getErrors()['vagasMaximas']));
}

// ConfirmarPresencaDto
$presencaDtoTrue = new ConfirmarPresencaDto(['presencaConfirmada' => true]);
assertTest('ConfirmarPresencaDto booleano true', $presencaDtoTrue->presencaConfirmada === true);

$presencaDtoFalse = new ConfirmarPresencaDto(['presencaConfirmada' => false]);
assertTest('ConfirmarPresencaDto booleano false', $presencaDtoFalse->presencaConfirmada === false);

echo "\n======================================================\n";
echo " RESULTADO: {$passed} passaram, {$failed} falharam.\n";
echo "======================================================\n";

exit($failed > 0 ? 1 : 0);
