<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\ContainerException;
use Closure;

/**
 * Conteneur de services minimal.
 *
 * Chaque service est déclaré par une fabrique, exécutée une seule fois au
 * premier accès : les services sont partagés pendant toute la requête.
 */
final class Container
{
    /**
     * @var array<string, Closure(self): object> Fabriques indexées par identifiant de service.
     */
    private array $factories = [];

    /**
     * @var array<string, object> Services déjà construits.
     */
    private array $instances = [];

    /**
     * Déclare la fabrique d'un service.
     *
     * @template T of object
     *
     * @param class-string<T>  $id      Identifiant du service (nom de classe).
     * @param Closure(self): T $factory Fabrique recevant le conteneur.
     *
     * @return void
     */
    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * Enregistre une instance déjà construite.
     *
     * @param string $id       Identifiant du service (nom de classe).
     * @param object $instance Instance à partager.
     *
     * @return void
     */
    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Indique si le conteneur sait fournir un service.
     *
     * @param string $id Identifiant du service.
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->factories[$id]);
    }

    /**
     * Retourne un service, en le construisant au premier appel.
     *
     * @template T of object
     *
     * @param class-string<T> $id Identifiant du service (nom de classe).
     *
     * @return T
     *
     * @throws ContainerException Si le service est inconnu ou n'a pas le type attendu.
     */
    public function get(string $id): object
    {
        if (!isset($this->instances[$id])) {
            if (!isset($this->factories[$id])) {
                throw new ContainerException(sprintf('Service inconnu : %s.', $id));
            }
            $this->instances[$id] = ($this->factories[$id])($this);
        }

        $service = $this->instances[$id];
        if (!$service instanceof $id) {
            throw new ContainerException(sprintf('Le service %s n\'a pas le type attendu.', $id));
        }

        return $service;
    }
}
