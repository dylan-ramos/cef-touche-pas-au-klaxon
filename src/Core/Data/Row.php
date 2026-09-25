<?php

declare(strict_types=1);

namespace App\Core\Data;

use DateTimeImmutable;
use UnexpectedValueException;

/**
 * Lecture typée d'une ligne de résultat SQL.
 *
 * Garantit que chaque colonne lue a le type attendu avant hydratation d'un
 * objet ; une incohérence entre le schéma et le code est signalée
 * immédiatement plutôt que propagée silencieusement.
 */
final class Row
{
    /**
     * @param array<array-key, mixed> $row Ligne retournée par PDO.
     */
    public function __construct(private readonly array $row)
    {
    }

    /**
     * Lit une colonne entière.
     *
     * @param string $column Nom de la colonne.
     *
     * @return int
     *
     * @throws UnexpectedValueException Si la colonne est absente ou non entière.
     */
    public function int(string $column): int
    {
        $value = $this->value($column);
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw $this->unexpected($column, 'entier');
    }

    /**
     * Lit une colonne texte.
     *
     * @param string $column Nom de la colonne.
     *
     * @return string
     *
     * @throws UnexpectedValueException Si la colonne est absente ou n'est pas une chaîne.
     */
    public function string(string $column): string
    {
        $value = $this->value($column);
        if (!is_string($value)) {
            throw $this->unexpected($column, 'chaîne');
        }

        return $value;
    }

    /**
     * Lit une colonne DATETIME (format `Y-m-d H:i:s`).
     *
     * @param string $column Nom de la colonne.
     *
     * @return DateTimeImmutable
     *
     * @throws UnexpectedValueException Si la colonne n'est pas une date valide.
     */
    public function dateTime(string $column): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $this->string($column));
        if ($date === false) {
            throw $this->unexpected($column, 'date-heure');
        }

        return $date;
    }

    /**
     * Retourne la valeur brute d'une colonne existante.
     *
     * @param string $column Nom de la colonne.
     *
     * @return mixed
     *
     * @throws UnexpectedValueException Si la colonne est absente.
     */
    private function value(string $column): mixed
    {
        if (!array_key_exists($column, $this->row)) {
            throw new UnexpectedValueException(sprintf('Colonne « %s » absente du résultat.', $column));
        }

        return $this->row[$column];
    }

    /**
     * Construit l'exception signalant un type inattendu.
     *
     * @param string $column   Nom de la colonne.
     * @param string $expected Type attendu.
     *
     * @return UnexpectedValueException
     */
    private function unexpected(string $column, string $expected): UnexpectedValueException
    {
        return new UnexpectedValueException(sprintf('Colonne « %s » : %s attendu.', $column, $expected));
    }
}
