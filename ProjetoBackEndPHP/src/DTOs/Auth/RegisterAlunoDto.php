<?php

namespace App\DTOs\Auth;

use App\Exceptions\ValidationException;

class RegisterAlunoDto
{
    public string $nome;
    public string $email;
    public string $ra;
    public string $senha;

    public function __construct(array $data)
    {
        $errors = [];

        $nome = trim($data['nome'] ?? $data['Nome'] ?? '');
        if ($nome === '') {
            $errors['nome'] = 'O nome é obrigatório';
        } elseif (mb_strlen($nome) > 100) {
            $errors['nome'] = 'O nome deve ter no máximo 100 caracteres';
        }

        $email = trim($data['email'] ?? $data['Email'] ?? '');
        if ($email === '') {
            $errors['email'] = 'O email é obrigatório';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            $errors['email'] = 'Formato de email inválido';
        }

        $ra = trim($data['ra'] ?? $data['RA'] ?? $data['Ra'] ?? '');
        if ($ra === '') {
            $errors['ra'] = 'O RA é obrigatório';
        } elseif (mb_strlen($ra) > 20) {
            $errors['ra'] = 'O RA deve ter no máximo 20 caracteres';
        }

        $senha = (string)($data['senha'] ?? $data['Senha'] ?? '');
        if ($senha === '') {
            $errors['senha'] = 'A senha é obrigatória';
        } elseif (mb_strlen($senha) < 6) {
            $errors['senha'] = 'A senha deve ter no mínimo 6 caracteres';
        }

        if (!empty($errors)) {
            throw new ValidationException('Dados de cadastro inválidos.', $errors);
        }

        $this->nome = $nome;
        $this->email = $email;
        $this->ra = $ra;
        $this->senha = $senha;
    }
}
