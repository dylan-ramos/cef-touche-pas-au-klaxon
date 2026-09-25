<?php

declare(strict_types=1);

namespace App\Core\Session;

/**
 * Session en mémoire, limitée à la requête courante (tests, traitements en ligne de commande).
 */
final class ArraySession implements Session
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @var int Nombre de régénérations d'identifiant demandées.
     */
    private int $regenerations = 0;

    /**
     * Lit une valeur de session.
     *
     * @param string $key     Clé.
     * @param mixed  $default Valeur retournée si la clé est absente.
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Enregistre une valeur en session.
     *
     * @param string $key   Clé.
     * @param mixed  $value Valeur sérialisable.
     *
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Indique si une clé est présente en session.
     *
     * @param string $key Clé.
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Supprime une valeur de session.
     *
     * @param string $key Clé.
     *
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Change l'identifiant de session en conservant les données.
     *
     * @return void
     */
    public function regenerate(): void
    {
        $this->regenerations++;
    }

    /**
     * Supprime toutes les données et invalide la session.
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->data = [];
    }

    /**
     * Nombre de régénérations d'identifiant demandées (contrôle en test).
     *
     * @return int
     */
    public function regenerations(): int
    {
        return $this->regenerations;
    }
}
