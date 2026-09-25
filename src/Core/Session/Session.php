<?php

declare(strict_types=1);

namespace App\Core\Session;

/**
 * Stockage de données propres au visiteur entre deux requêtes.
 */
interface Session
{
    /**
     * Lit une valeur.
     *
     * @param string $key     Clé.
     * @param mixed  $default Valeur retournée si la clé est absente.
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Enregistre une valeur.
     *
     * @param string $key   Clé.
     * @param mixed  $value Valeur sérialisable.
     *
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Indique si une clé est présente.
     *
     * @param string $key Clé.
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Supprime une valeur.
     *
     * @param string $key Clé.
     *
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Change l'identifiant de session en conservant les données
     * (à appeler à chaque changement de niveau de privilège).
     *
     * @return void
     */
    public function regenerate(): void;

    /**
     * Supprime toutes les données et invalide la session.
     *
     * @return void
     */
    public function destroy(): void;
}
