<?php

declare(strict_types=1);

namespace App\Core\Session;

use RuntimeException;

/**
 * Session PHP native, démarrée à la première utilisation.
 *
 * Le cookie de session est restreint : inaccessible à JavaScript, non
 * transmis par les requêtes intersites, limité à HTTPS lorsque la requête
 * est sécurisée.
 */
final class NativeSession implements Session
{
    /**
     * @param string $name   Nom du cookie de session.
     * @param bool   $secure Restreint le cookie aux connexions HTTPS.
     */
    public function __construct(
        private readonly string $name = 'klaxon_session',
        private readonly bool $secure = false,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $this->start();

        return array_key_exists($key, $_SESSION);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];

        $params = session_get_cookie_params();
        setcookie($this->name, '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_destroy();
    }

    /**
     * Démarre la session avec des paramètres de cookie sécurisés.
     *
     * @return void
     *
     * @throws RuntimeException Si la session ne peut pas être démarrée.
     */
    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start(['use_strict_mode' => true, 'use_only_cookies' => true])) {
            throw new RuntimeException('Impossible de démarrer la session.');
        }
    }
}
