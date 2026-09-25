<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;

/**
 * Trajet proposé par un employé entre deux agences.
 *
 * L'auteur du trajet est la personne à contacter.
 */
final class Trip
{
    /**
     * @param int               $id              Identifiant.
     * @param Agency            $departureAgency Agence de départ.
     * @param Agency            $arrivalAgency   Agence d'arrivée.
     * @param DateTimeImmutable $departureAt     Date et heure de départ.
     * @param DateTimeImmutable $arrivalAt       Date et heure d'arrivée.
     * @param int               $totalSeats      Nombre total de places.
     * @param int               $availableSeats  Nombre de places disponibles.
     * @param User              $author          Employé ayant proposé le trajet.
     */
    public function __construct(
        public readonly int $id,
        public readonly Agency $departureAgency,
        public readonly Agency $arrivalAgency,
        public readonly DateTimeImmutable $departureAt,
        public readonly DateTimeImmutable $arrivalAt,
        public readonly int $totalSeats,
        public readonly int $availableSeats,
        public readonly User $author,
    ) {
    }

    /**
     * Indique si l'utilisateur donné est l'auteur du trajet.
     *
     * @param User|null $user Utilisateur courant, null pour un visiteur.
     *
     * @return bool
     */
    public function isAuthoredBy(?User $user): bool
    {
        return $user !== null && $user->id === $this->author->id;
    }

    /**
     * Indique s'il ne reste aucune place.
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->availableSeats === 0;
    }

    /**
     * Indique si le départ a eu lieu à l'instant donné.
     *
     * @param DateTimeImmutable $now Instant de référence.
     *
     * @return bool
     */
    public function hasDeparted(DateTimeImmutable $now): bool
    {
        return $this->departureAt <= $now;
    }
}
