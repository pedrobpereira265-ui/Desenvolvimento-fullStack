<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

/**
 * Processa as ações (POST) que alteram dados na API e redireciona de volta
 * (padrão Post/Redirect/Get, evita reenvio de formulário ao atualizar a página).
 */
require_post();
$user = require_login();
$client = api();

$acao = (string)($_POST['acao'] ?? '');
$return = safe_local_url($_POST['return'] ?? null);

$intField = function (string $name): int {
    $v = filter_var($_POST[$name] ?? null, FILTER_VALIDATE_INT);
    return ($v !== false && $v > 0) ? $v : 0;
};

switch ($acao) {
    case 'inscrever':
        $eventoId = $intField('evento_id');
        $res = $eventoId ? api_guard($client->post('inscricoes/eventos/' . $eventoId)) : null;
        $ok = $res?->ok();
        flash($ok ? 'success' : 'error', $ok ? 'Inscrição realizada com sucesso!' : ($res?->errorText() ?? 'Evento inválido.'));
        break;

    case 'cancelar':
        $inscricaoId = $intField('inscricao_id');
        $res = $inscricaoId ? api_guard($client->delete('inscricoes/' . $inscricaoId)) : null;
        $ok = $res?->ok();
        flash($ok ? 'success' : 'error', $ok ? 'Inscrição cancelada.' : ($res?->errorText() ?? 'Inscrição inválida.'));
        break;

    case 'presenca':
        $inscricaoId = $intField('inscricao_id');
        $valor = ($_POST['valor'] ?? '') === '1';
        $res = $inscricaoId ? api_guard($client->patch('inscricoes/' . $inscricaoId . '/confirmar-presenca', ['presencaConfirmada' => $valor])) : null;
        $ok = $res?->ok();
        flash(
            $ok ? 'success' : 'error',
            $ok ? ($valor ? 'Presença confirmada.' : 'Presença revogada.') : ($res?->errorText() ?? 'Inscrição inválida.')
        );
        break;

    case 'excluir_evento':
        $eventoId = $intField('evento_id');
        $res = $eventoId ? api_guard($client->delete('eventos/' . $eventoId)) : null;
        $ok = $res?->ok();
        flash($ok ? 'success' : 'error', $ok ? 'Evento excluído.' : ($res?->errorText() ?? 'Evento inválido.'));
        if (!$ok) {
            $return = 'evento.php?id=' . $eventoId;
        }
        break;

    default:
        flash('error', 'Ação desconhecida.');
}

redirect($return);
