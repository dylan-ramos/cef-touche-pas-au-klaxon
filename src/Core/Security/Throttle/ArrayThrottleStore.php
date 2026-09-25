<?php

declare(strict_types=1);

namespace App\Core\Security\Throttle;

/**
 * Compteurs en mémoire (tests).
 */
final class ArrayThrottleStore implements ThrottleStore
{
    /**
     * @var array<string, array{attempts: int, resetAt: int}>
     */
    private array $records = [];

    /**
     * Lit un compteur.
     *
     * @param string $key Clé du compteur.
     *
     * @return array{attempts: int, resetAt: int}|null Null si aucun compteur n'existe.
     */
    public function get(string $key): ?array
    {
        return $this->records[$key] ?? null;
    }

    /**
     * Enregistre un compteur.
     *
     * @param string                             $key    Clé du compteur.
     * @param array{attempts: int, resetAt: int} $record Nombre de tentatives et horodatage de remise à zéro.
     *
     * @return void
     */
    public function put(string $key, array $record): void
    {
        $this->records[$key] = $record;
    }

    /**
     * Supprime un compteur.
     *
     * @param string $key Clé du compteur.
     *
     * @return void
     */
    public function forget(string $key): void
    {
        unset($this->records[$key]);
    }
}
