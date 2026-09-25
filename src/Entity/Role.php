<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Profil d'autorisation d'un utilisateur.
 */
enum Role: string
{
    case User = 'user';
    case Admin = 'admin';

    /**
     * Libellé affichable du rôle.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::User => 'Employé',
            self::Admin => 'Administrateur',
        };
    }
}
