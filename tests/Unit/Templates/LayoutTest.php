<?php

declare(strict_types=1);

namespace App\Tests\Unit\Templates;

use App\Core\Session\ArraySession;
use App\Core\Session\Flash;
use App\Core\View;
use App\Entity\Role;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests du rendu de la mise en page (en-tête, messages flash, pied de page).
 */
#[CoversNothing]
final class LayoutTest extends TestCase
{
    private Flash $flash;

    private View $view;

    protected function setUp(): void
    {
        $this->flash = new Flash(new ArraySession());
        $this->view = new View(dirname(__DIR__, 3) . '/templates');
        $this->view->share('appName', 'Touche pas au klaxon');
        $this->view->share('copyrightHolder', 'Touche pas au klaxon');
        $this->view->share('currentYear', 2026);
        $this->view->share('flash', $this->flash);
    }

    public function testVisitorHeaderOffersLoginOnly(): void
    {
        $html = $this->render(null);

        self::assertStringContainsString('href="/login"', $html);
        self::assertStringNotContainsString('Créer un trajet', $html);
        self::assertStringNotContainsString('Déconnexion', $html);
        self::assertStringContainsString('class="navbar-brand app-brand" href="/"', $html);
    }

    public function testEmployeeHeaderOffersTripCreationIdentityAndLogout(): void
    {
        $html = $this->render(self::user(Role::User));

        self::assertStringContainsString('href="/trips/create"', $html);
        self::assertStringContainsString('Bonjour Chloé Roux', $html);
        self::assertStringContainsString('action="/logout"', $html);
        self::assertStringNotContainsString('/admin/', $html);
        self::assertStringNotContainsString('href="/login"', $html);
    }

    public function testAdministratorHeaderOffersDashboardMenu(): void
    {
        $html = $this->render(self::user(Role::Admin));

        self::assertStringContainsString('class="navbar-brand app-brand" href="/admin"', $html);
        foreach (['/admin/users', '/admin/agencies', '/admin/trips'] as $link) {
            self::assertStringContainsString('href="' . $link . '"', $html);
        }
        self::assertStringContainsString('action="/logout"', $html);
        self::assertStringNotContainsString('href="/trips/create"', $html);
    }

    public function testFlashMessagesAreDisplayedEscapedThenConsumed(): void
    {
        $this->flash->success('Le trajet <b>a été</b> modifié.');

        $html = $this->render(null);

        self::assertStringContainsString('alert alert-success', $html);
        self::assertStringContainsString('Le trajet &lt;b&gt;a été&lt;/b&gt; modifié.', $html);
        self::assertStringNotContainsString('alert-success', $this->render(null));
    }

    public function testFooterShowsApplicationNameAndCopyright(): void
    {
        self::assertStringContainsString('&copy; 2026 Touche pas au klaxon', $this->render(null));
    }

    private function render(?User $user): string
    {
        $this->view->share('currentUser', $user);

        return (string) $this->view->render('home/index')->getContent();
    }

    private static function user(Role $role): User
    {
        return new User(7, 'Roux', 'Chloé', '0633221199', 'chloe.roux@email.fr', $role);
    }
}
