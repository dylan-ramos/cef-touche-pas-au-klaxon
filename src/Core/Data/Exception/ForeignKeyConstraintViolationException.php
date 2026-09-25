<?php

declare(strict_types=1);

namespace App\Core\Data\Exception;

/**
 * Référence inexistante ou enregistrement encore référencé (codes MySQL 1451, 1452).
 */
final class ForeignKeyConstraintViolationException extends ConstraintViolationException
{
}
