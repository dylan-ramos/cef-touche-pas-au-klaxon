<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Session\Flash;
use App\Core\Validation\ValidationException;
use App\Core\View;
use App\Security\Auth;
use App\Validator\LoginValidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Connexion et déconnexion.
 */
final class AuthController
{
    /**
     * @param Request        $request   Requête HTTP courante.
     * @param View           $view      Moteur de rendu.
     * @param Auth           $auth      Service d'authentification.
     * @param LoginValidator $validator Validation du formulaire.
     * @param Flash          $flash     Messages après redirection.
     */
    public function __construct(
        private readonly Request $request,
        private readonly View $view,
        private readonly Auth $auth,
        private readonly LoginValidator $validator,
        private readonly Flash $flash,
    ) {
    }

    /**
     * Affiche le formulaire de connexion.
     *
     * @return Response
     */
    public function showLogin(): Response
    {
        return $this->form();
    }

    /**
     * Traite le formulaire de connexion.
     *
     * @return Response Redirection vers la page d'arrivée, ou formulaire en erreur (422).
     */
    public function login(): Response
    {
        try {
            $input = $this->validator->validate($this->request->request->all());
        } catch (ValidationException $exception) {
            return $this->form($exception->errors(), $exception->old());
        }

        $user = $this->auth->attempt($input['email'], $input['password']);
        if ($user === null) {
            return $this->form(['credentials' => 'Adresse e-mail ou mot de passe incorrect.'], ['email' => $input['email']]);
        }

        $this->auth->login($user);
        $this->flash->success(sprintf('Bienvenue %s, vous êtes connecté.', $user->firstName));

        return new RedirectResponse(Auth::homeFor($user));
    }

    /**
     * Déconnecte l'utilisateur.
     *
     * @return Response Redirection vers l'accueil.
     */
    public function logout(): Response
    {
        $this->auth->logout();
        $this->flash->success('Vous êtes déconnecté.');

        return new RedirectResponse('/');
    }

    /**
     * Rend le formulaire de connexion.
     *
     * @param array<string, string> $errors Erreurs par champ.
     * @param array<string, string> $old    Valeurs à réafficher.
     *
     * @return Response
     */
    private function form(array $errors = [], array $old = []): Response
    {
        return $this->view->render(
            'auth/login',
            ['pageTitle' => 'Connexion', 'errors' => $errors, 'old' => $old],
            $errors === [] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
