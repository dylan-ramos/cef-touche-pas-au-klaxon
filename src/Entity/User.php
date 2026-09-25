<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Employé ou administrateur de l'application.
 *
 * L'empreinte du mot de passe n'appartient pas à cette entité : elle n'est
 * lue que lors de l'authentification (voir UserCredentials), ce qui évite de
 * la conserver en session ou de l'exposer à une vue.
 */
final class User
{
    /**
     * @param int    $id        Identifiant.
     * @param string $lastName  Nom de famille.
     * @param string $firstName Prénom.
     * @param string $phone     Téléphone (10 chiffres).
     * @param string $email     Adresse e-mail, identifiant de connexion.
     * @param Role   $role      Profil d'autorisation.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $lastName,
        public readonly string $firstName,
        public readonly string $phone,
        public readonly string $email,
        public readonly Role $role,
    ) {
    }

    /**
     * Prénom suivi du nom.
     *
     * @return string
     */
    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * Indique si l'utilisateur est administrateur.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Téléphone formaté par paires de chiffres (06 12 34 56 78).
     *
     * @return string
     */
    public function formattedPhone(): string
    {
        return trim(chunk_split($this->phone, 2, ' '));
    }
}
