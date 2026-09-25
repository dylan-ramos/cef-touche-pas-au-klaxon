<?php

/**
 * Déclaration des services de l'application.
 *
 * Chaque service est construit à la demande par le conteneur ; ajouter ici
 * toute nouvelle classe nécessitant des dépendances (contrôleurs, dépôts,
 * services métier).
 */

declare(strict_types=1);

use App\Controller\HomeController;
use App\Core\Config;
use App\Core\Container;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Routing\ControllerDispatcher;
use App\Core\View;

return static function (Container $container, Config $config, string $rootDir): void {
    // --- Noyau ---
    $container->set(Database::class, static fn (): Database => Database::fromConfig($config));

    $container->set(View::class, static function () use ($config, $rootDir): View {
        $view = new View($rootDir . '/templates');
        $view->share('appName', $config->string('APP_NAME', 'Touche pas au klaxon'));
        $view->share('copyrightHolder', $config->string('APP_COPYRIGHT_HOLDER', 'Touche pas au klaxon'));
        $view->share('currentYear', (int) date('Y'));

        return $view;
    });

    $container->set(ErrorHandler::class, static fn (Container $c): ErrorHandler => new ErrorHandler(
        $c->get(View::class),
        $config->bool('APP_DEBUG'),
    ));

    $container->set(ControllerDispatcher::class, static fn (Container $c): ControllerDispatcher => new ControllerDispatcher($c));

    // --- Contrôleurs ---
    $container->set(HomeController::class, static fn (Container $c): HomeController => new HomeController(
        $c->get(View::class),
    ));
};
