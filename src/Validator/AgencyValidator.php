<?php

declare(strict_types=1);

namespace App\Validator;

use App\Core\Validation\Input;
use App\Core\Validation\ValidationException;
use App\Repository\AgencyRepository;

/**
 * Validation du nom d'une agence.
 *
 * Le nom est normalisé (espaces superflus retirés) puis contrôlé : obligatoire,
 * 100 caractères au plus, lettres, espaces, apostrophes et traits d'union
 * uniquement, et unique (sans tenir compte de la casse ni des accents).
 */
final class AgencyValidator
{
    public const int NAME_MAX_LENGTH = 100;

    /**
     * Commence par une lettre ; puis lettres, espaces, apostrophes ou traits d'union.
     */
    private const string NAME_PATTERN = '/^\p{L}[\p{L} \'’-]*$/u';

    private const string NAME_CHARACTERS_ERROR
        = 'Le nom ne peut contenir que des lettres, espaces, apostrophes et traits d\'union.';

    /**
     * @param AgencyRepository $agencies Référentiel des agences (contrôle d'unicité).
     */
    public function __construct(private readonly AgencyRepository $agencies)
    {
    }

    /**
     * Valide la saisie et retourne le nom normalisé.
     *
     * @param array<array-key, mixed> $data      Corps de la requête.
     * @param int|null                $excludeId Agence modifiée, ignorée pour le contrôle d'unicité.
     *
     * @return string
     *
     * @throws ValidationException Si le nom est invalide ou déjà utilisé.
     */
    public function validate(array $data, ?int $excludeId = null): string
    {
        $name = self::normalize((new Input($data))->string('name'));
        $error = match (true) {
            $name === '' => 'Le nom de l\'agence est obligatoire.',
            mb_strlen($name) > self::NAME_MAX_LENGTH => sprintf('Le nom est limité à %d caractères.', self::NAME_MAX_LENGTH),
            preg_match(self::NAME_PATTERN, $name) !== 1 => self::NAME_CHARACTERS_ERROR,
            $this->agencies->existsByName($name, $excludeId) => 'Une agence porte déjà ce nom.',
            default => null,
        };

        if ($error !== null) {
            throw new ValidationException(['name' => $error], ['name' => mb_substr($name, 0, self::NAME_MAX_LENGTH + 20)]);
        }

        return $name;
    }

    /**
     * Retire les espaces de début et de fin et réduit les espaces multiples.
     *
     * @param string $name Nom saisi.
     *
     * @return string
     */
    private static function normalize(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }
}
