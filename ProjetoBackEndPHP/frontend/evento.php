<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/components.php';

$user = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    flash('error', 'Evento inválido.');
    redirect('eventos.php');
}

$client = api();
$res = api_guard($client->get('eventos/' . $id));
if (!$res->ok() || !is_array($res->data)) {
    flash('error', $res->status === 404 ? 'Evento não encontrado.' : $res->errorText());
    redirect('eventos.php');
}
$ev = $res->data;
$encerrado = evento_encerrado($ev);
$podeGerir = can_manage($ev, $user);
$return = 'evento.php?id=' . $id;

$inscricaoId = null;
if (is_aluno($user)) {
    $ins = api_guard($client->get('inscricoes/minhas'));
    if ($ins->ok() && is_array($ins->data)) {
        foreach ($ins->data as $i) {
            if ((int)$i['eventoId'] === $id) {
                $inscricaoId = (int)$i['id'];
                break;
            }
        }
    }
}

$totalInscritos = null;
if ($podeGerir) {
    $lista = api_guard($client->get('inscricoes/eventos/' . $id . '/alunos'));
    if ($lista->ok() && is_array($lista->data)) {
        $totalInscritos = count($lista->data);
    }
}

render_header($ev['nomeEvento'], 'eventos');
?>
<p class="breadcrumb"><a href="eventos.php">← Voltar para eventos</a></p>

<article class="detail card">
    <header class="detail__head">
        <?= date_badge($ev['dataEvento']) ?>
        <div>
            <div class="event__tags">
                <?php if ($encerrado): ?><span class="tag tag--muted">Encerrado</span><?php else: ?><span class="tag tag--ok">Inscrições abertas</span><?php endif; ?>
                <?php if ($inscricaoId !== null): ?><span class="tag tag--ok">Você está inscrito</span><?php endif; ?>
                <?php if ($podeGerir): ?><span class="tag tag--accent">Seu evento</span><?php endif; ?>
            </div>
            <h1><?= e($ev['nomeEvento']) ?></h1>
        </div>
    </header>

    <dl class="facts">
        <div><dt>Data e hora</dt><dd><?= e(fmt_data_longa($ev['dataEvento'])) ?></dd></div>
        <div><dt>Local</dt><dd><?= e($ev['local']) ?></dd></div>
        <div><dt>Vagas</dt><dd>
            <?php if ($totalInscritos !== null): ?>
                <?= $totalInscritos ?> de <?= (int)$ev['vagasMaximas'] ?> preenchidas
                <span class="progress" role="img" aria-label="<?= $totalInscritos ?> de <?= (int)$ev['vagasMaximas'] ?> vagas"><span style="width: <?= min(100, (int)round($totalInscritos / max(1, (int)$ev['vagasMaximas']) * 100)) ?>%"></span></span>
            <?php else: ?>
                <?= (int)$ev['vagasMaximas'] ?> vagas no total
            <?php endif; ?>
        </dd></div>
        <div><dt>Organizador</dt><dd><?= e(organizador($ev)) ?></dd></div>
        <div><dt>Criado em</dt><dd><?= e(fmt_data_curta($ev['criadoEm'])) ?></dd></div>
    </dl>

    <footer class="detail__actions">
        <?php if (is_aluno($user)): ?>
            <?php if ($encerrado): ?>
                <span class="muted">Este evento já aconteceu.</span>
            <?php elseif ($inscricaoId === null): ?>
                <?= action_form('inscrever', ['evento_id' => $id], 'Quero me inscrever', $return, 'btn btn--primary') ?>
            <?php else: ?>
                <?= action_form('cancelar', ['inscricao_id' => $inscricaoId], 'Cancelar inscrição', $return, 'btn btn--danger-ghost', 'Deseja cancelar sua inscrição neste evento?') ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($podeGerir): ?>
            <a class="btn btn--primary" href="inscritos.php?evento=<?= $id ?>">Ver inscritos e presença</a>
            <a class="btn btn--ghost" href="evento_form.php?id=<?= $id ?>">Editar</a>
            <?= action_form('excluir_evento', ['evento_id' => $id], 'Excluir', 'eventos.php?filtro=meus', 'btn btn--danger-ghost', 'Excluir “' . $ev['nomeEvento'] . '”? As inscrições também serão removidas e a ação não pode ser desfeita.') ?>
        <?php endif; ?>
    </footer>
</article>
<?php render_footer();
