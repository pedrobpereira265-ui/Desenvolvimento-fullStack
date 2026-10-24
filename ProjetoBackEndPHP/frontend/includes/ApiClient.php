<?php

declare(strict_types=1);

/**
 * Resposta normalizada da API.
 */
final class ApiResponse
{
    public function __construct(
        public readonly int $status,
        public readonly mixed $data,
        public readonly ?string $message = null,
        public readonly array $errors = []
    ) {
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /** Mensagem amigável incluindo erros de validação por campo. */
    public function errorText(): string
    {
        $text = $this->message ?? 'Não foi possível concluir a operação.';
        if ($this->errors) {
            $text .= ' ' . implode(' ', array_map('strval', array_values($this->errors)));
        }
        return $text;
    }
}

/**
 * Cliente HTTP (cURL) para a API REST do Back End.
 */
final class ApiClient
{
    public function __construct(
        private string $baseUrl,
        private ?string $token = null
    ) {
    }

    public function get(string $path): ApiResponse
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $body = []): ApiResponse
    {
        return $this->request('POST', $path, $body);
    }

    public function put(string $path, array $body = []): ApiResponse
    {
        return $this->request('PUT', $path, $body);
    }

    public function patch(string $path, array $body = []): ApiResponse
    {
        return $this->request('PATCH', $path, $body);
    }

    public function delete(string $path): ApiResponse
    {
        return $this->request('DELETE', $path);
    }

    public function request(string $method, string $path, ?array $body = null): ApiResponse
    {
        $ch = curl_init($this->baseUrl . '/' . ltrim($path, '/'));

        $headers = ['Accept: application/json'];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $opts = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
        ];

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            error_log('[Front] Falha ao chamar a API: ' . $curlError);
            return new ApiResponse(
                0,
                null,
                'Não foi possível conectar à API. Verifique se o Back End está em execução (' . $this->baseUrl . ').'
            );
        }

        $data = $raw !== '' ? json_decode($raw, true) : null;
        $message = null;
        $errors = [];

        if (is_array($data) && isset($data['message']) && is_string($data['message'])) {
            $message = $data['message'];
            if (isset($data['errors']) && is_array($data['errors'])) {
                $errors = $data['errors'];
            }
        }

        if ($status >= 400 && $message === null) {
            $message = 'A API respondeu com erro HTTP ' . $status . '.';
        }

        return new ApiResponse($status, $data, $message, $errors);
    }
}
