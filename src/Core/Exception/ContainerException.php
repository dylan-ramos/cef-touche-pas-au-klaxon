<?php

declare(strict_types=1);

namespace App\Core\Exception;

use RuntimeException;

/**
 * Levée lorsqu'un service ne peut pas être fourni par le conteneur.
 */
final class ContainerException extends RuntimeException
{
}
