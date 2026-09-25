<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Clock\Clock;
use App\Core\View;
use App\Repository\TripRepository;
use App\Security\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page d'accueil : trajets à venir disposant de places.
 */
final class HomeController
{
    /**
     * @param View           $view  Moteur de rendu.
     * @param TripRepository $trips Dépôt des trajets.
     * @param Auth           $auth  Utilisateur connecté.
     * @param Clock          $clock Heure de référence.
     */
    public function __construct(
        private readonly View $view,
        private readonly TripRepository $trips,
        private readonly Auth $auth,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Affiche la liste des trajets.
     *
     * Un visiteur ne voit que les informations de trajet ; un utilisateur
     * connecté accède en plus aux coordonnées de l'auteur et aux actions sur
     * ses propres trajets.
     *
     * @return Response
     */
    public function index(): Response
    {
        $user = $this->auth->user();

        return $this->view->render('home/index', [
            'pageTitle' => 'Trajets proposés',
            'trips' => $this->trips->findUpcomingAvailable($this->clock->now()),
            'user' => $user,
        ]);
    }
}
