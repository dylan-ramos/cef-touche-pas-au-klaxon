<?php

/**
 * Déclaration des services de l'application.
 *
 * Chaque service est construit à la demande par le conteneur ; ajouter ici
 * toute nouvelle classe nécessitant des dépendances (contrôleurs, dépôts,
 * services métier).
 */

declare(strict_types=1);

use App\Controller\Admin\AgencyController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\TripController as AdminTripController;
use App\Controller\Admin\UserController;
use App\Controller\AuthController;
use App\Controller\HomeController;
use App\Controller\TripController;
use App\Core\Clock\Clock;
use App\Core\Clock\SystemClock;
use App\Core\Config;
use App\Core\Container;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Routing\ControllerDispatcher;
use App\Core\Security\Csrf;
use App\Core\Security\Throttle\FileThrottleStore;
use App\Core\Security\Throttle\LoginThrottle;
use App\Core\Session\Flash;
use App\Core\Session\NativeSession;
use App\Core\Session\Session;
use App\Core\View;
use App\Repository\AgencyRepository;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use App\Security\Auth;
use App\Service\AgencyService;
use App\Service\TripService;
use App\Validator\AgencyValidator;
use App\Validator\LoginValidator;
use App\Validator\TripValidator;
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

    $container->set(AgencyRepository::class, static fn (Container $c): AgencyRepository => new AgencyRepository(
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

    $container->set(LoginThrottle::class, static fn (Container $c): LoginThrottle => new LoginThrottle(
        new FileThrottleStore($rootDir . '/var/throttle'),
        $c->get(Clock::class),
        $config->int('LOGIN_MAX_ATTEMPTS', 5),
        $config->int('LOGIN_LOCK_SECONDS', 900),
    ));

    $container->set(LoginValidator::class, static fn (): LoginValidator => new LoginValidator());

    // --- Règles métier ---
    $container->set(TripValidator::class, static fn (Container $c): TripValidator => new TripValidator(
        $c->get(AgencyRepository::class),
        $c->get(Clock::class),
    ));

    $container->set(TripService::class, static fn (Container $c): TripService => new TripService(
        $c->get(TripRepository::class),
        $c->get(TripValidator::class),
        $c->get(Clock::class),
    ));

    $container->set(AgencyValidator::class, static fn (Container $c): AgencyValidator => new AgencyValidator(
        $c->get(AgencyRepository::class),
    ));

    $container->set(AgencyService::class, static fn (Container $c): AgencyService => new AgencyService(
        $c->get(AgencyRepository::class),
        $c->get(AgencyValidator::class),
    ));

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
        $c->get(LoginThrottle::class),
    ));

    $container->set(TripController::class, static fn (Container $c): TripController => new TripController(
        $c->get(Request::class),
        $c->get(View::class),
        $c->get(Auth::class),
        $c->get(TripService::class),
        $c->get(AgencyRepository::class),
        $c->get(Flash::class),
        $c->get(Clock::class),
    ));

    // --- Contrôleurs d'administration ---
    $container->set(DashboardController::class, static fn (Container $c): DashboardController => new DashboardController(
        $c->get(View::class),
        $c->get(UserRepository::class),
        $c->get(AgencyRepository::class),
        $c->get(TripRepository::class),
        $c->get(Clock::class),
    ));

    $container->set(UserController::class, static fn (Container $c): UserController => new UserController(
        $c->get(View::class),
        $c->get(UserRepository::class),
    ));

    $container->set(AgencyController::class, static fn (Container $c): AgencyController => new AgencyController(
        $c->get(Request::class),
        $c->get(View::class),
        $c->get(AgencyRepository::class),
        $c->get(AgencyService::class),
        $c->get(Flash::class),
    ));

    $container->set(AdminTripController::class, static fn (Container $c): AdminTripController => new AdminTripController(
        $c->get(View::class),
        $c->get(TripRepository::class),
        $c->get(TripService::class),
        $c->get(Auth::class),
        $c->get(Flash::class),
        $c->get(Clock::class),
    ));
};
