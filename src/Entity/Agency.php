<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Agence (site géographique) de l'entreprise.
 */
final class Agency
{
    /**
     * @param int    $id   Identifiant.
     * @param string $name Nom de la ville.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
