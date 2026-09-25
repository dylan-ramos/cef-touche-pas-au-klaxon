<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Controller\AuthController;
use App\Core\Clock\FrozenClock;
use App\Core\Security\Throttle\ArrayThrottleStore;
use App\Core\Security\Throttle\LoginThrottle;
use App\Tests\Integration\DatabaseTestCase;
use App\Tests\Integration\ServiceFactory;
use App\Validator\LoginValidator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests d'intégration du contrôleur de connexion.
 */
#[CoversClass(AuthController::class)]
final class AuthControllerTest extends DatabaseTestCase
{
    private ServiceFactory $services;

    private ArrayThrottleStore $throttleStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->services = new ServiceFactory($this->database);
        $this->throttleStore = new ArrayThrottleStore();
    }

    public function testRepeatedFailuresBlockFurtherAttemptsEvenWithRightPassword(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $failure = $this->controller(['email' => 'sophie.dubois@email.fr', 'password' => 'faux'])->login();
            self::assertSame(422, $failure->getStatusCode());
        }

        $response = $this->controller(['email' => 'sophie.dubois@email.fr', 'password' => 'Covoiturage#2026'])->login();

        self::assertSame(429, $response->getStatusCode());
        self::assertStringContainsString('Veuillez réessayer dans 15 minutes.', (string) $response->getContent());
        self::assertNull($this->services->auth->user());
    }

    public function testSuccessfulLoginResetsFailureCounter(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->controller(['email' => 'sophie.dubois@email.fr', 'password' => 'faux'])->login();
        }

        $this->controller(['email' => 'sophie.dubois@email.fr', 'password' => 'Covoiturage#2026'])->login();

        self::assertNull($this->throttleStore->get(LoginThrottle::key('sophie.dubois@email.fr', '127.0.0.1')));
    }

    public function testLoginFormIsDisplayed(): void
    {
        $response = $this->controller()->showLogin();
        $html = (string) $response->getContent();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('action="/login"', $html);
        self::assertStringContainsString('name="_csrf" value="' . $this->services->csrf->token() . '"', $html);
    }

    public function testEmployeeLoginRedirectsHomeWithWelcomeMessage(): void
    {
        $response = $this->controller(['email' => 'Alexandre.Martin@email.fr', 'password' => 'Covoiturage#2026'])->login();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/', $response->getTargetUrl());
        self::assertSame(1, $this->services->auth->user()?->id);
        self::assertSame('Bienvenue Alexandre, vous êtes connecté.', $this->services->flash->consume()[0]['message']);
    }

    public function testAdministratorLoginRedirectsToDashboard(): void
    {
        $response = $this->controller(['email' => 'admin@touche-pas-au-klaxon.fr', 'password' => 'Admin#Klaxon2026'])->login();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin', $response->getTargetUrl());
    }

    public function testWrongPasswordShowsGenericErrorAndKeepsEmail(): void
    {
        $response = $this->controller(['email' => 'alexandre.martin@email.fr', 'password' => 'erreur'])->login();
        $html = (string) $response->getContent();

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('Adresse e-mail ou mot de passe incorrect.', $html);
        self::assertStringContainsString('value="alexandre.martin@email.fr"', $html);
        self::assertStringNotContainsString('erreur"', $html);
        self::assertNull($this->services->auth->user());
    }

    public function testUnknownEmailShowsSameGenericError(): void
    {
        $response = $this->controller(['email' => 'personne@email.fr', 'password' => 'Covoiturage#2026'])->login();

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('Adresse e-mail ou mot de passe incorrect.', (string) $response->getContent());
    }

    public function testInvalidInputShowsFieldErrors(): void
    {
        $response = $this->controller(['email' => 'pas-un-email'])->login();
        $html = (string) $response->getContent();

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('L&apos;adresse e-mail n&apos;est pas valide.', $html);
        self::assertStringContainsString('Le mot de passe est obligatoire.', $html);
    }

    public function testLogoutRedirectsHome(): void
    {
        $this->services->session->set('_user_id', 1);

        $response = $this->controller()->logout();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/', $response->getTargetUrl());
        self::assertNull($this->services->auth->user());
    }

    /**
     * @param array<string, mixed> $post Corps de la requête.
     */
    private function controller(array $post = []): AuthController
    {
        return new AuthController(
            Request::create('/login', 'POST', $post),
            $this->services->view,
            $this->services->auth,
            new LoginValidator(),
            $this->services->flash,
            new LoginThrottle($this->throttleStore, new FrozenClock(new DateTimeImmutable('2030-01-01 12:00:00'))),
        );
    }
}
