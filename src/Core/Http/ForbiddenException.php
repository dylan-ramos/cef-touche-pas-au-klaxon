<?php

declare(strict_types=1);

namespace App\Core\Http;

use Throwable;

/**
 * Erreur HTTP 403.
 */
final class ForbiddenException extends HttpException
{
    /**
     * @param string         $message  Message présentable à l'utilisateur.
     * @param Throwable|null $previous Exception d'origine éventuelle.
     */
    public function __construct(
        string $message = "Vous n'avez pas les droits nécessaires pour accéder à cette page.",
        ?Throwable $previous = null,
    ) {
        parent::__construct(403, $message, $previous);
    }
}
