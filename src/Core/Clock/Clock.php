<?php

declare(strict_types=1);

namespace App\Core\Clock;

use DateTimeImmutable;

/**
 * Source de l'heure courante, injectable pour rendre le code testable.
 */
interface Clock
{
    /**
     * Retourne l'instant présent.
     *
     * @return DateTimeImmutable
     */
    public function now(): DateTimeImmutable;
}
