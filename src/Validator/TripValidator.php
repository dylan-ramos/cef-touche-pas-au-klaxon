<?php

declare(strict_types=1);

namespace App\Validator;

use App\Core\Clock\Clock;
use App\Core\Validation\Input;
use App\Core\Validation\ValidationException;
use App\Entity\TripData;
use App\Repository\AgencyRepository;
use DateTimeImmutable;

/**
 * Validation et contrôles de cohérence d'un trajet saisi.
 *
 * Règles appliquées :
 * - agences de départ et d'arrivée existantes et différentes ;
 * - départ dans le futur, arrivée strictement postérieure au départ ;
 * - places totales entre 1 et 9, places disponibles entre 0 et le total.
 */
final class TripValidator
{
    public const int MAX_SEATS = 9;

    /**
     * Format produit par les champs `datetime-local`.
     */
    public const string DATETIME_FORMAT = 'Y-m-d\TH:i';

    /**
     * Champs du formulaire, dans l'ordre d'affichage.
     */
    public const array FIELDS = [
        'departure_agency_id',
        'arrival_agency_id',
        'departure_at',
        'arrival_at',
        'total_seats',
        'available_seats',
    ];

    /**
     * @param AgencyRepository $agencies Référentiel des agences.
     * @param Clock            $clock    Heure de référence pour le contrôle « départ futur ».
     */
    public function __construct(
        private readonly AgencyRepository $agencies,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Valide la saisie et retourne les données à enregistrer.
     *
     * @param array<array-key, mixed> $data Corps de la requête.
     *
     * @return TripData
     *
     * @throws ValidationException Si une règle n'est pas respectée.
     */
    public function validate(array $data): TripData
    {
        $input = new Input($data);
        $errors = [];

        $departureAgencyId = $this->agencyId($input, 'departure_agency_id', 'de départ', $errors);
        $arrivalAgencyId = $this->agencyId($input, 'arrival_agency_id', 'd\'arrivée', $errors);
        if ($departureAgencyId !== null && $departureAgencyId === $arrivalAgencyId) {
            $errors['arrival_agency_id'] = 'L\'agence d\'arrivée doit être différente de l\'agence de départ.';
        }

        $departureAt = $this->dateTime($input, 'departure_at', 'de départ', $errors);
        $arrivalAt = $this->dateTime($input, 'arrival_at', 'd\'arrivée', $errors);
        if ($departureAt !== null && $departureAt <= $this->clock->now()) {
            $errors['departure_at'] = 'La date de départ doit être dans le futur.';
        }
        if ($departureAt !== null && $arrivalAt !== null && $arrivalAt <= $departureAt) {
            $errors['arrival_at'] = 'L\'arrivée doit être postérieure au départ.';
        }

        $totalSeats = $input->intBetween('total_seats', 1, self::MAX_SEATS);
        if ($totalSeats === null) {
            $errors['total_seats'] = sprintf('Le nombre total de places doit être un entier entre 1 et %d.', self::MAX_SEATS);
        }

        $availableSeats = $input->intBetween('available_seats', 0, $totalSeats ?? self::MAX_SEATS);
        if ($availableSeats === null) {
            $errors['available_seats'] = 'Les places disponibles doivent être comprises entre 0 et le nombre total de places.';
        }

        if (
            $errors !== [] || $departureAgencyId === null || $arrivalAgencyId === null || $departureAt === null
            || $arrivalAt === null || $totalSeats === null || $availableSeats === null
        ) {
            throw new ValidationException($errors, self::old($input));
        }

        return new TripData($departureAgencyId, $arrivalAgencyId, $departureAt, $arrivalAt, $totalSeats, $availableSeats);
    }

    /**
     * Lit une agence et vérifie son existence.
     *
     * @param Input                 $input  Saisie.
     * @param string                $field  Nom du champ.
     * @param string                $label  Complément du libellé (« de départ »…).
     * @param array<string, string> $errors Erreurs, complétées en cas d'échec.
     *
     * @return int|null
     */
    private function agencyId(Input $input, string $field, string $label, array &$errors): ?int
    {
        $id = $input->positiveInt($field, 4294967295);
        if ($id === null) {
            $errors[$field] = sprintf('Veuillez choisir l\'agence %s.', $label);

            return null;
        }

        if ($this->agencies->findById($id) === null) {
            $errors[$field] = sprintf('L\'agence %s n\'existe pas.', $label);

            return null;
        }

        return $id;
    }

    /**
     * Lit une date-heure au format `datetime-local`.
     *
     * @param Input                 $input  Saisie.
     * @param string                $field  Nom du champ.
     * @param string                $label  Complément du libellé.
     * @param array<string, string> $errors Erreurs, complétées en cas d'échec.
     *
     * @return DateTimeImmutable|null
     */
    private function dateTime(Input $input, string $field, string $label, array &$errors): ?DateTimeImmutable
    {
        $value = $input->string($field);
        $date = DateTimeImmutable::createFromFormat('!' . self::DATETIME_FORMAT, $value);

        if ($date === false || $date->format(self::DATETIME_FORMAT) !== $value) {
            $errors[$field] = sprintf('Veuillez indiquer une date et une heure %s valides.', $label);

            return null;
        }

        return $date;
    }

    /**
     * Valeurs saisies à réafficher dans le formulaire.
     *
     * @param Input $input Saisie.
     *
     * @return array<string, string>
     */
    private static function old(Input $input): array
    {
        $old = [];
        foreach (self::FIELDS as $field) {
            $old[$field] = mb_substr($input->string($field), 0, 50);
        }

        return $old;
    }
}
