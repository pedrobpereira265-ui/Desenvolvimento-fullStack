<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/components.php';

$user = require_login(['Coordenador', 'Diretor']);
$client = api();

$eventoId = filter_input(INPUT_GET, 'evento', FILTER_VALIDATE_INT);
if (!$eventoId || $eventoId < 1) {
    flash('error', 'Evento inválido.');
    redirect('eventos.php');
}

$resEv = api_guard($client->get('eventos/' . $eventoId));
if (!$resEv->ok() || !is_array($resEv->data)) {
    flash('error', $resEv->status === 404 ? 'Evento não encontrado.' : $resEv->errorText());
    redirect('eventos.php');
}
$ev = $resEv->data;

$filtro = ($_GET['filtro'] ?? 'todos') === 'confirmados' ? 'confirmados' : 'todos';

$resIns = api_guard($client->get('inscricoes/eventos/' . $eventoId . '/alunos'));
if (!$resIns->ok() || !is_array($resIns->data)) {
    flash('error', $resIns->errorText());
    redirect('evento.php?id=' . $eventoId);
}
$todos = $resIns->data;
$confirmados = array_values(array_filter($todos, fn($i) => !empty($i['presencaConfirmada'])));
$lista = $filtro === 'confirmados' ? $confirmados : $todos;

// Exportação CSV (compatível com Excel: BOM UTF-8 e separador ";")
if (($_GET['formato'] ?? '') === 'csv') {
    $safe = function (string $v): string {
        // Neutraliza injeção de fórmulas em planilhas
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    };
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inscritos-evento-' . $eventoId . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Nome', 'RA', 'E-mail', 'Inscrito em', 'Presença'], ';');
    foreach ($lista as $i) {
        fputcsv($out, [
            $safe((string)($i['aluno']['nome'] ?? '')),
            $safe((string)($i['aluno']['ra'] ?? '')),
            $safe((string)($i['aluno']['email'] ?? '')),
            fmt_data_curta($i['inscritoEm']),
            $i['presencaConfirmada'] ? 'Confirmada' : 'Pendente',
        ], ';');
    }
    fclose($out);
    exit;
}

$return = 'inscritos.php?evento=' . $eventoId . '&filtro=' . $filtro;
$pct = min(100, (int)round(count($todos) / max(1, (int)$ev['vagasMaximas']) * 100));

render_header('Inscritos', 'eventos');
?>
<p class="breadcrumb"><a href="evento.php?id=<?= $eventoId ?>">← <?= e($ev['nomeEvento']) ?></a></p>

<section class="pagehead">
    <div>
        <h1>Inscritos e presença</h1>
        <p class="muted"><?= e(fmt_data_longa($ev['dataEvento'])) ?> · <?= e($ev['local']) ?></p>
    </div>
    <a class="btn btn--ghost" href="inscritos.php?evento=<?= $eventoId ?>&filtro=<?= e($filtro) ?>&formato=csv">⬇ Exportar CSV</a>
</section>

<section class="stats" aria-label="Resumo">
    <div class="stat card">
        <span class="stat__value"><?= count($todos) ?><small>/<?= (int)$ev['vagasMaximas'] ?></small></span>
        <span class="stat__label">Vagas preenchidas</span>
        <span class="progress" role="img" aria-label="<?= $pct ?>% das vagas"><span style="width: <?= $pct ?>%"></span></span>
    </div>
    <div class="stat card"><span class="stat__value"><?= count($confirmados) ?></span><span class="stat__label">Presenças confirmadas</span></div>
    <div class="stat card"><span class="stat__value"><?= count($todos) - count($confirmados) ?></span><span class="stat__label">Pendentes</span></div>
</section>

<div class="toolbar">
    <nav class="tabs" aria-label="Filtro de inscritos">
        <a class="tabs__item<?= $filtro === 'todos' ? ' is-active' : '' ?>" href="inscritos.php?evento=<?= $eventoId ?>&filtro=todos">Todos (<?= count($todos) ?>)</a>
        <a class="tabs__item<?= $filtro === 'confirmados' ? ' is-active' : '' ?>" href="inscritos.php?evento=<?= $eventoId ?>&filtro=confirmados">Presença confirmada (<?= count($confirmados) ?>)</a>
    </nav>
    <label class="search">
        <span class="sr-only">Buscar inscritos</span>
        <input type="search" placeholder="Buscar por nome, RA ou e-mail…" data-filter-input>
    </label>
</div>

<?php if (!$lista): ?>
    <div class="empty card"><p><?= $filtro === 'confirmados' ? 'Nenhuma presença confirmada ainda.' : 'Ainda não há alunos inscritos neste evento.' ?></p></div>
<?php else: ?>
    <div class="card table-wrap" data-filter-list>
        <table class="table">
            <thead>
                <tr><th>Aluno</th><th>RA</th><th class="hide-sm">Inscrito em</th><th>Presença</th><th class="right">Ação</th></tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $i): $a = $i['aluno'] ?? []; ?>
                    <tr data-filter-item data-search="<?= e(mb_strtolower(($a['nome'] ?? '') . ' ' . ($a['ra'] ?? '') . ' ' . ($a['email'] ?? ''))) ?>">
                        <td>
                            <strong><?= e($a['nome'] ?? 'Aluno #' . $i['alunoId']) ?></strong><br>
                            <small class="muted"><?= e($a['email'] ?? '') ?></small>
                        </td>
                        <td><?= e($a['ra'] ?? '—') ?></td>
                        <td class="hide-sm"><?= e(fmt_data_curta($i['inscritoEm'])) ?></td>
                        <td><?= $i['presencaConfirmada'] ? '<span class="tag tag--ok">Confirmada</span>' : '<span class="tag tag--warn">Pendente</span>' ?></td>
                        <td class="right">
                            <?php if ($i['presencaConfirmada']): ?>
                                <?= action_form('presenca', ['inscricao_id' => $i['id'], 'valor' => '0'], 'Revogar', $return, 'btn btn--ghost btn--sm') ?>
                            <?php else: ?>
                                <?= action_form('presenca', ['inscricao_id' => $i['id'], 'valor' => '1'], 'Confirmar', $return, 'btn btn--primary btn--sm') ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="empty card" data-filter-empty hidden><p>Nenhum inscrito corresponde à sua busca.</p></div>
<?php endif; ?>
<?php render_footer();
