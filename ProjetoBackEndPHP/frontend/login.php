<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('eventos.php');
}

$next = safe_local_url($_GET['next'] ?? $_POST['next'] ?? null);
$email = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string)($_POST['email'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $error = 'Informe e-mail e senha.';
    } else {
        $client = new ApiClient(api_base_url());
        $res = $client->post('auth/login', ['email' => $email, 'senha' => $senha]);

        if ($res->ok() && isset($res->data['token'])) {
            $token = (string)$res->data['token'];
            // Descobre o userId pelo endpoint /auth/me (o login devolve apenas token, role e nome)
            $me = (new ApiClient(api_base_url(), $token))->get('auth/me');
            if ($me->ok() && isset($me->data['userId'])) {
                session_regenerate_id(true);
                $_SESSION['auth'] = [
                    'token'  => $token,
                    'role'   => (string)$res->data['role'],
                    'nome'   => (string)$res->data['nome'],
                    'userId' => (int)$me->data['userId'],
                ];
                flash('success', 'Bem-vindo(a), ' . $res->data['nome'] . '!');
                redirect($next);
            }
            $error = $me->errorText();
        } else {
            $error = $res->status === 401 ? 'E-mail ou senha inválidos.' : $res->errorText();
        }
    }
}

render_header('Entrar');
?>
<section class="auth">
    <div class="auth__aside">
        <h1>Gestão de eventos escolares, <em>sem complicação</em>.</h1>
        <p>Veja os eventos da escola, inscreva-se em segundos e acompanhe sua presença. Coordenação e direção gerenciam tudo em um só lugar.</p>
        <ul class="ticks">
            <li>Alunos: inscrição e cancelamento em um clique</li>
            <li>Gestores: criação de eventos e controle de presença</li>
            <li>Login seguro com token JWT</li>
        </ul>
    </div>
    <div class="card auth__card">
        <h2>Entrar</h2>
        <p class="muted">Use o e-mail e a senha cadastrados.</p>

        <?php if ($error): ?>
            <div class="alert alert--error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <label class="field">
                <span class="field__label">E-mail</span>
                <input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus>
            </label>
            <label class="field">
                <span class="field__label">Senha</span>
                <span class="input-group">
                    <input type="password" name="senha" autocomplete="current-password" required>
                    <button type="button" class="input-group__btn" data-toggle-password aria-label="Mostrar senha">👁</button>
                </span>
            </label>
            <button class="btn btn--primary btn--block" type="submit">Entrar</button>
        </form>
        <p class="auth__alt">Ainda não tem conta? <a href="registrar.php">Cadastre-se</a></p>
    </div>
</section>
<?php render_footer();
