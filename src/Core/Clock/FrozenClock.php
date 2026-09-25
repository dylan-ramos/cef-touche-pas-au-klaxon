<?php

declare(strict_types=1);

namespace App\Core\Clock;

use DateTimeImmutable;

/**
 * Horloge figée sur un instant donné (tests, traitements rejoués).
 */
final class FrozenClock implements Clock
{
    /**
     * @param DateTimeImmutable $now Instant retourné à chaque appel.
     */
    public function __construct(private readonly DateTimeImmutable $now)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
