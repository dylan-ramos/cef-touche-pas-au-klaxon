<?php

declare(strict_types=1);

namespace App\Service\Exception;

use DomainException;

/**
 * Suppression refusée : l'agence est le départ ou l'arrivée d'au moins un trajet.
 */
final class AgencyInUseException extends DomainException
{
    /**
     * @param string $agencyName Nom de l'agence concernée.
     */
    public function __construct(string $agencyName)
    {
        parent::__construct(sprintf(
            'L\'agence « %s » ne peut pas être supprimée : des trajets partent de cette agence ou y arrivent.',
            $agencyName,
        ));
    }
}
