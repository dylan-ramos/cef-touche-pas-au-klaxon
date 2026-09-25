<?php

declare(strict_types=1);

namespace App\Core\Validation;

/**
 * Lecture défensive des champs d'un formulaire.
 *
 * Un champ absent, ou transmis sous forme de tableau par une requête
 * forgée, est lu comme une chaîne vide.
 */
final class Input
{
    /**
     * @param array<array-key, mixed> $data Données brutes reçues (corps de la requête).
     */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * Retourne un champ texte, espaces de début et de fin retirés.
     *
     * @param string $field Nom du champ.
     *
     * @return string
     */
    public function string(string $field): string
    {
        return trim($this->raw($field));
    }

    /**
     * Retourne un champ texte tel que saisi (mot de passe).
     *
     * @param string $field Nom du champ.
     *
     * @return string
     */
    public function raw(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * Retourne un entier strictement positif, ou null si la saisie n'en est pas un.
     *
     * @param string $field Nom du champ.
     * @param int    $max   Valeur maximale acceptée.
     *
     * @return int|null
     */
    public function positiveInt(string $field, int $max = PHP_INT_MAX): ?int
    {
        return self::toInt($this->string($field), 1, $max);
    }

    /**
     * Retourne un entier compris dans un intervalle, ou null si la saisie n'en est pas un.
     *
     * @param string $field Nom du champ.
     * @param int    $min   Valeur minimale.
     * @param int    $max   Valeur maximale.
     *
     * @return int|null
     */
    public function intBetween(string $field, int $min, int $max): ?int
    {
        return self::toInt($this->string($field), $min, $max);
    }

    /**
     * Convertit une chaîne décimale en entier borné.
     *
     * @param string $value Chaîne à convertir.
     * @param int    $min   Valeur minimale.
     * @param int    $max   Valeur maximale.
     *
     * @return int|null
     */
    private static function toInt(string $value, int $min, int $max): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

        return is_int($int) ? $int : null;
    }
}
