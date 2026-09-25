<?php

declare(strict_types=1);

namespace App\Core\Validation;

use RuntimeException;

/**
 * Saisie refusée par un validateur.
 *
 * Transporte les messages d'erreur par champ et les valeurs saisies, afin de
 * réafficher le formulaire sans faire ressaisir l'utilisateur.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors Message d'erreur par nom de champ.
     * @param array<string, string> $old    Valeurs saisies à réafficher (jamais de mot de passe).
     */
    public function __construct(
        private readonly array $errors,
        private readonly array $old = [],
    ) {
        parent::__construct('Saisie invalide.');
    }

    /**
     * Messages d'erreur par champ.
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Valeurs saisies à réafficher.
     *
     * @return array<string, string>
     */
    public function old(): array
    {
        return $this->old;
    }
}
