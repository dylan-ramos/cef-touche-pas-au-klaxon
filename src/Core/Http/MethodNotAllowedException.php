<?php

declare(strict_types=1);

namespace App\Core\Http;

use Throwable;

/**
 * Erreur HTTP 405 : l'adresse existe mais pas pour cette méthode.
 */
final class MethodNotAllowedException extends HttpException
{
    /**
     * @param list<string>   $allowedMethods Méthodes acceptées par l'adresse (en-tête Allow).
     * @param Throwable|null $previous       Exception d'origine éventuelle.
     */
    public function __construct(
        private readonly array $allowedMethods,
        ?Throwable $previous = null,
    ) {
        parent::__construct(405, 'Cette action n\'est pas disponible à cette adresse.', $previous);
    }

    /**
     * Méthodes acceptées par l'adresse demandée.
     *
     * @return list<string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
