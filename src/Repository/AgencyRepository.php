<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Data\AbstractRepository;
use App\Core\Data\Exception\ForeignKeyConstraintViolationException;
use App\Core\Data\Exception\UniqueConstraintViolationException;
use App\Core\Data\Row;
use App\Entity\Agency;

/**
 * Accès aux agences.
 */
final class AgencyRepository extends AbstractRepository
{
    /**
     * Liste toutes les agences par ordre alphabétique.
     *
     * @return list<Agency>
     */
    public function findAll(): array
    {
        return array_map(
            self::hydrate(...),
            $this->fetchAll('SELECT id, nom FROM agence ORDER BY nom'),
        );
    }

    /**
     * Recherche une agence par identifiant.
     *
     * @param int $id Identifiant.
     *
     * @return Agency|null
     */
    public function findById(int $id): ?Agency
    {
        $row = $this->fetchOne('SELECT id, nom FROM agence WHERE id = :id', ['id' => $id]);

        return $row === null ? null : self::hydrate($row);
    }

    /**
     * Indique si une agence porte déjà ce nom (comparaison insensible à la casse et aux accents).
     *
     * @param string   $name      Nom recherché.
     * @param int|null $excludeId Agence à ignorer (cas d'une modification).
     *
     * @return bool
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        return $this->fetchInt(
            'SELECT COUNT(*) FROM agence WHERE nom = :nom AND id <> :exclude',
            ['nom' => $name, 'exclude' => $excludeId ?? 0],
        ) > 0;
    }

    /**
     * Indique si des trajets partent de cette agence ou y arrivent.
     *
     * @param int $id Identifiant de l'agence.
     *
     * @return bool
     */
    public function isUsedByTrips(int $id): bool
    {
        return $this->fetchInt(
            'SELECT COUNT(*) FROM trajet WHERE agence_depart_id = :depart OR agence_arrivee_id = :arrivee',
            ['depart' => $id, 'arrivee' => $id],
        ) > 0;
    }

    /**
     * Nombre total d'agences.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->fetchInt('SELECT COUNT(*) FROM agence');
    }

    /**
     * Crée une agence.
     *
     * @param string $name Nom de la ville.
     *
     * @return int Identifiant de l'agence créée.
     *
     * @throws UniqueConstraintViolationException Si le nom est déjà utilisé.
     */
    public function create(string $name): int
    {
        $this->execute('INSERT INTO agence (nom) VALUES (:nom)', ['nom' => $name]);

        return $this->lastInsertId();
    }

    /**
     * Renomme une agence.
     *
     * @param int    $id   Identifiant.
     * @param string $name Nouveau nom.
     *
     * @return bool False si l'agence n'existe pas.
     *
     * @throws UniqueConstraintViolationException Si le nom est déjà utilisé.
     */
    public function update(int $id, string $name): bool
    {
        return $this->execute('UPDATE agence SET nom = :nom WHERE id = :id', ['nom' => $name, 'id' => $id]) === 1;
    }

    /**
     * Supprime une agence.
     *
     * @param int $id Identifiant.
     *
     * @return bool False si l'agence n'existe pas.
     *
     * @throws ForeignKeyConstraintViolationException Si l'agence est utilisée par un trajet.
     */
    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM agence WHERE id = :id', ['id' => $id]) === 1;
    }

    /**
     * Construit une agence à partir d'une ligne de résultat.
     *
     * @param Row $row Ligne contenant `id` et `nom`.
     *
     * @return Agency
     */
    private static function hydrate(Row $row): Agency
    {
        return new Agency($row->int('id'), $row->string('nom'));
    }
}
