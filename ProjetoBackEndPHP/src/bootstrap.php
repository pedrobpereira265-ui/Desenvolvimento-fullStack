<?php

/**
 * Bootstrap da aplicação: define fuso horário, carrega dependências e registra autoloader.
 */

declare(strict_types=1);

date_default_timezone_set('UTC');

// 1. Carregador de classes do Composer (se instalado)
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// 2. Autoloader PSR-4 nativo (zero-config, fallback automático para XAMPP)
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\Config\\')) {
        $relative = substr($class, strlen('App\\Config\\'));
        $file = __DIR__ . '/../config/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    if (str_starts_with($class, 'App\\')) {
        $relative = substr($class, strlen('App\\'));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
