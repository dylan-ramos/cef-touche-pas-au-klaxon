<?php

declare(strict_types=1);

namespace App\Core\Routing;

/**
 * Détermine les méthodes HTTP acceptées par une adresse, à partir de la
 * table de routes du routeur. Permet de répondre 405 plutôt que 404 lorsque
 * l'adresse existe pour une autre méthode.
 */
final class AllowedMethods
{
    /**
     * Motifs de paramètres utilisés dans les routes de l'application.
     */
    private const array PATTERNS = [
        ':id' => '(\d+)',
        ':int' => '(\d+)',
        ':any' => '([^/]+)',
        ':slug' => '([\w\-_]+)',
    ];

    /**
     * Retourne les méthodes des routes correspondant au chemin.
     *
     * @param array<array-key, mixed> $routes Table de routes (`route` et `method` par entrée).
     * @param string                  $path   Chemin demandé.
     *
     * @return list<string> Méthodes triées, HEAD ajoutée lorsque GET est acceptée.
     */
    public static function for(array $routes, string $path): array
    {
        $methods = [];
        foreach ($routes as $route) {
            if (!is_array($route) || !is_string($route['route'] ?? null) || !is_string($route['method'] ?? null)) {
                continue;
            }

            $pattern = '#^' . strtr($route['route'], self::PATTERNS) . '$#';
            if (preg_match($pattern, $path) === 1) {
                array_push($methods, ...explode('|', $route['method']));
            }
        }

        if (in_array('GET', $methods, true)) {
            $methods[] = 'HEAD';
        }

        $methods = array_values(array_unique($methods));
        sort($methods);

        return $methods;
    }
}
