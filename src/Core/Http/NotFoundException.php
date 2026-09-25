<?php

declare(strict_types=1);

namespace App\Core\Http;

use Throwable;

/**
 * Erreur HTTP 404.
 */
final class NotFoundException extends HttpException
{
    /**
     * @param string         $message  Message présentable à l'utilisateur.
     * @param Throwable|null $previous Exception d'origine éventuelle.
     */
    public function __construct(string $message = "La page demandée n'existe pas.", ?Throwable $previous = null)
    {
        parent::__construct(404, $message, $previous);
    }
}
