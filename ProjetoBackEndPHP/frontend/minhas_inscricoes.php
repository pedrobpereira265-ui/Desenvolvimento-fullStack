<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/components.php';

$user = require_login(['Aluno']);

$res = api_guard(api()->get('inscricoes/minhas'));
$inscricoes = ($res->ok() && is_array($res->data)) ? $res->data : [];
$erroApi = $res->ok() ? null : $res->errorText();

usort($inscricoes, fn($a, $b) => strcmp($b['evento']['dataEvento'] ?? '', $a['evento']['dataEvento'] ?? ''));

render_header('Minhas inscrições', 'inscricoes');
?>
<section class="pagehead">
    <div>
        <h1>Minhas inscrições</h1>
        <p class="muted">Acompanhe os eventos em que você está inscrito e sua presença.</p>
    </div>
    <a class="btn btn--primary" href="eventos.php">Ver eventos</a>
</section>

<?php if ($erroApi): ?>
    <div class="alert alert--error" role="alert"><?= e($erroApi) ?></div>
<?php elseif (!$inscricoes): ?>
    <div class="empty card">
        <p>Você ainda não se inscreveu em nenhum evento.</p>
        <a class="btn btn--primary" href="eventos.php">Explorar eventos</a>
    </div>
<?php else: ?>
    <div class="list">
        <?php foreach ($inscricoes as $i): $ev = $i['evento'] ?? null; ?>
            <article class="row card">
                <?php if ($ev): ?>
                    <?= date_badge($ev['dataEvento']) ?>
                    <div class="row__body">
                        <h3 class="event__title"><a href="evento.php?id=<?= (int)$ev['id'] ?>"><?= e($ev['nomeEvento']) ?></a></h3>
                        <ul class="meta">
                            <li><span aria-hidden="true">🕒</span> <?= e(fmt_data_longa($ev['dataEvento'])) ?></li>
                            <li><span aria-hidden="true">📍</span> <?= e($ev['local']) ?></li>
                            <li><span aria-hidden="true">✍️</span> Inscrito em <?= e(fmt_data_curta($i['inscritoEm'])) ?></li>
                        </ul>
                    </div>
                    <div class="row__side">
                        <?php if ($i['presencaConfirmada']): ?>
                            <span class="tag tag--ok">Presença confirmada</span>
                        <?php elseif (evento_encerrado($ev)): ?>
                            <span class="tag tag--muted">Encerrado</span>
                        <?php else: ?>
                            <span class="tag tag--warn">Aguardando presença</span>
                        <?php endif; ?>
                        <?php if (!evento_encerrado($ev)): ?>
                            <?= action_form('cancelar', ['inscricao_id' => $i['id']], 'Cancelar', 'minhas_inscricoes.php', 'btn btn--danger-ghost btn--sm', 'Deseja cancelar sua inscrição em “' . $ev['nomeEvento'] . '”?') ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php render_footer();
