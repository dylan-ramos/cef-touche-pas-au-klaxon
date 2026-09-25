<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\View;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consultation des utilisateurs (données issues du SI RH, en lecture seule).
 */
final class UserController
{
    /**
     * @param View           $view  Moteur de rendu.
     * @param UserRepository $users Dépôt des utilisateurs.
     */
    public function __construct(
        private readonly View $view,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * Liste les utilisateurs.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->view->render('admin/users', [
            'pageTitle' => 'Utilisateurs',
            'users' => $this->users->findAll(),
        ]);
    }
}
