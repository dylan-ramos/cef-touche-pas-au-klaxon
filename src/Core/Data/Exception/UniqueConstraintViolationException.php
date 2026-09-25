<?php

declare(strict_types=1);

namespace App\Core\Data\Exception;

/**
 * Valeur déjà utilisée pour un champ unique (code MySQL 1062).
 */
final class UniqueConstraintViolationException extends ConstraintViolationException
{
}
