<?php

declare(strict_types=1);

namespace App\Core\Data\Exception;

/**
 * Règle de cohérence CHECK non respectée (code MySQL 3819).
 */
final class CheckConstraintViolationException extends ConstraintViolationException
{
}
