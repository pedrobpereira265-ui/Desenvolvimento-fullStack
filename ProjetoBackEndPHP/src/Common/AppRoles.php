<?php

namespace App\Common;

class AppRoles
{
    public const Aluno = 'Aluno';
    public const Coordenador = 'Coordenador';
    public const Diretor = 'Diretor';
    public const Gestores = 'Diretor,Coordenador';

    /**
     * Verifica se uma role está contida em uma regra de roles permitidas (ex: 'Diretor,Coordenador').
     */
    public static function hasRole(string $userRole, string $allowedRoles): bool
    {
        $roles = array_map('trim', explode(',', $allowedRoles));
        return in_array($userRole, $roles, true);
    }
}
