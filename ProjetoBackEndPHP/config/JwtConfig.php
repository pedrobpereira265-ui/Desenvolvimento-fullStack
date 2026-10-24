<?php

namespace App\Config;

/**
 * Parâmetros de configuração do JWT.
 *
 * A chave secreta NUNCA deve ficar versionada em produção.
 * Defina a variável de ambiente JWT_SECRET no servidor (ou no php.ini / .env do XAMPP).
 * O valor abaixo é apenas um fallback para o ambiente local de desenvolvimento.
 */
class JwtConfig
{
    public const ISSUER = 'GestaoEventosEscolares';
    public const AUDIENCE = 'GestaoEventosEscolaresClientes';
    public const EXPIRES_IN_HOURS = 8;

    /** Fallback usado somente em desenvolvimento local. */
    private const DEV_SECRET = 'dev-only-nao-use-em-producao-troque-pela-env-JWT_SECRET';

    /**
     * Retorna a chave secreta usada para assinar e validar os tokens.
     */
    public static function secretKey(): string
    {
        $secret = getenv('JWT_SECRET');

        if (is_string($secret) && $secret !== '') {
            return $secret;
        }

        return self::DEV_SECRET;
    }
}
