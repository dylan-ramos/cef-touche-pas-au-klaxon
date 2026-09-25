<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Data\AbstractRepository;
use App\Core\Data\Exception\CheckConstraintViolationException;
use App\Core\Data\Exception\ForeignKeyConstraintViolationException;
use App\Core\Data\Row;
use App\Entity\Agency;
use App\Entity\Trip;
use App\Entity\TripData;
use DateTimeImmutable;

/**
 * Accès aux trajets.
 */
final class TripRepository extends AbstractRepository
{
    private const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Sélection d'un trajet avec ses agences et son auteur.
     */
    private const string SELECT = <<<'SQL'
        SELECT t.id, t.date_heure_depart, t.date_heure_arrivee, t.places_totales, t.places_disponibles,
               ad.id AS depart_id, ad.nom AS depart_nom,
               aa.id AS arrivee_id, aa.nom AS arrivee_nom,
               u.id AS auteur_id, u.nom AS auteur_nom, u.prenom AS auteur_prenom,
               u.telephone AS auteur_telephone, u.email AS auteur_email, u.role AS auteur_role
        FROM trajet t
                 INNER JOIN agence ad ON ad.id = t.agence_depart_id
                 INNER JOIN agence aa ON aa.id = t.agence_arrivee_id
                 INNER JOIN utilisateur u ON u.id = t.auteur_id
        SQL;

    /**
     * Trajets à venir disposant encore de places, par départ croissant.
     *
     * @param DateTimeImmutable $now Instant de référence : seuls les départs postérieurs sont retenus.
     *
     * @return list<Trip>
     */
    public function findUpcomingAvailable(DateTimeImmutable $now): array
    {
        return array_map(self::hydrate(...), $this->fetchAll(
            self::SELECT . ' WHERE t.date_heure_depart > :now AND t.places_disponibles > 0'
            . ' ORDER BY t.date_heure_depart, t.id',
            ['now' => $now->format(self::DATETIME_FORMAT)],
        ));
    }

    /**
     * Tous les trajets, du départ le plus récent au plus ancien.
     *
     * @return list<Trip>
     */
    public function findAll(): array
    {
        return array_map(
            self::hydrate(...),
            $this->fetchAll(self::SELECT . ' ORDER BY t.date_heure_depart DESC, t.id DESC'),
        );
    }

    /**
     * Recherche un trajet par identifiant.
     *
     * @param int $id Identifiant.
     *
     * @return Trip|null
     */
    public function findById(int $id): ?Trip
    {
        $row = $this->fetchOne(self::SELECT . ' WHERE t.id = :id', ['id' => $id]);

        return $row === null ? null : self::hydrate($row);
    }

    /**
     * Nombre de trajets dont le départ est postérieur à l'instant donné.
     *
     * @param DateTimeImmutable $now Instant de référence.
     *
     * @return int
     */
    public function countUpcoming(DateTimeImmutable $now): int
    {
        return $this->fetchInt(
            'SELECT COUNT(*) FROM trajet WHERE date_heure_depart > :now',
            ['now' => $now->format(self::DATETIME_FORMAT)],
        );
    }

    /**
     * Enregistre un nouveau trajet.
     *
     * @param TripData $data     Données validées du trajet.
     * @param int      $authorId Employé proposant le trajet.
     *
     * @return int Identifiant du trajet créé.
     *
     * @throws ForeignKeyConstraintViolationException Si une agence ou l'auteur n'existe pas.
     * @throws CheckConstraintViolationException      Si une règle de cohérence est violée.
     */
    public function create(TripData $data, int $authorId): int
    {
        $this->execute(
            'INSERT INTO trajet (agence_depart_id, agence_arrivee_id, date_heure_depart, date_heure_arrivee,'
            . ' places_totales, places_disponibles, auteur_id)'
            . ' VALUES (:depart, :arrivee, :date_depart, :date_arrivee, :places_totales, :places_disponibles, :auteur)',
            [...self::parameters($data), 'auteur' => $authorId],
        );

        return $this->lastInsertId();
    }

    /**
     * Met à jour un trajet (l'auteur reste inchangé).
     *
     * @param int      $id   Identifiant du trajet.
     * @param TripData $data Données validées du trajet.
     *
     * @return bool False si le trajet n'existe pas.
     *
     * @throws ForeignKeyConstraintViolationException Si une agence n'existe pas.
     * @throws CheckConstraintViolationException      Si une règle de cohérence est violée.
     */
    public function update(int $id, TripData $data): bool
    {
        return $this->execute(
            'UPDATE trajet SET agence_depart_id = :depart, agence_arrivee_id = :arrivee,'
            . ' date_heure_depart = :date_depart, date_heure_arrivee = :date_arrivee,'
            . ' places_totales = :places_totales, places_disponibles = :places_disponibles'
            . ' WHERE id = :id',
            [...self::parameters($data), 'id' => $id],
        ) === 1;
    }

    /**
     * Supprime un trajet.
     *
     * @param int $id Identifiant.
     *
     * @return bool False si le trajet n'existe pas.
     */
    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM trajet WHERE id = :id', ['id' => $id]) === 1;
    }

    /**
     * Paramètres SQL communs à la création et à la modification.
     *
     * @param TripData $data Données du trajet.
     *
     * @return array<string, int|string>
     */
    private static function parameters(TripData $data): array
    {
        return [
            'depart' => $data->departureAgencyId,
            'arrivee' => $data->arrivalAgencyId,
            'date_depart' => $data->departureAt->format(self::DATETIME_FORMAT),
            'date_arrivee' => $data->arrivalAt->format(self::DATETIME_FORMAT),
            'places_totales' => $data->totalSeats,
            'places_disponibles' => $data->availableSeats,
        ];
    }

    /**
     * Construit un trajet à partir d'une ligne de la sélection commune.
     *
     * @param Row $row Ligne de résultat.
     *
     * @return Trip
     */
    private static function hydrate(Row $row): Trip
    {
        return new Trip(
            $row->int('id'),
            new Agency($row->int('depart_id'), $row->string('depart_nom')),
            new Agency($row->int('arrivee_id'), $row->string('arrivee_nom')),
            $row->dateTime('date_heure_depart'),
            $row->dateTime('date_heure_arrivee'),
            $row->int('places_totales'),
            $row->int('places_disponibles'),
            UserRepository::hydrate($row, 'auteur_'),
        );
    }
}
