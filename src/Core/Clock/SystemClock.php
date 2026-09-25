<?php

declare(strict_types=1);

namespace App\Core\Clock;

use DateTimeImmutable;

/**
 * Horloge système, dans le fuseau horaire par défaut de l'application.
 */
final class SystemClock implements Clock
{
    /**
     * {@inheritDoc}
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
