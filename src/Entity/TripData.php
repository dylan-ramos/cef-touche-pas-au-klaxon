<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;

/**
 * Données d'un trajet à enregistrer (création ou modification).
 *
 * Les valeurs sont supposées déjà validées : ce sont celles produites par le
 * validateur de trajet à partir de la saisie utilisateur.
 */
final class TripData
{
    /**
     * @param int               $departureAgencyId Agence de départ.
     * @param int               $arrivalAgencyId   Agence d'arrivée.
     * @param DateTimeImmutable $departureAt       Date et heure de départ.
     * @param DateTimeImmutable $arrivalAt         Date et heure d'arrivée.
     * @param int               $totalSeats        Nombre total de places.
     * @param int               $availableSeats    Nombre de places disponibles.
     */
    public function __construct(
        public readonly int $departureAgencyId,
        public readonly int $arrivalAgencyId,
        public readonly DateTimeImmutable $departureAt,
        public readonly DateTimeImmutable $arrivalAt,
        public readonly int $totalSeats,
        public readonly int $availableSeats,
    ) {
    }
}
