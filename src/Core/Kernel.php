<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\NotFoundException;
use App\Core\Routing\ControllerDispatcher;
use Buki\Router\Router;
use Closure;
use ErrorException;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Point d'entrée de l'application : configuration, services, routage et
 * gestion globale des erreurs.
 */
final class Kernel
{
    /**
     * @param string $rootDir Répertoire racine du projet.
     */
    public function __construct(private readonly string $rootDir)
    {
    }

    /**
     * Traite la requête HTTP courante et envoie la réponse.
     *
     * @return void
     */
    public function run(): void
    {
        $config = Config::load($this->rootDir);
        date_default_timezone_set($config->string('APP_TIMEZONE', 'Europe/Paris'));
        $this->registerErrorConversion();

        $container = $this->buildContainer($config);
        $errorHandler = $container->get(ErrorHandler::class);

        try {
            $router = new Router(
                ['debug' => true, 'base_folder' => $this->rootDir . '/public'],
                $this->createRequest(),
            );
            $router->notFound(static fn (): Response => $errorHandler->handle(new NotFoundException()));

            $routes = $this->requireClosure('config/routes.php');
            $routes($router, $container->get(ControllerDispatcher::class), $container);

            $router->run();
        } catch (Throwable $exception) {
            $errorHandler->handle($exception)->send();
        }
    }

    /**
     * Crée la requête HTTP courante.
     *
     * Une requête HEAD est traitée comme un GET : le serveur web n'en renvoie
     * que les en-têtes, ce qui évite de déclarer chaque route deux fois.
     *
     * @return Request
     */
    private function createRequest(): Request
    {
        $request = Request::createFromGlobals();
        if ($request->isMethod(Request::METHOD_HEAD)) {
            $request->setMethod(Request::METHOD_GET);
        }

        return $request;
    }

    /**
     * Construit le conteneur de services à partir de `config/services.php`.
     *
     * @param Config $config Configuration chargée.
     *
     * @return Container
     */
    private function buildContainer(Config $config): Container
    {
        $container = new Container();
        $container->instance(Config::class, $config);
        $container->instance(self::class, $this);

        $services = $this->requireClosure('config/services.php');
        $services($container, $config, $this->rootDir);

        return $container;
    }

    /**
     * Convertit les avertissements PHP du code applicatif en exceptions.
     *
     * Les avertissements émis par les bibliothèques tierces restent traités
     * par PHP (journalisés) afin de ne pas interrompre une réponse valide.
     *
     * @return void
     */
    private function registerErrorConversion(): void
    {
        $vendorDir = $this->rootDir . '/vendor/';

        set_error_handler(static function (int $severity, string $message, string $file, int $line) use ($vendorDir): bool {
            if ((error_reporting() & $severity) === 0 || str_starts_with($file, $vendorDir)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }

    /**
     * Charge un fichier de configuration PHP qui retourne une fonction.
     *
     * @param string $relativePath Chemin relatif à la racine du projet.
     *
     * @return Closure
     *
     * @throws LogicException Si le fichier ne retourne pas une fonction.
     */
    private function requireClosure(string $relativePath): Closure
    {
        $closure = require $this->rootDir . '/' . $relativePath;
        if (!$closure instanceof Closure) {
            throw new LogicException(sprintf('%s doit retourner une fonction.', $relativePath));
        }

        return $closure;
    }
}
