<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Data\Exception\ForeignKeyConstraintViolationException;
use App\Core\Data\Exception\UniqueConstraintViolationException;
use App\Core\Http\NotFoundException;
use App\Core\Validation\ValidationException;
use App\Entity\Agency;
use App\Repository\AgencyRepository;
use App\Service\Exception\AgencyInUseException;
use App\Validator\AgencyValidator;

/**
 * Gestion du référentiel des agences (réservée aux administrateurs).
 */
final class AgencyService
{
    private const string DUPLICATE = 'Une agence porte déjà ce nom.';

    /**
     * @param AgencyRepository $agencies  Dépôt des agences.
     * @param AgencyValidator  $validator Validation du nom.
     */
    public function __construct(
        private readonly AgencyRepository $agencies,
        private readonly AgencyValidator $validator,
    ) {
    }

    /**
     * Retourne une agence existante.
     *
     * @param int $id Identifiant.
     *
     * @return Agency
     *
     * @throws NotFoundException Si l'agence n'existe pas.
     */
    public function get(int $id): Agency
    {
        return $this->agencies->findById($id) ?? throw new NotFoundException('Cette agence n\'existe pas ou a été supprimée.');
    }

    /**
     * Crée une agence.
     *
     * @param array<array-key, mixed> $input Saisie du formulaire.
     *
     * @return Agency Agence créée.
     *
     * @throws ValidationException Si le nom est invalide ou déjà utilisé.
     */
    public function create(array $input): Agency
    {
        $name = $this->validator->validate($input);

        try {
            return new Agency($this->agencies->create($name), $name);
        } catch (UniqueConstraintViolationException) {
            throw new ValidationException(['name' => self::DUPLICATE], ['name' => $name]);
        }
    }

    /**
     * Renomme une agence.
     *
     * @param int                     $id    Identifiant.
     * @param array<array-key, mixed> $input Saisie du formulaire.
     *
     * @return Agency Agence modifiée.
     *
     * @throws NotFoundException   Si l'agence n'existe pas.
     * @throws ValidationException Si le nom est invalide ou déjà utilisé.
     */
    public function update(int $id, array $input): Agency
    {
        $this->get($id);
        $name = $this->validator->validate($input, $id);

        try {
            $this->agencies->update($id, $name);
        } catch (UniqueConstraintViolationException) {
            throw new ValidationException(['name' => self::DUPLICATE], ['name' => $name]);
        }

        return new Agency($id, $name);
    }

    /**
     * Supprime une agence qui n'est utilisée par aucun trajet.
     *
     * @param int $id Identifiant.
     *
     * @return Agency Agence supprimée.
     *
     * @throws NotFoundException    Si l'agence n'existe pas.
     * @throws AgencyInUseException Si des trajets y font référence.
     */
    public function delete(int $id): Agency
    {
        $agency = $this->get($id);

        if ($this->agencies->isUsedByTrips($id)) {
            throw new AgencyInUseException($agency->name);
        }

        try {
            $this->agencies->delete($id);
        } catch (ForeignKeyConstraintViolationException) {
            throw new AgencyInUseException($agency->name);
        }

        return $agency;
    }
}
