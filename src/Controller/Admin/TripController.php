<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Clock\Clock;
use App\Core\Session\Flash;
use App\Core\View;
use App\Repository\TripRepository;
use App\Security\Auth;
use App\Service\TripService;
use LogicException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consultation et suppression de tous les trajets par l'administrateur.
 */
final class TripController
{
    private const string LIST_URL = '/admin/trips';

    /**
     * @param View           $view    Moteur de rendu.
     * @param TripRepository $trips   Dépôt des trajets.
     * @param TripService    $service Règles de gestion des trajets.
     * @param Auth           $auth    Administrateur connecté.
     * @param Flash          $flash   Messages après redirection.
     * @param Clock          $clock   Heure de référence (trajets passés).
     */
    public function __construct(
        private readonly View $view,
        private readonly TripRepository $trips,
        private readonly TripService $service,
        private readonly Auth $auth,
        private readonly Flash $flash,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Liste tous les trajets, passés et complets compris.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->view->render('admin/trips', [
            'pageTitle' => 'Trajets',
            'trips' => $this->trips->findAll(),
            'now' => $this->clock->now(),
        ]);
    }

    /**
     * Supprime un trajet.
     *
     * @param int $id Identifiant du trajet.
     *
     * @return Response Redirection vers la liste des trajets.
     */
    public function delete(int $id): Response
    {
        $admin = $this->auth->user() ?? throw new LogicException('Route d\'administration appelée sans utilisateur connecté.');
        $this->service->delete($admin, $id);
        $this->flash->success('Le trajet a été supprimé.');

        return new RedirectResponse(self::LIST_URL);
    }
}
