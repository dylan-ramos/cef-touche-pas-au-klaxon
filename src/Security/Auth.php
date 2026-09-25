<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Http\ForbiddenException;
use App\Core\Security\Csrf;
use App\Core\Session\Flash;
use App\Core\Session\Session;
use App\Entity\User;
use App\Repository\UserRepository;
use Closure;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentification des utilisateurs et contrôle d'accès aux routes.
 *
 * Seul l'identifiant de l'utilisateur est conservé en session ; ses données
 * sont relues en base à chaque requête, ce qui prend en compte immédiatement
 * une modification du référentiel RH.
 */
final class Auth
{
    private const string SESSION_KEY = '_user_id';

    /**
     * Empreinte factice vérifiée lorsque l'e-mail est inconnu, pour que la
     * durée de réponse ne révèle pas l'existence d'un compte.
     */
    private const string DUMMY_HASH = '$2y$12$.2QJ5GRCpS4ZTwl1tF/nxeUpaBRK0eNGnQVqDF4XS0sOh..DGGznu';

    /**
     * @var User|null Utilisateur connecté, une fois lu en base.
     */
    private ?User $user = null;

    /**
     * @var bool Indique si l'utilisateur de la session a déjà été recherché.
     */
    private bool $resolved = false;

    /**
     * @param Session        $session Session du visiteur.
     * @param UserRepository $users   Référentiel des utilisateurs.
     * @param Csrf           $csrf    Jetons de formulaire, renouvelés à la connexion.
     * @param Flash          $flash   Messages affichés après redirection.
     */
    public function __construct(
        private readonly Session $session,
        private readonly UserRepository $users,
        private readonly Csrf $csrf,
        private readonly Flash $flash,
    ) {
    }

    /**
     * Vérifie un couple e-mail / mot de passe.
     *
     * @param string $email    Adresse e-mail saisie.
     * @param string $password Mot de passe saisi.
     *
     * @return User|null L'utilisateur si les identifiants sont corrects.
     */
    public function attempt(string $email, string $password): ?User
    {
        $credentials = $this->users->findCredentialsByEmail($email);
        $valid = password_verify($password, $credentials->passwordHash ?? self::DUMMY_HASH);

        return $valid && $credentials !== null ? $credentials->user : null;
    }

    /**
     * Ouvre la session de l'utilisateur.
     *
     * L'identifiant de session et le jeton CSRF sont renouvelés pour empêcher
     * la fixation de session.
     *
     * @param User $user Utilisateur authentifié.
     *
     * @return void
     */
    public function login(User $user): void
    {
        $this->session->regenerate();
        $this->session->set(self::SESSION_KEY, $user->id);
        $this->csrf->regenerate();
        $this->user = $user;
        $this->resolved = true;
    }

    /**
     * Ferme la session de l'utilisateur et efface toutes ses données.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->session->destroy();
        $this->user = null;
        $this->resolved = true;
    }

    /**
     * Utilisateur connecté, ou null pour un visiteur.
     *
     * @return User|null
     */
    public function user(): ?User
    {
        if (!$this->resolved) {
            $this->resolved = true;
            $id = $this->session->get(self::SESSION_KEY);
            $this->user = is_int($id) ? $this->users->findById($id) : null;

            if ($this->user === null && $id !== null) {
                $this->session->remove(self::SESSION_KEY);
            }
        }

        return $this->user;
    }

    /**
     * Garde : route réservée aux visiteurs (formulaire de connexion).
     *
     * @return Closure(): ?Response Redirige un utilisateur déjà connecté.
     */
    public function guardGuest(): Closure
    {
        return function (): ?Response {
            $user = $this->user();

            return $user === null ? null : new RedirectResponse(self::homeFor($user));
        };
    }

    /**
     * Garde : route réservée aux utilisateurs connectés.
     *
     * @return Closure(): ?Response Redirige un visiteur vers le formulaire de connexion.
     */
    public function guardUser(): Closure
    {
        return function (): ?Response {
            if ($this->user() !== null) {
                return null;
            }
            $this->flash->info('Veuillez vous connecter pour accéder à cette page.');

            return new RedirectResponse('/login');
        };
    }

    /**
     * Garde : route réservée aux administrateurs.
     *
     * @return Closure(): ?Response Redirige un visiteur ; refuse un employé.
     *
     * @throws ForbiddenException Si l'utilisateur connecté n'est pas administrateur.
     */
    public function guardAdmin(): Closure
    {
        $guardUser = $this->guardUser();

        return function () use ($guardUser): ?Response {
            $redirect = $guardUser();
            if ($redirect !== null) {
                return $redirect;
            }

            if ($this->user()?->isAdmin() !== true) {
                throw new ForbiddenException();
            }

            return null;
        };
    }

    /**
     * Page d'arrivée d'un utilisateur connecté.
     *
     * @param User $user Utilisateur connecté.
     *
     * @return string Chemin de la page.
     */
    public static function homeFor(User $user): string
    {
        return $user->isAdmin() ? '/admin' : '/';
    }
}
