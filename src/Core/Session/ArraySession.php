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

    private int $regenerations = 0;

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function regenerate(): void
    {
        $this->regenerations++;
    }

    /**
     * {@inheritDoc}
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
