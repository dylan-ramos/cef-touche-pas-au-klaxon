<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Core\Database;
use App\Core\Security\Csrf;
use App\Core\Session\ArraySession;
use App\Core\Session\Flash;
use App\Core\View;
use App\Repository\UserRepository;
use App\Security\Auth;

/**
 * Assemble les services de l'application pour les tests, avec une session en
 * mémoire et la base de test.
 */
final class ServiceFactory
{
    public readonly ArraySession $session;

    public readonly Flash $flash;

    public readonly Csrf $csrf;

    public readonly Auth $auth;

    public readonly View $view;

    /**
     * @param Database $database Base de test.
     */
    public function __construct(public readonly Database $database)
    {
        $this->session = new ArraySession();
        $this->flash = new Flash($this->session);
        $this->csrf = new Csrf($this->session);
        $this->auth = new Auth($this->session, new UserRepository($database), $this->csrf, $this->flash);

        $this->view = new View(dirname(__DIR__, 2) . '/templates');
        $this->view->share('appName', 'Touche pas au klaxon');
        $this->view->share('copyrightHolder', 'Touche pas au klaxon');
        $this->view->share('currentYear', 2026);
        $this->view->share('flash', $this->flash);
        $this->view->shareLazy('currentUser', fn () => $this->auth->user());
        $this->view->shareLazy('csrfToken', fn (): string => $this->csrf->token());
    }
}
