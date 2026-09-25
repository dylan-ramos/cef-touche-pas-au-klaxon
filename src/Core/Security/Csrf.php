<?php

declare(strict_types=1);

namespace App\Core\Security;

use App\Core\Http\InvalidCsrfTokenException;
use App\Core\Session\Session;

/**
 * Protection contre la falsification de requêtes intersites (CSRF).
 *
 * Un jeton aléatoire est associé à la session ; tout formulaire l'envoie dans
 * le champ caché `_csrf` et toute requête d'écriture est refusée si le jeton
 * reçu ne correspond pas.
 */
final class Csrf
{
    public const string FIELD = '_csrf';

    private const string SESSION_KEY = '_csrf_token';

    /**
     * @param Session $session Session du visiteur.
     */
    public function __construct(private readonly Session $session)
    {
    }

    /**
     * Retourne le jeton de la session, en le créant si nécessaire.
     *
     * @return string Jeton hexadécimal de 64 caractères.
     */
    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (is_string($token) && $token !== '') {
            return $token;
        }

        return $this->regenerate();
    }

    /**
     * Remplace le jeton (après connexion, par exemple).
     *
     * @return string Nouveau jeton.
     */
    public function regenerate(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set(self::SESSION_KEY, $token);

        return $token;
    }

    /**
     * Indique si le jeton reçu correspond à celui de la session.
     *
     * @param mixed $token Valeur reçue du formulaire.
     *
     * @return bool
     */
    public function isValid(mixed $token): bool
    {
        $expected = $this->session->get(self::SESSION_KEY);

        return is_string($token) && is_string($expected) && $expected !== '' && hash_equals($expected, $token);
    }

    /**
     * Refuse la requête si le jeton reçu est invalide.
     *
     * @param mixed $token Valeur reçue du formulaire.
     *
     * @return void
     *
     * @throws InvalidCsrfTokenException Si le jeton est absent ou invalide.
     */
    public function assertValid(mixed $token): void
    {
        if (!$this->isValid($token)) {
            throw new InvalidCsrfTokenException();
        }
    }
}
