<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Session\Flash;
use App\Core\Validation\ValidationException;
use App\Core\View;
use App\Repository\AgencyRepository;
use App\Service\AgencyService;
use App\Service\Exception\AgencyInUseException;
use App\Validator\AgencyValidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestion des agences : liste, création, modification, suppression.
 */
final class AgencyController
{
    private const string LIST_URL = '/admin/agencies';

    /**
     * @param Request          $request  Requête HTTP courante.
     * @param View             $view     Moteur de rendu.
     * @param AgencyRepository $agencies Dépôt des agences.
     * @param AgencyService    $service  Règles de gestion des agences.
     * @param Flash            $flash    Messages après redirection.
     */
    public function __construct(
        private readonly Request $request,
        private readonly View $view,
        private readonly AgencyRepository $agencies,
        private readonly AgencyService $service,
        private readonly Flash $flash,
    ) {
    }

    /**
     * Liste les agences.
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->view->render('admin/agencies/index', [
            'pageTitle' => 'Agences',
            'agencies' => $this->agencies->findAll(),
        ]);
    }

    /**
     * Formulaire de création.
     *
     * @return Response
     */
    public function create(): Response
    {
        return $this->form(self::LIST_URL, 'Nouvelle agence');
    }

    /**
     * Enregistre une nouvelle agence.
     *
     * @return Response Redirection vers la liste, ou formulaire en erreur (422).
     */
    public function store(): Response
    {
        try {
            $agency = $this->service->create($this->request->request->all());
        } catch (ValidationException $exception) {
            return $this->form(self::LIST_URL, 'Nouvelle agence', $exception->old(), $exception->errors());
        }

        $this->flash->success(sprintf('L\'agence « %s » a été créée.', $agency->name));

        return new RedirectResponse(self::LIST_URL);
    }

    /**
     * Formulaire de modification.
     *
     * @param int $id Identifiant de l'agence.
     *
     * @return Response
     */
    public function edit(int $id): Response
    {
        $agency = $this->service->get($id);

        return $this->form(self::LIST_URL . '/' . $id, 'Modifier l\'agence', ['name' => $agency->name]);
    }

    /**
     * Enregistre la modification d'une agence.
     *
     * @param int $id Identifiant de l'agence.
     *
     * @return Response Redirection vers la liste, ou formulaire en erreur (422).
     */
    public function update(int $id): Response
    {
        try {
            $agency = $this->service->update($id, $this->request->request->all());
        } catch (ValidationException $exception) {
            return $this->form(self::LIST_URL . '/' . $id, 'Modifier l\'agence', $exception->old(), $exception->errors());
        }

        $this->flash->success(sprintf('L\'agence « %s » a été modifiée.', $agency->name));

        return new RedirectResponse(self::LIST_URL);
    }

    /**
     * Supprime une agence non utilisée.
     *
     * @param int $id Identifiant de l'agence.
     *
     * @return Response Redirection vers la liste.
     */
    public function delete(int $id): Response
    {
        try {
            $agency = $this->service->delete($id);
            $this->flash->success(sprintf('L\'agence « %s » a été supprimée.', $agency->name));
        } catch (AgencyInUseException $exception) {
            $this->flash->error($exception->getMessage());
        }

        return new RedirectResponse(self::LIST_URL);
    }

    /**
     * Rend le formulaire d'agence.
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
        return $this->view->render('admin/agencies/form', [
            'pageTitle' => $title,
            'action' => $action,
            'old' => $old,
            'errors' => $errors,
            'maxLength' => AgencyValidator::NAME_MAX_LENGTH,
        ], $errors === [] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
