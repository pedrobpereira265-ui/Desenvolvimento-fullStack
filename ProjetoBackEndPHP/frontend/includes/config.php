<?php

declare(strict_types=1);

/**
 * Configuração do Front End.
 *
 * A URL da API é descoberta automaticamente:
 *  - XAMPP/Apache (http://localhost/ProjetoBackEndPHP/frontend/...) => http://localhost/ProjetoBackEndPHP/api
 *  - Servidor embutido do PHP (php -S localhost:8000 -t frontend)    => http://localhost:5000/api
 *
 * Para forçar outra URL, defina a variável de ambiente FRONT_API_URL
 * (ex.: FRONT_API_URL="http://localhost:5000/api").
 */

const APP_NAME = 'Eventos Escolares';
const APP_TZ = 'America/Sao_Paulo';   // Fuso exibido ao usuário (a API trabalha em UTC)
const API_TIMEOUT = 10;               // Segundos

function api_base_url(): string
{
    $env = getenv('FRONT_API_URL');
    if (is_string($env) && $env !== '') {
        return rtrim($env, '/');
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strpos($script, '/frontend/');
    if ($pos !== false) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Evita Host header injection: só aceita caracteres válidos de host[:porta]
        if (!preg_match('/^[A-Za-z0-9.\-]+(:\d+)?$/', $host)) {
            $host = 'localhost';
        }
        return ($https ? 'https' : 'http') . '://' . $host . substr($script, 0, $pos) . '/api';
    }

    return 'http://localhost:5000/api';
}
