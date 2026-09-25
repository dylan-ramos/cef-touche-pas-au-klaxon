<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use App\Core\Http\ForbiddenException;
use App\Security\Auth;
use App\Tests\Integration\DatabaseTestCase;
use App\Tests\Integration\ServiceFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Tests d'intégration de l'authentification et des gardes d'accès.
 */
#[CoversClass(Auth::class)]
final class AuthTest extends DatabaseTestCase
{
    private ServiceFactory $services;

    protected function setUp(): void
    {
        parent::setUp();
        $this->services = new ServiceFactory($this->database);
    }

    public function testAttemptAcceptsValidCredentials(): void
    {
        $user = $this->services->auth->attempt('sophie.dubois@email.fr', 'Covoiturage#2026');

        self::assertSame(2, $user?->id);
    }

    public function testAttemptRejectsWrongPasswordAndUnknownEmail(): void
    {
        self::assertNull($this->services->auth->attempt('sophie.dubois@email.fr', 'mauvais'));
        self::assertNull($this->services->auth->attempt('inconnu@email.fr', 'Covoiturage#2026'));
    }

    public function testLoginStoresOnlyIdentifierAndRenewsSessionAndCsrfToken(): void
    {
        $auth = $this->services->auth;
        $oldToken = $this->services->csrf->token();
        $user = $auth->attempt('sophie.dubois@email.fr', 'Covoiturage#2026');
        self::assertNotNull($user);

        $auth->login($user);

        self::assertSame(2, $this->services->session->get('_user_id'));
        self::assertSame(1, $this->services->session->regenerations());
        self::assertFalse($this->services->csrf->isValid($oldToken));
        self::assertSame($user, $auth->user());
    }

    public function testUserIsReloadedFromSessionOnNextRequest(): void
    {
        $this->services->session->set('_user_id', 5);

        self::assertSame('Lucie Lefèvre', $this->services->auth->user()?->fullName());
    }

    public function testUnknownUserInSessionIsDiscarded(): void
    {
        $this->services->session->set('_user_id', 999);

        self::assertNull($this->services->auth->user());
        self::assertFalse($this->services->session->has('_user_id'));
    }

    public function testLogoutClearsSession(): void
    {
        $this->services->session->set('_user_id', 5);
        $this->services->auth->logout();

        self::assertNull($this->services->auth->user());
        self::assertFalse($this->services->session->has('_user_id'));
    }

    public function testGuestGuardRedirectsConnectedUsersToTheirHome(): void
    {
        self::assertNull(($this->services->auth->guardGuest())());

        $admin = new ServiceFactory($this->database);
        $admin->session->set('_user_id', 21);
        $response = ($admin->auth->guardGuest())();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin', $response->getTargetUrl());
    }

    public function testUserGuardRedirectsVisitorsToLogin(): void
    {
        $response = ($this->services->auth->guardUser())();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/login', $response->getTargetUrl());
        self::assertSame('info', $this->services->flash->consume()[0]['type']);

        $connected = new ServiceFactory($this->database);
        $connected->session->set('_user_id', 1);
        self::assertNull(($connected->auth->guardUser())());
    }

    public function testAdminGuardRedirectsVisitorsAndRefusesEmployees(): void
    {
        self::assertInstanceOf(RedirectResponse::class, ($this->services->auth->guardAdmin())());

        $employee = new ServiceFactory($this->database);
        $employee->session->set('_user_id', 1);
        try {
            ($employee->auth->guardAdmin())();
            self::fail('Un employé ne doit pas accéder à l\'administration.');
        } catch (ForbiddenException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }

        $admin = new ServiceFactory($this->database);
        $admin->session->set('_user_id', 21);
        self::assertNull(($admin->auth->guardAdmin())());
    }
}
