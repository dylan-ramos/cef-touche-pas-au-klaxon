<?php

declare(strict_types=1);

namespace App\Core\Exception;

use RuntimeException;

/**
 * Levée lorsqu'un paramètre de configuration est absent ou invalide.
 */
final class ConfigException extends RuntimeException
{
}
