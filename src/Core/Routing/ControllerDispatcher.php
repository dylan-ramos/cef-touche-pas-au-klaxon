<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Container;
use App\Core\Http\NotFoundException;
use Closure;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Relie les routes déclarées dans le routeur aux contrôleurs de l'application.
 *
 * Le routeur instancie lui-même les classes qu'il appelle, sans dépendance.
 * Le répartiteur lui fournit à la place des fonctions qui récupèrent le
 * contrôleur dans le conteneur de services (avec ses dépendances), exécutent
 * les gardes d'accès puis appellent l'action.
 */
final class ControllerDispatcher
{
    /**
     * Identifiant maximal accepté dans une URL (INT UNSIGNED MySQL).
     */
    private const int MAX_ID = 4294967295;

    /**
     * @param Container $container Conteneur fournissant les contrôleurs.
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Construit l'action exécutée par le routeur pour une route.
     *
     * Si la route contient un paramètre `:id`, il est converti en entier
     * positif et transmis comme premier argument de l'action.
     *
     * @param class-string                  $controller Classe du contrôleur.
     * @param string                        $method     Méthode publique du contrôleur.
     * @param list<Closure(): ?Response>    $guards     Gardes exécutées avant l'action ; une réponse
     *                                                  non nulle interrompt le traitement.
     *
     * @return Closure(string=): Response
     */
    public function to(string $controller, string $method, array $guards = []): Closure
    {
        return function (string $id = '') use ($controller, $method, $guards): Response {
            foreach ($guards as $guard) {
                $response = $guard();
                if ($response instanceof Response) {
                    return $response;
                }
            }

            $action = [$this->container->get($controller), $method];
            if (!is_callable($action)) {
                throw new LogicException(sprintf('Action introuvable : %s::%s.', $controller, $method));
            }

            $response = $id === '' ? $action() : $action(self::parseId($id));
            if (!$response instanceof Response) {
                throw new LogicException(sprintf('L\'action %s::%s doit retourner une réponse.', $controller, $method));
            }

            return $response;
        };
    }

    /**
     * Convertit un identifiant d'URL en entier strictement positif.
     *
     * @param string $id Segment d'URL.
     *
     * @return int
     *
     * @throws NotFoundException Si l'identifiant n'est pas un entier positif valide.
     */
    public static function parseId(string $id): int
    {
        if (preg_match('/^[1-9]\d{0,9}$/', $id) !== 1 || (int) $id > self::MAX_ID) {
            throw new NotFoundException();
        }

        return (int) $id;
    }
}
