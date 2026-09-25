<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Clock\Clock;
use App\Core\Session\Flash;
use App\Core\Validation\ValidationException;
use App\Core\View;
use App\Entity\User;
use App\Repository\AgencyRepository;
use App\Security\Auth;
use App\Service\TripService;
use App\Validator\TripValidator;
use LogicException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proposition, modification et suppression de trajets par les employés.
 *
 * Toutes les actions sont réservées aux utilisateurs connectés (garde de
 * route) ; les droits sur un trajet précis sont vérifiés par TripService.
 */
final class TripController
{
    /**
     * @param Request          $request  Requête HTTP courante.
     * @param View             $view     Moteur de rendu.
     * @param Auth             $auth     Utilisateur connecté.
     * @param TripService      $service  Règles de gestion des trajets.
     * @param AgencyRepository $agencies Agences proposées dans le formulaire.
     * @param Flash            $flash    Messages après redirection.
     * @param Clock            $clock    Heure de référence (valeur minimale des dates).
     */
    public function __construct(
        private readonly Request $request,
        private readonly View $view,
        private readonly Auth $auth,
        private readonly TripService $service,
        private readonly AgencyRepository $agencies,
        private readonly Flash $flash,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Formulaire de proposition d'un trajet.
     *
     * @return Response
     */
    public function create(): Response
    {
        return $this->form('/trips', 'Proposer un trajet');
    }

    /**
     * Enregistre un nouveau trajet.
     *
     * @return Response Redirection vers la liste, ou formulaire en erreur (422).
     */
    public function store(): Response
    {
        try {
            $this->service->create($this->user(), $this->request->request->all());
        } catch (ValidationException $exception) {
            return $this->form('/trips', 'Proposer un trajet', $exception->old(), $exception->errors());
        }

        $this->flash->success('Le trajet a été créé.');

        return new RedirectResponse('/');
    }

    /**
     * Formulaire de modification d'un trajet de l'utilisateur.
     *
     * @param int $id Identifiant du trajet.
     *
     * @return Response
     */
    public function edit(int $id): Response
    {
        $trip = $this->service->findEditable($this->user(), $id);

        return $this->form('/trips/' . $id, 'Modifier le trajet', TripService::formValues($trip));
    }

    /**
     * Enregistre la modification d'un trajet.
     *
     * @param int $id Identifiant du trajet.
     *
     * @return Response Redirection vers la liste, ou formulaire en erreur (422).
     */
    public function update(int $id): Response
    {
        try {
            $this->service->update($this->user(), $id, $this->request->request->all());
        } catch (ValidationException $exception) {
            return $this->form('/trips/' . $id, 'Modifier le trajet', $exception->old(), $exception->errors());
        }

        $this->flash->success('Le trajet a été modifié.');

        return new RedirectResponse('/');
    }

    /**
     * Supprime un trajet de l'utilisateur.
     *
     * @param int $id Identifiant du trajet.
     *
     * @return Response Redirection vers la liste.
     */
    public function delete(int $id): Response
    {
        $this->service->delete($this->user(), $id);
        $this->flash->success('Le trajet a été supprimé.');

        return new RedirectResponse('/');
    }

    /**
     * Rend le formulaire de trajet (création ou modification).
     *
     * @param string                $action Adresse de soumission.
     * @param string                $title  Titre de la page.
     * @param array<string, string> $old    Valeurs des champs.
     * @param array<string, string> $errors Erreurs par champ.
     *
     * @return Response
     */
    private function form(string $action, string $title, array $old = [], array $errors = []): Response
    {
        return $this->view->render('trip/form', [
            'pageTitle' => $title,
            'action' => $action,
            'author' => $this->user(),
            'agencies' => $this->agencies->findAll(),
            'old' => $old,
            'errors' => $errors,
            'minDateTime' => $this->clock->now()->format(TripValidator::DATETIME_FORMAT),
            'maxSeats' => TripValidator::MAX_SEATS,
        ], $errors === [] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Utilisateur connecté, garanti par la garde de route.
     *
     * @return User
     *
     * @throws LogicException Si la route n'est pas protégée.
     */
    private function user(): User
    {
        return $this->auth->user() ?? throw new LogicException('Route de trajet appelée sans utilisateur connecté.');
    }
}
