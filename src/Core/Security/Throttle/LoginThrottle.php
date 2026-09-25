<?php

declare(strict_types=1);

namespace App\Core\Security\Throttle;

use App\Core\Clock\Clock;

/**
 * Limitation des tentatives de connexion.
 *
 * Après un nombre maximal d'échecs pour un même couple identifiant / adresse
 * IP, les tentatives sont bloquées jusqu'à la fin de la fenêtre de temps
 * ouverte au premier échec. Une connexion réussie remet le compteur à zéro.
 */
final class LoginThrottle
{
    /**
     * @param ThrottleStore $store       Stockage des compteurs.
     * @param Clock         $clock       Heure de référence.
     * @param int           $maxAttempts Nombre d'échecs tolérés.
     * @param int           $windowSeconds Durée de la fenêtre de comptage et du blocage.
     */
    public function __construct(
        private readonly ThrottleStore $store,
        private readonly Clock $clock,
        private readonly int $maxAttempts = 5,
        private readonly int $windowSeconds = 900,
    ) {
    }

    /**
     * Clé de comptage d'un couple identifiant / adresse IP.
     *
     * @param string $login     Identifiant saisi.
     * @param string $ipAddress Adresse IP du client.
     *
     * @return string
     */
    public static function key(string $login, string $ipAddress): string
    {
        return 'login|' . mb_strtolower(trim($login)) . '|' . $ipAddress;
    }

    /**
     * Nombre de secondes avant qu'une nouvelle tentative soit acceptée.
     *
     * @param string $key Clé de comptage.
     *
     * @return int 0 si la tentative est autorisée.
     */
    public function secondsUntilAvailable(string $key): int
    {
        $record = $this->activeRecord($key);
        if ($record === null || $record['attempts'] < $this->maxAttempts) {
            return 0;
        }

        return max(1, $record['resetAt'] - $this->clock->now()->getTimestamp());
    }

    /**
     * Enregistre un échec.
     *
     * @param string $key Clé de comptage.
     *
     * @return void
     */
    public function hit(string $key): void
    {
        $record = $this->activeRecord($key)
            ?? ['attempts' => 0, 'resetAt' => $this->clock->now()->getTimestamp() + $this->windowSeconds];
        $record['attempts']++;

        $this->store->put($key, $record);
    }

    /**
     * Remet le compteur à zéro (connexion réussie).
     *
     * @param string $key Clé de comptage.
     *
     * @return void
     */
    public function clear(string $key): void
    {
        $this->store->forget($key);
    }

    /**
     * Compteur en cours, ou null si absent ou expiré.
     *
     * @param string $key Clé de comptage.
     *
     * @return array{attempts: int, resetAt: int}|null
     */
    private function activeRecord(string $key): ?array
    {
        $record = $this->store->get($key);
        if ($record === null || $record['resetAt'] <= $this->clock->now()->getTimestamp()) {
            return null;
        }

        return $record;
    }
}
