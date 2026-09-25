<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Security\Throttle\LoginThrottle;
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
     * @param LoginThrottle  $throttle  Limitation des tentatives de connexion.
     */
    public function __construct(
        private readonly Request $request,
        private readonly View $view,
        private readonly Auth $auth,
        private readonly LoginValidator $validator,
        private readonly Flash $flash,
        private readonly LoginThrottle $throttle,
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
     * @return Response Redirection vers la page d'arrivée, formulaire en erreur (422)
     *                  ou tentatives bloquées (429).
     */
    public function login(): Response
    {
        try {
            $input = $this->validator->validate($this->request->request->all());
        } catch (ValidationException $exception) {
            return $this->form($exception->errors(), $exception->old());
        }

        $throttleKey = LoginThrottle::key($input['email'], (string) $this->request->getClientIp());
        $wait = $this->throttle->secondsUntilAvailable($throttleKey);
        if ($wait > 0) {
            $minutes = (int) ceil($wait / 60);
            $message = sprintf(
                'Trop de tentatives de connexion. Veuillez réessayer dans %d minute%s.',
                $minutes,
                $minutes > 1 ? 's' : '',
            );

            return $this->form(['credentials' => $message], ['email' => $input['email']], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $user = $this->auth->attempt($input['email'], $input['password']);
        if ($user === null) {
            $this->throttle->hit($throttleKey);

            return $this->form(['credentials' => 'Adresse e-mail ou mot de passe incorrect.'], ['email' => $input['email']]);
        }

        $this->throttle->clear($throttleKey);
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
     * @param int|null              $status Code HTTP ; 200 sans erreur, 422 avec erreurs par défaut.
     *
     * @return Response
     */
    private function form(array $errors = [], array $old = [], ?int $status = null): Response
    {
        return $this->view->render(
            'auth/login',
            ['pageTitle' => 'Connexion', 'errors' => $errors, 'old' => $old],
            $status ?? ($errors === [] ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY),
        );
    }
}
