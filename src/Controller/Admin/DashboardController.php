<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tableau de bord de l'administrateur.
 */
final class DashboardController
{
    /**
     * @param View $view Moteur de rendu.
     */
    public function __construct(private readonly View $view)
    {
    }

    /**
     * Affiche le tableau de bord.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->view->render('admin/dashboard', ['pageTitle' => 'Tableau de bord']);
    }
}
