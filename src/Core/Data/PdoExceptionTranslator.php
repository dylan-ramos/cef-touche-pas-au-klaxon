<?php

declare(strict_types=1);

namespace App\Core\Data;

use App\Core\Data\Exception\CheckConstraintViolationException;
use App\Core\Data\Exception\ConstraintViolationException;
use App\Core\Data\Exception\ForeignKeyConstraintViolationException;
use App\Core\Data\Exception\UniqueConstraintViolationException;
use PDOException;

/**
 * Traduit les erreurs d'intégrité MySQL en exceptions typées.
 */
final class PdoExceptionTranslator
{
    private const int DUPLICATE_ENTRY = 1062;
    private const int ROW_IS_REFERENCED = 1451;
    private const int NO_REFERENCED_ROW = 1452;
    private const int CHECK_CONSTRAINT_VIOLATED = 3819;

    /**
     * Retourne l'exception typée correspondant à une erreur PDO, ou l'erreur
     * d'origine si elle ne concerne pas une contrainte d'intégrité.
     *
     * @param PDOException $exception Erreur levée par PDO.
     *
     * @return ConstraintViolationException|PDOException
     */
    public static function translate(PDOException $exception): ConstraintViolationException|PDOException
    {
        $driverCode = $exception->errorInfo[1] ?? null;

        return match ($driverCode) {
            self::DUPLICATE_ENTRY => new UniqueConstraintViolationException($exception),
            self::ROW_IS_REFERENCED, self::NO_REFERENCED_ROW => new ForeignKeyConstraintViolationException($exception),
            self::CHECK_CONSTRAINT_VIOLATED => new CheckConstraintViolationException($exception),
            default => $exception,
        };
    }
}
