<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Clock\Clock;
use App\Core\View;
use App\Repository\AgencyRepository;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tableau de bord de l'administrateur.
 */
final class DashboardController
{
    /**
     * @param View             $view     Moteur de rendu.
     * @param UserRepository   $users    Dépôt des utilisateurs.
     * @param AgencyRepository $agencies Dépôt des agences.
     * @param TripRepository   $trips    Dépôt des trajets.
     * @param Clock            $clock    Heure de référence.
     */
    public function __construct(
        private readonly View $view,
        private readonly UserRepository $users,
        private readonly AgencyRepository $agencies,
        private readonly TripRepository $trips,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Affiche le tableau de bord et ses indicateurs.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->view->render('admin/dashboard', [
            'pageTitle' => 'Tableau de bord',
            'userCount' => $this->users->count(),
            'agencyCount' => $this->agencies->count(),
            'upcomingTripCount' => $this->trips->countUpcoming($this->clock->now()),
        ]);
    }
}
