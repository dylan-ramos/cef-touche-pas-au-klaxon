<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page d'accueil de l'application.
 */
final class HomeController
{
    /**
     * @param View $view Moteur de rendu.
     */
    public function __construct(private readonly View $view)
    {
    }

    /**
     * Affiche la page d'accueil.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->view->render('home/index', ['pageTitle' => 'Accueil']);
    }
}
