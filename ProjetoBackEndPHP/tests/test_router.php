<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Router;

echo "======================================================\n";
echo " TESTES DE ROTEAMENTO (ROUTER HTTP DISPATCH) \n";
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

// 1. Rota GET simples
$router1 = new Router();
$r1Matched = false;
$router1->get('/api/eventos', function () use (&$r1Matched) {
    $r1Matched = true;
});
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/eventos';
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$router1->dispatch();
assertTest('GET /api/eventos casou com sucesso', $r1Matched);

// 2. Rota GET com parâmetro {id}
$router2 = new Router();
$capturedId = 0;
$router2->get('/api/eventos/{id}', function (int $id) use (&$capturedId) {
    $capturedId = $id;
});
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/eventos/789';
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$router2->dispatch();
assertTest('GET /api/eventos/{id} capturou id = 789', $capturedId === 789);

// 3. Rota POST com payload JSON
$router3 = new Router();
$receivedBody = null;
$router3->post('/api/eventos', function (array $body) use (&$receivedBody) {
    $receivedBody = $body;
});
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/eventos';
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_POST = ['nomeEvento' => 'Feira Tech'];
$router3->dispatch();
assertTest('POST /api/eventos recebeu corpo da requisição', is_array($receivedBody) && $receivedBody['nomeEvento'] === 'Feira Tech');

// 4. Rota PATCH com parâmetro e body
$router4 = new Router();
$patchParam = 0;
$patchBody = null;
$router4->patch('/api/inscricoes/{inscricaoId}/confirmar-presenca', function (int $inscricaoId, array $body) use (&$patchParam, &$patchBody) {
    $patchParam = $inscricaoId;
    $patchBody = $body;
});
$_SERVER['REQUEST_METHOD'] = 'PATCH';
$_SERVER['REQUEST_URI'] = '/api/inscricoes/55/confirmar-presenca';
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_POST = ['presencaConfirmada' => true];
$router4->dispatch();
assertTest('PATCH com id e body capturados corretamente', $patchParam === 55 && isset($patchBody['presencaConfirmada']));

// 5. Suporte a prefixo de subpasta do Apache (/ProjetoBackEndPHP/api/...)
$router5 = new Router();
$subpathMatched = false;
$router5->get('/api/auth/me', function () use (&$subpathMatched) {
    $subpathMatched = true;
});
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/ProjetoBackEndPHP/api/auth/me';
$_SERVER['SCRIPT_NAME'] = '/ProjetoBackEndPHP/public/index.php';
$router5->dispatch();
assertTest('Suporte a subpasta /ProjetoBackEndPHP/api/auth/me', $subpathMatched);

echo "\n======================================================\n";
echo " RESULTADO: {$passed} passaram, {$failed} falharam.\n";
echo "======================================================\n";

exit($failed > 0 ? 1 : 0);
