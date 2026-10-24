<?php

namespace App\Middleware;

use App\Common\AppClaims;
use App\Common\AppRoles;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Services\JwtService;

class AuthMiddleware
{
    private static ?array $currentUser = null;

    /**
     * Valida o token JWT e opcionalmente verifica se a role do usuário é permitida.
     *
     * @param string|null $allowedRoles Roles separadas por vírgula (ex: 'Diretor,Coordenador')
     * @return array Dados do usuário autenticado (userId, role, nome)
     */
    public static function authenticate(?string $allowedRoles = null): array
    {
        $token = self::extractBearerToken();
        if (!$token) {
            throw new UnauthorizedException('Token de autenticação não fornecido.');
        }

        $jwtService = new JwtService();
        $payload = $jwtService->validateToken($token);

        $userId = (int)($payload[AppClaims::UserId] ?? $payload['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/nameidentifier'] ?? 0);
        $role = (string)($payload[AppClaims::Role] ?? $payload['http://schemas.microsoft.com/ws/2008/06/identity/claims/role'] ?? '');
        $nome = (string)($payload[AppClaims::Nome] ?? '');

        if ($userId <= 0 || $role === '') {
            throw new UnauthorizedException('Token com claims de identificação inválidas.');
        }

        // Validação de Roles se especificado
        if ($allowedRoles !== null) {
            if (!AppRoles::hasRole($role, $allowedRoles)) {
                throw new ForbiddenException('Acesso negado. Seu perfil (' . $role . ') não tem permissão para acessar este recurso.');
            }
        }

        self::$currentUser = [
            'userId' => $userId,
            'role'   => $role,
            'nome'   => $nome,
            'claims' => $payload,
        ];

        return self::$currentUser;
    }

    /**
     * Retorna o usuário autenticado da requisição atual.
     */
    public static function getUser(): ?array
    {
        return self::$currentUser;
    }

    /**
     * Extrai o token Bearer do cabeçalho HTTP Authorization de forma tolerante a diferentes servidores web.
     */
    private static function extractBearerToken(): ?string
    {
        $header = null;

        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
