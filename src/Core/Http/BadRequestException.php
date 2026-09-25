<?php

declare(strict_types=1);

namespace App\Core\Http;

use Throwable;

/**
 * Erreur HTTP 400.
 */
final class BadRequestException extends HttpException
{
    /**
     * @param string         $message  Message présentable à l'utilisateur.
     * @param Throwable|null $previous Exception d'origine éventuelle.
     */
    public function __construct(string $message = "La requête est invalide.", ?Throwable $previous = null)
    {
        parent::__construct(400, $message, $previous);
    }
}
