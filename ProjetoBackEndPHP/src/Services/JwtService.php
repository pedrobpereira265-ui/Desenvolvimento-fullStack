<?php

namespace App\Services;

use App\Common\AppClaims;
use App\Config\JwtConfig;
use App\Exceptions\UnauthorizedException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Serviço responsável pela geração e validação de tokens JWT.
 * Suporta a biblioteca oficial 'firebase/php-jwt' (quando o Composer for executado)
 * e possui fallback nativo em PHP puro (RFC 7519 / HS256), garantindo funcionamento
 * imediato no XAMPP mesmo sem Composer instalado.
 */
class JwtService
{
    /**
     * Gera um token JWT com as claims padronizadas.
     */
    public function generateToken(int $userId, string $role, string $nome): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (JwtConfig::EXPIRES_IN_HOURS * 3600);

        $payload = [
            'iss' => JwtConfig::ISSUER,
            'aud' => JwtConfig::AUDIENCE,
            'iat' => $issuedAt,
            'exp' => $expire,
            'jti' => $this->generateUuidV4(),
            AppClaims::UserId => (string)$userId,
            AppClaims::Role   => $role,
            AppClaims::Nome   => $nome,
            // Claims do ASP.NET para compatibilidade cruzada
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/nameidentifier' => (string)$userId,
            'http://schemas.microsoft.com/ws/2008/06/identity/claims/role'         => $role,
        ];

        // Se a biblioteca firebase/php-jwt estiver presente
        if (class_exists(JWT::class)) {
            return JWT::encode($payload, JwtConfig::secretKey(), 'HS256');
        }

        // Fallback nativo HS256 (RFC 7519)
        return $this->nativeEncode($payload, JwtConfig::secretKey());
    }

    /**
     * Valida um token JWT e retorna o payload decodificado em array associativo.
     * Lança UnauthorizedException se o token for inválido ou expirado.
     */
    public function validateToken(string $token): array
    {
        if (class_exists(JWT::class) && class_exists(Key::class)) {
            try {
                $decoded = JWT::decode($token, new Key(JwtConfig::secretKey(), 'HS256'));
                return (array)$decoded;
            } catch (\Throwable $e) {
                throw new UnauthorizedException('Token inválido ou expirado: ' . $e->getMessage());
            }
        }

        // Fallback nativo
        return $this->nativeDecode($token, JwtConfig::secretKey());
    }

    private function nativeEncode(array $payload, string $secret): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    private function nativeDecode(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new UnauthorizedException('Formato de token JWT inválido.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSig = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret, true);
        $providedSig = $this->base64UrlDecode($signatureB64);

        if (!hash_equals($expectedSig, $providedSig)) {
            throw new UnauthorizedException('Assinatura do token JWT inválida.');
        }

        $payloadJson = $this->base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            throw new UnauthorizedException('Corpo do token JWT malformado.');
        }

        // Validação de expiração
        if (isset($payload['exp']) && time() >= $payload['exp']) {
            throw new UnauthorizedException('Token JWT expirado.');
        }

        // Validação de Issuer e Audience se presentes
        if (isset($payload['iss']) && $payload['iss'] !== JwtConfig::ISSUER) {
            throw new UnauthorizedException('Emissor do token inválido.');
        }
        if (isset($payload['aud']) && $payload['aud'] !== JwtConfig::AUDIENCE) {
            throw new UnauthorizedException('Audiência do token inválida.');
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }

    private function generateUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
