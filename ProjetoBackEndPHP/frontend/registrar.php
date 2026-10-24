<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('eventos.php');
}

$tipos = [
    'aluno'       => 'Aluno',
    'coordenador' => 'Coordenador',
    'diretor'     => 'Diretor',
];

$old = ['tipo' => 'aluno', 'nome' => '', 'email' => '', 'ra' => ''];
$errors = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old['tipo'] = array_key_exists($_POST['tipo'] ?? '', $tipos) ? $_POST['tipo'] : 'aluno';
    $old['nome'] = trim((string)($_POST['nome'] ?? ''));
    $old['email'] = trim((string)($_POST['email'] ?? ''));
    $old['ra'] = trim((string)($_POST['ra'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    $confirma = (string)($_POST['senha_confirmacao'] ?? '');

    if ($senha !== $confirma) {
        $errors['senha_confirmacao'] = 'As senhas não conferem.';
    } else {
        $payload = ['nome' => $old['nome'], 'email' => $old['email'], 'senha' => $senha];
        if ($old['tipo'] === 'aluno') {
            $payload['ra'] = $old['ra'];
        }

        $client = new ApiClient(api_base_url());
        $res = $client->post('auth/register/' . $old['tipo'], $payload);

        if ($res->ok()) {
            // Já autentica o novo usuário para não obrigar um segundo passo.
            $login = $client->post('auth/login', ['email' => $old['email'], 'senha' => $senha]);
            if ($login->ok() && isset($login->data['token'])) {
                $token = (string)$login->data['token'];
                $me = (new ApiClient(api_base_url(), $token))->get('auth/me');
                if ($me->ok()) {
                    session_regenerate_id(true);
                    $_SESSION['auth'] = [
                        'token'  => $token,
                        'role'   => (string)$login->data['role'],
                        'nome'   => (string)$login->data['nome'],
                        'userId' => (int)$me->data['userId'],
                    ];
                    flash('success', 'Cadastro concluído! Bem-vindo(a), ' . $login->data['nome'] . '.');
                    redirect('eventos.php');
                }
            }
            flash('success', 'Cadastro concluído! Faça login para continuar.');
            redirect('login.php');
        }

        $errors = $res->errors;
        $error = $res->message;
    }
}

render_header('Criar conta');
?>
<section class="auth auth--single">
    <div class="card auth__card auth__card--wide">
        <h2>Criar conta</h2>
        <p class="muted">Preencha os dados para acessar o sistema.</p>

        <?php if ($error): ?>
            <div class="alert alert--error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form" novalidate>
            <?= csrf_field() ?>
            <fieldset class="segmented" aria-label="Tipo de conta">
                <?php foreach ($tipos as $value => $label): ?>
                    <label class="segmented__item">
                        <input type="radio" name="tipo" value="<?= e($value) ?>" <?= $old['tipo'] === $value ? 'checked' : '' ?> data-role-radio>
                        <span><?= e($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <label class="field">
                <span class="field__label">Nome completo</span>
                <input type="text" name="nome" maxlength="100" value="<?= e($old['nome']) ?>" required autocomplete="name">
                <?= field_error($errors, 'nome') ?>
            </label>
            <label class="field">
                <span class="field__label">E-mail</span>
                <input type="email" name="email" maxlength="100" value="<?= e($old['email']) ?>" required autocomplete="email">
                <?= field_error($errors, 'email') ?>
            </label>
            <label class="field" data-only-role="aluno">
                <span class="field__label">RA (Registro do Aluno)</span>
                <input type="text" name="ra" maxlength="20" value="<?= e($old['ra']) ?>">
                <?= field_error($errors, 'ra') ?>
            </label>
            <div class="grid-2">
                <label class="field">
                    <span class="field__label">Senha</span>
                    <input type="password" name="senha" minlength="6" required autocomplete="new-password">
                    <small class="field__hint">Mínimo de 6 caracteres.</small>
                    <?= field_error($errors, 'senha') ?>
                </label>
                <label class="field">
                    <span class="field__label">Confirmar senha</span>
                    <input type="password" name="senha_confirmacao" minlength="6" required autocomplete="new-password">
                    <?= field_error($errors, 'senha_confirmacao') ?>
                </label>
            </div>
            <button class="btn btn--primary btn--block" type="submit">Criar conta</button>
        </form>
        <p class="auth__alt">Já possui conta? <a href="login.php">Entrar</a></p>
    </div>
</section>
<?php render_footer();
