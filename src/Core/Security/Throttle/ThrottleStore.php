<?php

declare(strict_types=1);

namespace App\Core\Security\Throttle;

/**
 * Stockage des compteurs de tentatives.
 */
interface ThrottleStore
{
    /**
     * Lit un compteur.
     *
     * @param string $key Clé du compteur.
     *
     * @return array{attempts: int, resetAt: int}|null Null si aucun compteur n'existe.
     */
    public function get(string $key): ?array;

    /**
     * Enregistre un compteur.
     *
     * @param string                              $key    Clé du compteur.
     * @param array{attempts: int, resetAt: int}  $record Nombre de tentatives et horodatage de remise à zéro.
     *
     * @return void
     */
    public function put(string $key, array $record): void;

    /**
     * Supprime un compteur.
     *
     * @param string $key Clé du compteur.
     *
     * @return void
     */
    public function forget(string $key): void;
}
