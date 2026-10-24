<?php

namespace App\DTOs\Auth;

use App\Exceptions\ValidationException;

class LoginDto
{
    public string $email;
    public string $senha;

    public function __construct(array $data)
    {
        $errors = [];

        $email = trim($data['email'] ?? $data['Email'] ?? '');
        if ($email === '') {
            $errors['email'] = 'O email é obrigatório';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        $senha = (string)($data['senha'] ?? $data['Senha'] ?? '');
        if ($senha === '') {
            $errors['senha'] = 'A senha é obrigatória';
        } elseif (mb_strlen($senha) < 6) {
            $errors['senha'] = 'A senha deve ter no mínimo 6 caracteres';
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados de login inválidos.', $errors);
        }

        $this->email = $email;
        $this->senha = $senha;
    }
}
