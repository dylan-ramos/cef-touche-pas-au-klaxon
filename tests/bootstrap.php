<?php

/**
 * Amorçage des tests : chargement automatique et fuseau horaire de l'application.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('Europe/Paris');
