<?php

declare(strict_types=1);

/** Selo de data (dia + mês) usado nos cards. */
function date_badge(string $iso): string
{
    $d = evento_dt($iso);
    return '<time class="datebadge" datetime="' . e($d->format('c')) . '">'
        . '<span class="datebadge__day">' . e($d->format('d')) . '</span>'
        . '<span class="datebadge__month">' . e(MESES_ABREV[(int)$d->format('n') - 1]) . '</span>'
        . '</time>';
}

/** Formulário de ação (POST -> acoes.php) com CSRF e retorno à página atual. */
function action_form(string $acao, array $fields, string $label, string $return, string $class = 'btn', ?string $confirm = null): string
{
    $html = '<form method="post" action="acoes.php" class="inline-form">' . csrf_field();
    $html .= '<input type="hidden" name="acao" value="' . e($acao) . '">';
    $html .= '<input type="hidden" name="return" value="' . e($return) . '">';
    foreach ($fields as $name => $value) {
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
    }
    $html .= '<button type="submit" class="' . e($class) . '"'
        . ($confirm !== null ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . e($label) . '</button></form>';
    return $html;
}

function render_evento_card(array $ev, ?int $inscricaoId, string $return): void
{
    $user = current_user();
    $encerrado = evento_encerrado($ev);
    $busca = mb_strtolower($ev['nomeEvento'] . ' ' . $ev['local'] . ' ' . organizador($ev));
    ?>
    <article class="event card<?= $encerrado ? ' event--past' : '' ?>" data-filter-item data-search="<?= e($busca) ?>">
        <?= date_badge($ev['dataEvento']) ?>
        <div class="event__body">
            <div class="event__tags">
                <?php if ($encerrado): ?><span class="tag tag--muted">Encerrado</span><?php endif; ?>
                <?php if ($inscricaoId !== null): ?><span class="tag tag--ok">Inscrito</span><?php endif; ?>
                <?php if (can_manage($ev, $user)): ?><span class="tag tag--accent">Seu evento</span><?php endif; ?>
            </div>
            <h3 class="event__title"><a href="evento.php?id=<?= (int)$ev['id'] ?>"><?= e($ev['nomeEvento']) ?></a></h3>
            <ul class="meta">
                <li><span aria-hidden="true">🕒</span> <?= e(fmt_data_longa($ev['dataEvento'])) ?></li>
                <li><span aria-hidden="true">📍</span> <?= e($ev['local']) ?></li>
                <li><span aria-hidden="true">👥</span> <?= (int)$ev['vagasMaximas'] ?> vagas</li>
                <li><span aria-hidden="true">🎓</span> <?= e(organizador($ev)) ?></li>
            </ul>
        </div>
        <div class="event__actions">
            <a class="btn btn--ghost btn--sm" href="evento.php?id=<?= (int)$ev['id'] ?>">Detalhes</a>
            <?php if (is_aluno($user) && !$encerrado): ?>
                <?php if ($inscricaoId === null): ?>
                    <?= action_form('inscrever', ['evento_id' => $ev['id']], 'Inscrever-se', $return, 'btn btn--primary btn--sm') ?>
                <?php else: ?>
                    <?= action_form('cancelar', ['inscricao_id' => $inscricaoId], 'Cancelar inscrição', $return, 'btn btn--danger-ghost btn--sm', 'Deseja cancelar sua inscrição em “' . $ev['nomeEvento'] . '”?') ?>
                <?php endif; ?>
            <?php elseif (can_manage($ev, $user)): ?>
                <a class="btn btn--primary btn--sm" href="inscritos.php?evento=<?= (int)$ev['id'] ?>">Inscritos</a>
            <?php endif; ?>
        </div>
    </article>
    <?php
}
