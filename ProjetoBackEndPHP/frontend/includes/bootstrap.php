<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ApiClient.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('GEE_FRONT');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set(APP_TZ);

/* ------------------------------------------------------------------ *
 * Utilidades gerais
 * ------------------------------------------------------------------ */

/** Escapa texto para HTML (proteção contra XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Aceita apenas destinos internos simples (ex.: "evento.php?id=3") contra open redirect. */
function safe_local_url(?string $url, string $fallback = 'eventos.php'): string
{
    if ($url !== null && preg_match('#^[a-z_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', $url)) {
        return $url;
    }
    return $fallback;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_pull(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ------------------------------------------------------------------ *
 * CSRF
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        flash('error', 'Sua sessão de formulário expirou. Tente novamente.');
        redirect(safe_local_url($_POST['return'] ?? null));
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        redirect('eventos.php');
    }
    csrf_verify();
}

/* ------------------------------------------------------------------ *
 * Autenticação / sessão
 * ------------------------------------------------------------------ */

function current_user(): ?array
{
    return $_SESSION['auth'] ?? null;
}

function is_gestor(?array $user = null): bool
{
    $user ??= current_user();
    return $user !== null && in_array($user['role'], ['Coordenador', 'Diretor'], true);
}

function is_aluno(?array $user = null): bool
{
    $user ??= current_user();
    return $user !== null && $user['role'] === 'Aluno';
}

/**
 * Exige login. Opcionalmente restringe a perfis específicos.
 *
 * @param string[]|null $roles
 */
function require_login(?array $roles = null): array
{
    $user = current_user();
    if ($user === null) {
        $next = basename($_SERVER['SCRIPT_NAME'] ?? '') . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        redirect('login.php?next=' . urlencode($next));
    }
    if ($roles !== null && !in_array($user['role'], $roles, true)) {
        flash('error', 'Seu perfil não tem permissão para acessar essa página.');
        redirect('eventos.php');
    }
    return $user;
}

function logout_session(): void
{
    unset($_SESSION['auth']);
    session_regenerate_id(true);
}

/** Cliente da API já autenticado com o token da sessão. */
function api(): ApiClient
{
    return new ApiClient(api_base_url(), current_user()['token'] ?? null);
}

/**
 * Executa uma chamada e trata automaticamente a expiração do token (401).
 */
function api_guard(ApiResponse $response): ApiResponse
{
    if ($response->status === 401 && current_user() !== null) {
        logout_session();
        flash('error', 'Sua sessão expirou. Faça login novamente.');
        redirect('login.php');
    }
    return $response;
}

/* ------------------------------------------------------------------ *
 * Regras de apresentação
 * ------------------------------------------------------------------ */

function can_manage(array $evento, ?array $user = null): bool
{
    $user ??= current_user();
    if ($user === null) {
        return false;
    }
    $id = (int)$user['userId'];
    if ($user['role'] === 'Coordenador') {
        return isset($evento['coordenadorId']) && (int)$evento['coordenadorId'] === $id;
    }
    if ($user['role'] === 'Diretor') {
        return isset($evento['diretorId']) && (int)$evento['diretorId'] === $id;
    }
    return false;
}

function organizador(array $evento): string
{
    return $evento['coordenador']['nome'] ?? $evento['diretor']['nome'] ?? 'Não informado';
}

function evento_dt(string $iso): DateTimeImmutable
{
    return (new DateTimeImmutable($iso))->setTimezone(new DateTimeZone(APP_TZ));
}

function evento_encerrado(array $evento): bool
{
    return evento_dt($evento['dataEvento']) < new DateTimeImmutable('now', new DateTimeZone(APP_TZ));
}

const MESES_ABREV = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
const DIAS_SEMANA = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];

function fmt_data_longa(string $iso): string
{
    $d = evento_dt($iso);
    return DIAS_SEMANA[(int)$d->format('w')] . ', ' . $d->format('d/m/Y') . ' às ' . $d->format('H:i');
}

function fmt_data_curta(string $iso): string
{
    return evento_dt($iso)->format('d/m/Y H:i');
}

/** Valor para <input type="datetime-local"> no fuso do usuário. */
function dt_para_input(string $iso): string
{
    return evento_dt($iso)->format('Y-m-d\TH:i');
}

/** Converte o valor do <input type="datetime-local"> (fuso do usuário) para UTC aceito pela API. */
function input_para_utc(string $value): ?string
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, new DateTimeZone(APP_TZ));
    if ($d === false) {
        return null;
    }
    return $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

/* ------------------------------------------------------------------ *
 * Layout
 * ------------------------------------------------------------------ */

function render_header(string $title, string $active = ''): void
{
    $user = current_user();
    $flashes = flash_pull();
    $nav = [];
    if ($user) {
        $nav['eventos'] = ['eventos.php', 'Eventos'];
        if (is_aluno($user)) {
            $nav['inscricoes'] = ['minhas_inscricoes.php', 'Minhas inscrições'];
        }
        if (is_gestor($user)) {
            $nav['novo'] = ['evento_form.php', 'Novo evento'];
        }
    }
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="topbar">
    <div class="container topbar__inner">
        <a class="brand" href="<?= $user ? 'eventos.php' : 'login.php' ?>">
            <span class="brand__mark" aria-hidden="true">◆</span>
            <span><?= e(APP_NAME) ?></span>
        </a>
        <?php if ($user): ?>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu" data-nav-toggle>
                <span class="sr-only">Abrir menu</span>☰
            </button>
            <nav id="menu" class="nav" aria-label="Principal">
                <?php foreach ($nav as $key => [$href, $label]): ?>
                    <a href="<?= e($href) ?>" class="nav__link<?= $active === $key ? ' is-active' : '' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
                <span class="nav__user">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['nome'], 0, 1))) ?></span>
                    <span class="nav__user-text">
                        <strong><?= e($user['nome']) ?></strong>
                        <small><?= e($user['role']) ?></small>
                    </span>
                </span>
                <form action="logout.php" method="post" class="nav__logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--ghost btn--sm">Sair</button>
                </form>
            </nav>
        <?php endif; ?>
    </div>
</header>

<main id="conteudo" class="container main">
    <?php if ($flashes): ?>
        <div class="toasts" role="status" aria-live="polite">
            <?php foreach ($flashes as $f): ?>
                <div class="toast toast--<?= e($f['type']) ?>" data-toast>
                    <span><?= e($f['message']) ?></span>
                    <button type="button" class="toast__close" data-toast-close aria-label="Fechar">×</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php
}

function render_footer(): void
{
    ?>
</main>
<footer class="footer">
    <div class="container">
        <small><?= e(APP_NAME) ?> · Front End PHP consumindo a API REST de Gestão de Eventos Escolares</small>
    </div>
</footer>
<dialog id="confirm-dialog" class="dialog">
    <form method="dialog">
        <h2 class="dialog__title">Confirmar ação</h2>
        <p class="dialog__text" data-dialog-text></p>
        <div class="dialog__actions">
            <button value="cancel" class="btn btn--ghost">Cancelar</button>
            <button value="ok" class="btn btn--danger" data-dialog-ok>Confirmar</button>
        </div>
    </form>
</dialog>
</body>
</html>
<?php
}

/** Mostra o erro de um campo vindo da API (ou de validação local). */
function field_error(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<span class="field__error" role="alert">' . e(is_array($errors[$field]) ? implode(' ', $errors[$field]) : $errors[$field]) . '</span>'
        : '';
}
