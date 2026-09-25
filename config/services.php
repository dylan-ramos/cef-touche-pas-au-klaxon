<?php

/**
 * Déclaration des services de l'application.
 *
 * Chaque service est construit à la demande par le conteneur ; ajouter ici
 * toute nouvelle classe nécessitant des dépendances (contrôleurs, dépôts,
 * services métier).
 */

declare(strict_types=1);

use App\Controller\Admin\DashboardController;
use App\Controller\AuthController;
use App\Controller\HomeController;
use App\Core\Clock\Clock;
use App\Core\Clock\SystemClock;
use App\Core\Config;
use App\Core\Container;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Routing\ControllerDispatcher;
use App\Core\Security\Csrf;
use App\Core\Session\Flash;
use App\Core\Session\NativeSession;
use App\Core\Session\Session;
use App\Core\View;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use App\Security\Auth;
use App\Validator\LoginValidator;
use Symfony\Component\HttpFoundation\Request;

return static function (Container $container, Config $config, string $rootDir): void {
    // --- Noyau ---
    $container->set(Database::class, static fn (): Database => Database::fromConfig($config));

    $container->set(Session::class, static fn (): Session => new NativeSession(
        $config->string('SESSION_NAME', 'klaxon_session'),
        $config->bool('SESSION_SECURE'),
    ));

    $container->set(Flash::class, static fn (Container $c): Flash => new Flash($c->get(Session::class)));

    $container->set(Csrf::class, static fn (Container $c): Csrf => new Csrf($c->get(Session::class)));

    $container->set(View::class, static function (Container $c) use ($config, $rootDir): View {
        $view = new View($rootDir . '/templates');
        $view->share('appName', $config->string('APP_NAME', 'Touche pas au klaxon'));
        $view->share('copyrightHolder', $config->string('APP_COPYRIGHT_HOLDER', 'Touche pas au klaxon'));
        $view->share('currentYear', (int) date('Y'));
        $view->share('flash', $c->get(Flash::class));
        $view->shareLazy('currentUser', static fn () => $c->get(Auth::class)->user());
        $view->shareLazy('csrfToken', static fn (): string => $c->get(Csrf::class)->token());

        return $view;
    });

    $container->set(ErrorHandler::class, static fn (Container $c): ErrorHandler => new ErrorHandler(
        $c->get(View::class),
        $config->bool('APP_DEBUG'),
    ));

    $container->set(Clock::class, static fn (): Clock => new SystemClock());

    $container->set(ControllerDispatcher::class, static fn (Container $c): ControllerDispatcher => new ControllerDispatcher($c));

    // --- Accès aux données ---
    $container->set(UserRepository::class, static fn (Container $c): UserRepository => new UserRepository(
        $c->get(Database::class),
    ));

    $container->set(TripRepository::class, static fn (Container $c): TripRepository => new TripRepository(
        $c->get(Database::class),
    ));

    // --- Sécurité ---
    $container->set(Auth::class, static fn (Container $c): Auth => new Auth(
        $c->get(Session::class),
        $c->get(UserRepository::class),
        $c->get(Csrf::class),
        $c->get(Flash::class),
    ));

    $container->set(LoginValidator::class, static fn (): LoginValidator => new LoginValidator());

    // --- Contrôleurs ---
    $container->set(HomeController::class, static fn (Container $c): HomeController => new HomeController(
        $c->get(View::class),
        $c->get(TripRepository::class),
        $c->get(Auth::class),
        $c->get(Clock::class),
    ));

    $container->set(AuthController::class, static fn (Container $c): AuthController => new AuthController(
        $c->get(Request::class),
        $c->get(View::class),
        $c->get(Auth::class),
        $c->get(LoginValidator::class),
        $c->get(Flash::class),
    ));

    $container->set(DashboardController::class, static fn (Container $c): DashboardController => new DashboardController(
        $c->get(View::class),
    ));
};
