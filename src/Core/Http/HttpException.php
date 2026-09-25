<?php

declare(strict_types=1);

namespace App\Core\Http;

use RuntimeException;
use Throwable;

/**
 * Erreur destinée à produire une réponse HTTP avec un code de statut précis.
 *
 * Le message est affiché à l'utilisateur pour les codes 4xx : il ne doit
 * jamais contenir de détail technique.
 */
class HttpException extends RuntimeException
{
    /**
     * @param int            $statusCode Code de statut HTTP (4xx ou 5xx).
     * @param string         $message    Message présentable à l'utilisateur.
     * @param Throwable|null $previous   Exception d'origine éventuelle.
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Retourne le code de statut HTTP associé.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
