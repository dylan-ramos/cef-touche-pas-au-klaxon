<?php

declare(strict_types=1);

namespace App\Core\Http;

use Throwable;

/**
 * Erreur HTTP 419 : jeton CSRF absent, expiré ou falsifié.
 */
final class InvalidCsrfTokenException extends HttpException
{
    /**
     * @param string         $message  Message présentable à l'utilisateur.
     * @param Throwable|null $previous Exception d'origine éventuelle.
     */
    public function __construct(
        string $message = 'Le formulaire a expiré. Veuillez recharger la page puis recommencer.',
        ?Throwable $previous = null,
    ) {
        parent::__construct(419, $message, $previous);
    }
}
