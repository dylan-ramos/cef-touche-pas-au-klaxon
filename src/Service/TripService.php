<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Clock\Clock;
use App\Core\Data\Exception\ConstraintViolationException;
use App\Core\Http\ForbiddenException;
use App\Core\Http\NotFoundException;
use App\Core\Validation\ValidationException;
use App\Entity\Trip;
use App\Entity\User;
use App\Repository\TripRepository;
use App\Validator\TripValidator;

/**
 * Règles de gestion des trajets : droits, validation et enregistrement.
 *
 * - tout employé connecté peut proposer un trajet, dont il devient l'auteur ;
 * - seul l'auteur modifie un trajet, tant que celui-ci n'est pas parti ;
 * - l'auteur ou un administrateur peut supprimer un trajet.
 */
final class TripService
{
    private const string INCONSISTENT_DATA
        = 'Le trajet n\'a pas pu être enregistré : les données ne sont plus cohérentes. Veuillez vérifier la saisie.';

    /**
     * @param TripRepository $trips     Dépôt des trajets.
     * @param TripValidator  $validator Validation de la saisie.
     * @param Clock          $clock     Heure de référence.
     */
    public function __construct(
        private readonly TripRepository $trips,
        private readonly TripValidator $validator,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Crée un trajet proposé par l'utilisateur.
     *
     * @param User                    $author Employé connecté.
     * @param array<array-key, mixed> $input  Saisie du formulaire.
     *
     * @return int Identifiant du trajet créé.
     *
     * @throws ValidationException Si la saisie est invalide ou incohérente.
     */
    public function create(User $author, array $input): int
    {
        $data = $this->validator->validate($input);

        return $this->persist(fn (): int => $this->trips->create($data, $author->id), $input);
    }

    /**
     * Retourne un trajet que l'utilisateur est autorisé à modifier.
     *
     * @param User $actor Utilisateur connecté.
     * @param int  $id    Identifiant du trajet.
     *
     * @return Trip
     *
     * @throws NotFoundException  Si le trajet n'existe pas.
     * @throws ForbiddenException Si l'utilisateur n'en est pas l'auteur ou si le trajet est parti.
     */
    public function findEditable(User $actor, int $id): Trip
    {
        $trip = $this->find($id);

        if (!$trip->isAuthoredBy($actor)) {
            throw new ForbiddenException('Seul l\'auteur d\'un trajet peut le modifier.');
        }

        if ($trip->hasDeparted($this->clock->now())) {
            throw new ForbiddenException('Un trajet déjà parti ne peut plus être modifié.');
        }

        return $trip;
    }

    /**
     * Modifie un trajet de l'utilisateur.
     *
     * @param User                    $actor Utilisateur connecté.
     * @param int                     $id    Identifiant du trajet.
     * @param array<array-key, mixed> $input Saisie du formulaire.
     *
     * @return void
     *
     * @throws NotFoundException   Si le trajet n'existe pas.
     * @throws ForbiddenException  Si l'utilisateur n'est pas autorisé.
     * @throws ValidationException Si la saisie est invalide ou incohérente.
     */
    public function update(User $actor, int $id, array $input): void
    {
        $this->findEditable($actor, $id);
        $data = $this->validator->validate($input);

        $this->persist(fn (): bool => $this->trips->update($id, $data), $input);
    }

    /**
     * Supprime un trajet.
     *
     * @param User $actor Utilisateur connecté (auteur ou administrateur).
     * @param int  $id    Identifiant du trajet.
     *
     * @return void
     *
     * @throws NotFoundException  Si le trajet n'existe pas.
     * @throws ForbiddenException Si l'utilisateur n'est ni l'auteur ni administrateur.
     */
    public function delete(User $actor, int $id): void
    {
        $trip = $this->find($id);

        if (!$trip->isAuthoredBy($actor) && !$actor->isAdmin()) {
            throw new ForbiddenException('Seul l\'auteur d\'un trajet ou un administrateur peut le supprimer.');
        }

        $this->trips->delete($id);
    }

    /**
     * Valeurs d'un trajet existant, au format du formulaire.
     *
     * @param Trip $trip Trajet.
     *
     * @return array<string, string>
     */
    public static function formValues(Trip $trip): array
    {
        return [
            'departure_agency_id' => (string) $trip->departureAgency->id,
            'arrival_agency_id' => (string) $trip->arrivalAgency->id,
            'departure_at' => $trip->departureAt->format(TripValidator::DATETIME_FORMAT),
            'arrival_at' => $trip->arrivalAt->format(TripValidator::DATETIME_FORMAT),
            'total_seats' => (string) $trip->totalSeats,
            'available_seats' => (string) $trip->availableSeats,
        ];
    }

    /**
     * Recherche un trajet existant.
     *
     * @param int $id Identifiant.
     *
     * @return Trip
     *
     * @throws NotFoundException Si le trajet n'existe pas.
     */
    private function find(int $id): Trip
    {
        return $this->trips->findById($id) ?? throw new NotFoundException('Ce trajet n\'existe pas ou a été supprimé.');
    }

    /**
     * Exécute une écriture en transformant une violation de contrainte
     * (modification concurrente d'une agence, par exemple) en erreur de saisie.
     *
     * @template T
     *
     * @param callable(): T           $write Écriture à exécuter.
     * @param array<array-key, mixed> $input Saisie, réaffichée en cas d'échec.
     *
     * @return T
     *
     * @throws ValidationException Si la base refuse l'enregistrement.
     */
    private function persist(callable $write, array $input): mixed
    {
        try {
            return $write();
        } catch (ConstraintViolationException) {
            $old = [];
            foreach (TripValidator::FIELDS as $field) {
                $value = $input[$field] ?? '';
                $old[$field] = is_string($value) ? mb_substr(trim($value), 0, 50) : '';
            }

            throw new ValidationException(['form' => self::INCONSISTENT_DATA], $old);
        }
    }
}
