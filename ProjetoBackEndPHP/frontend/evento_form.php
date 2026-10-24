<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login(['Coordenador', 'Diretor']);
$client = api();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$editando = $id !== null;

$old = ['nomeEvento' => '', 'dataEvento' => '', 'local' => '', 'vagasMaximas' => '30'];

if ($editando) {
    $res = api_guard($client->get('eventos/' . $id));
    if (!$res->ok() || !is_array($res->data)) {
        flash('error', $res->status === 404 ? 'Evento não encontrado.' : $res->errorText());
        redirect('eventos.php');
    }
    if (!can_manage($res->data, $user)) {
        flash('error', 'Você só pode editar eventos criados por você.');
        redirect('evento.php?id=' . $id);
    }
    $old = [
        'nomeEvento'   => $res->data['nomeEvento'],
        'dataEvento'   => dt_para_input($res->data['dataEvento']),
        'local'        => $res->data['local'],
        'vagasMaximas' => (string)$res->data['vagasMaximas'],
    ];
}

$errors = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach (array_keys($old) as $campo) {
        $old[$campo] = trim((string)($_POST[$campo] ?? ''));
    }

    $utc = input_para_utc($old['dataEvento']);
    if ($utc === null) {
        $errors['dataEvento'] = 'Informe uma data e hora válidas.';
    }
    if (!ctype_digit($old['vagasMaximas']) || (int)$old['vagasMaximas'] < 1) {
        $errors['vagasMaximas'] = 'O evento precisa ter pelo menos uma vaga.';
    }

    if (!$errors) {
        $payload = [
            'nomeEvento'   => $old['nomeEvento'],
            'dataEvento'   => $utc,
            'local'        => $old['local'],
            'vagasMaximas' => (int)$old['vagasMaximas'],
        ];
        $res = api_guard($editando ? $client->put('eventos/' . $id, $payload) : $client->post('eventos', $payload));

        if ($res->ok() && isset($res->data['id'])) {
            flash('success', $editando ? 'Evento atualizado com sucesso.' : 'Evento criado com sucesso.');
            redirect('evento.php?id=' . (int)$res->data['id']);
        }
        $errors = $res->errors;
        $error = $res->message;
    }
}

render_header($editando ? 'Editar evento' : 'Novo evento', 'novo');
?>
<p class="breadcrumb"><a href="<?= $editando ? 'evento.php?id=' . $id : 'eventos.php' ?>">← Voltar</a></p>

<section class="card form-card">
    <h1><?= $editando ? 'Editar evento' : 'Novo evento' ?></h1>
    <p class="muted">Os horários são informados no fuso de Brasília.</p>

    <?php if ($error): ?>
        <div class="alert alert--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="form" novalidate>
        <?= csrf_field() ?>
        <label class="field">
            <span class="field__label">Nome do evento</span>
            <input type="text" name="nomeEvento" maxlength="100" value="<?= e($old['nomeEvento']) ?>" required autofocus>
            <?= field_error($errors, 'nomeEvento') ?>
        </label>
        <div class="grid-2">
            <label class="field">
                <span class="field__label">Data e hora</span>
                <input type="datetime-local" name="dataEvento" value="<?= e($old['dataEvento']) ?>" required>
                <?= field_error($errors, 'dataEvento') ?>
            </label>
            <label class="field">
                <span class="field__label">Vagas máximas</span>
                <input type="number" name="vagasMaximas" min="1" step="1" value="<?= e($old['vagasMaximas']) ?>" required>
                <?= field_error($errors, 'vagasMaximas') ?>
            </label>
        </div>
        <label class="field">
            <span class="field__label">Local</span>
            <input type="text" name="local" maxlength="100" value="<?= e($old['local']) ?>" required>
            <?= field_error($errors, 'local') ?>
        </label>
        <div class="form__actions">
            <a class="btn btn--ghost" href="<?= $editando ? 'evento.php?id=' . $id : 'eventos.php' ?>">Cancelar</a>
            <button class="btn btn--primary" type="submit"><?= $editando ? 'Salvar alterações' : 'Criar evento' ?></button>
        </div>
    </form>
</section>
<?php render_footer();
