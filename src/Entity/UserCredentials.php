<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Utilisateur accompagné de l'empreinte de son mot de passe.
 *
 * Objet à durée de vie courte, réservé à la vérification d'une connexion.
 */
final class UserCredentials
{
    /**
     * @param User   $user         Utilisateur.
     * @param string $passwordHash Empreinte produite par password_hash().
     */
    public function __construct(
        public readonly User $user,
        public readonly string $passwordHash,
    ) {
    }
}
