<?php

/**
 * Table de routage de l'application.
 *
 * Chaque route associe une méthode HTTP et un chemin à une action de
 * contrôleur via le répartiteur, qui applique les éventuelles gardes d'accès.
 * Le motif `:id` n'accepte que des chiffres.
 */

declare(strict_types=1);

use App\Controller\HomeController;
use App\Core\Container;
use App\Core\Routing\ControllerDispatcher;
use Buki\Router\Router;

return static function (Router $router, ControllerDispatcher $dispatch, Container $container): void {
    $router->get('/', $dispatch->to(HomeController::class, 'index'));
};
