<?php

namespace App\DTOs\Auth;

class TokenResponseDto
{
    public string $token;
    public string $role;
    public string $nome;

    public function __construct(string $token, string $role, string $nome)
    {
        $this->token = $token;
        $this->role = $role;
        $this->nome = $nome;
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'role'  => $this->role,
            'nome'  => $this->nome,
        ];
    }
}
