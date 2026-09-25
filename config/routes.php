<?php

/**
 * Table de routage de l'application.
 *
 * Chaque route associe une méthode HTTP et un chemin à une action de
 * contrôleur via le répartiteur, qui applique les gardes d'accès :
 * - guardGuest : visiteurs uniquement ;
 * - guardUser  : utilisateurs connectés ;
 * - guardAdmin : administrateurs.
 * Le motif `:id` n'accepte que des chiffres. Toute route POST exige un jeton CSRF.
 */

declare(strict_types=1);

use App\Controller\Admin\DashboardController;
use App\Controller\AuthController;
use App\Controller\HomeController;
use App\Controller\TripController;
use App\Core\Container;
use App\Core\Routing\ControllerDispatcher;
use App\Security\Auth;
use Buki\Router\Router;

return static function (Router $router, ControllerDispatcher $dispatch, Container $container): void {
    $auth = $container->get(Auth::class);
    $guest = [$auth->guardGuest()];
    $user = [$auth->guardUser()];
    $admin = [$auth->guardAdmin()];

    // --- Public ---
    $router->get('/', $dispatch->to(HomeController::class, 'index'));

    // --- Authentification ---
    $router->get('/login', $dispatch->to(AuthController::class, 'showLogin', $guest));
    $router->post('/login', $dispatch->to(AuthController::class, 'login', $guest));
    $router->post('/logout', $dispatch->to(AuthController::class, 'logout', $user));

    // --- Trajets (employés connectés) ---
    $router->get('/trips/create', $dispatch->to(TripController::class, 'create', $user));
    $router->post('/trips', $dispatch->to(TripController::class, 'store', $user));
    $router->get('/trips/:id/edit', $dispatch->to(TripController::class, 'edit', $user));
    $router->post('/trips/:id', $dispatch->to(TripController::class, 'update', $user));
    $router->post('/trips/:id/delete', $dispatch->to(TripController::class, 'delete', $user));

    // --- Administration ---
    $router->get('/admin', $dispatch->to(DashboardController::class, 'index', $admin));
};
