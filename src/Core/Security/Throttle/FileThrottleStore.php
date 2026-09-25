<?php

declare(strict_types=1);

namespace App\Core\Security\Throttle;

use RuntimeException;

/**
 * Compteurs stockés dans des fichiers JSON, un par clé.
 *
 * Les clés sont des empreintes : aucun identifiant ni adresse IP n'est écrit
 * en clair sur le disque.
 */
final class FileThrottleStore implements ThrottleStore
{
    /**
     * @param string $directory Répertoire de stockage, créé si nécessaire.
     */
    public function __construct(private readonly string $directory)
    {
    }

    /**
     * Lit un compteur.
     *
     * @param string $key Clé du compteur.
     *
     * @return array{attempts: int, resetAt: int}|null Null si aucun compteur n'existe.
     */
    public function get(string $key): ?array
    {
        $content = @file_get_contents($this->path($key));
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !is_int($data['attempts'] ?? null) || !is_int($data['resetAt'] ?? null)) {
            return null;
        }

        return ['attempts' => $data['attempts'], 'resetAt' => $data['resetAt']];
    }

    /**
     * Enregistre un compteur.
     *
     * @param string                             $key    Clé du compteur.
     * @param array{attempts: int, resetAt: int} $record Nombre de tentatives et horodatage de remise à zéro.
     *
     * @return void
     *
     * @throws RuntimeException Si le répertoire n'est pas accessible en écriture.
     */
    public function put(string $key, array $record): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0o770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Répertoire de limitation des tentatives inaccessible.');
        }

        if (file_put_contents($this->path($key), json_encode($record, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
            throw new RuntimeException('Impossible d\'enregistrer le compteur de tentatives.');
        }
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
        $path = $this->path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Chemin du fichier d'une clé (empreinte SHA-256 de la clé).
     *
     * @param string $key Clé du compteur.
     *
     * @return string
     */
    private function path(string $key): string
    {
        return $this->directory . '/' . hash('sha256', $key) . '.json';
    }
}
