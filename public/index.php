<?php

/**
 * Contrôleur frontal : toutes les requêtes HTTP passent par ce fichier.
 */

declare(strict_types=1);

use App\Core\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Kernel(dirname(__DIR__)))->run();
