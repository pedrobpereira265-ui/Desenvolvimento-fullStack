<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/components.php';

$user = require_login();
$client = api();

$filtro = $_GET['filtro'] ?? 'proximos';
if (!in_array($filtro, ['proximos', 'encerrados', 'todos', 'meus'], true)) {
    $filtro = 'proximos';
}
if ($filtro === 'meus' && !is_gestor($user)) {
    $filtro = 'proximos';
}

$res = api_guard($client->get('eventos'));
$eventos = [];
$erroApi = null;
if ($res->ok() && is_array($res->data)) {
    $eventos = $res->data;
} else {
    $erroApi = $res->errorText();
}

// Alunos: mapa evento -> inscrição, para indicar "Inscrito" e permitir cancelar.
$inscricaoPorEvento = [];
if (is_aluno($user) && !$erroApi) {
    $ins = api_guard($client->get('inscricoes/minhas'));
    if ($ins->ok() && is_array($ins->data)) {
        foreach ($ins->data as $i) {
            $inscricaoPorEvento[(int)$i['eventoId']] = (int)$i['id'];
        }
    }
}

usort($eventos, fn($a, $b) => strcmp($a['dataEvento'], $b['dataEvento']));

$total = count($eventos);
$proximos = count(array_filter($eventos, fn($e) => !evento_encerrado($e)));
$meus = count(array_filter($eventos, fn($e) => can_manage($e, $user)));

$lista = array_values(array_filter($eventos, function ($e) use ($filtro, $user) {
    return match ($filtro) {
        'proximos'   => !evento_encerrado($e),
        'encerrados' => evento_encerrado($e),
        'meus'       => can_manage($e, $user),
        default      => true,
    };
}));
if ($filtro === 'encerrados') {
    $lista = array_reverse($lista);
}

$return = 'eventos.php?filtro=' . $filtro;

$abas = ['proximos' => 'Próximos', 'encerrados' => 'Encerrados', 'todos' => 'Todos'];
if (is_gestor($user)) {
    $abas = ['meus' => 'Meus eventos'] + $abas;
}

render_header('Eventos', 'eventos');
?>
<section class="pagehead">
    <div>
        <h1>Olá, <?= e($user['nome']) ?> 👋</h1>
        <p class="muted"><?= is_aluno($user) ? 'Escolha um evento e garanta sua vaga.' : 'Gerencie os eventos da escola e acompanhe as inscrições.' ?></p>
    </div>
    <?php if (is_gestor($user)): ?>
        <a class="btn btn--primary" href="evento_form.php">+ Novo evento</a>
    <?php endif; ?>
</section>

<section class="stats" aria-label="Resumo">
    <div class="stat card"><span class="stat__value"><?= $proximos ?></span><span class="stat__label">Próximos eventos</span></div>
    <div class="stat card"><span class="stat__value"><?= $total ?></span><span class="stat__label">Total de eventos</span></div>
    <?php if (is_aluno($user)): ?>
        <div class="stat card"><span class="stat__value"><?= count($inscricaoPorEvento) ?></span><span class="stat__label">Minhas inscrições</span></div>
    <?php else: ?>
        <div class="stat card"><span class="stat__value"><?= $meus ?></span><span class="stat__label">Eventos sob sua gestão</span></div>
    <?php endif; ?>
</section>

<?php if ($erroApi): ?>
    <div class="alert alert--error" role="alert"><?= e($erroApi) ?></div>
<?php else: ?>
    <div class="toolbar">
        <nav class="tabs" aria-label="Filtro de eventos">
            <?php foreach ($abas as $key => $label): ?>
                <a href="eventos.php?filtro=<?= e($key) ?>" class="tabs__item<?= $filtro === $key ? ' is-active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <label class="search">
            <span class="sr-only">Buscar eventos</span>
            <input type="search" placeholder="Buscar por nome, local ou organizador…" data-filter-input>
        </label>
    </div>

    <?php if (!$lista): ?>
        <div class="empty card">
            <p>Nenhum evento encontrado neste filtro.</p>
            <?php if (is_gestor($user)): ?><a class="btn btn--primary" href="evento_form.php">Criar o primeiro evento</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid-cards" data-filter-list>
            <?php foreach ($lista as $ev): ?>
                <?php render_evento_card($ev, $inscricaoPorEvento[(int)$ev['id']] ?? null, $return); ?>
            <?php endforeach; ?>
        </div>
        <div class="empty card" data-filter-empty hidden><p>Nenhum evento corresponde à sua busca.</p></div>
    <?php endif; ?>
<?php endif; ?>
<?php render_footer();
