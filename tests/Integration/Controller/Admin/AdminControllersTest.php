<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\Admin\AgencyController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\TripController;
use App\Controller\Admin\UserController;
use App\Core\Clock\FrozenClock;
use App\Repository\AgencyRepository;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use App\Service\AgencyService;
use App\Service\TripService;
use App\Tests\Integration\DatabaseTestCase;
use App\Tests\Integration\ServiceFactory;
use App\Validator\AgencyValidator;
use App\Validator\TripValidator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests d'intégration des contrôleurs d'administration (administrateur connecté).
 */
#[CoversClass(DashboardController::class)]
#[CoversClass(UserController::class)]
#[CoversClass(AgencyController::class)]
#[CoversClass(TripController::class)]
final class AdminControllersTest extends DatabaseTestCase
{
    private ServiceFactory $services;

    private AgencyRepository $agencies;

    private TripRepository $trips;

    private FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->services = new ServiceFactory($this->database);
        $this->services->session->set('_user_id', 21);
        $this->agencies = new AgencyRepository($this->database);
        $this->trips = new TripRepository($this->database);
        $this->clock = new FrozenClock(new DateTimeImmutable('2030-01-01 12:00:00'));
    }

    public function testDashboardShowsCounters(): void
    {
        $this->trips->create(self::tripData(), 1);
        $controller = new DashboardController(
            $this->services->view,
            new UserRepository($this->database),
            $this->agencies,
            $this->trips,
            $this->clock,
        );

        $html = (string) $controller->index()->getContent();

        self::assertMatchesRegularExpression('#Utilisateurs</h2>\s*<p class="display-6 mb-0">21</p>#', $html);
        self::assertMatchesRegularExpression('#Agences</h2>\s*<p class="display-6 mb-0">12</p>#', $html);
        self::assertMatchesRegularExpression('#Trajets</h2>\s*<p class="display-6 mb-0">1</p>#', $html);
    }

    public function testUserListShowsEveryUserWithoutActions(): void
    {
        $html = (string) (new UserController($this->services->view, new UserRepository($this->database)))->index()->getContent();

        self::assertSame(21, substr_count($html, 'href="mailto:'));
        self::assertStringContainsString('Lefèvre', $html);
        self::assertStringContainsString('Administrateur</td>', $html);
        self::assertStringNotContainsString('/delete', $html);
    }

    public function testAgencyListOffersEditionAndDeletion(): void
    {
        $html = (string) $this->agencyController()->index()->getContent();

        self::assertSame(12, substr_count($html, '/edit"'));
        self::assertSame(12, substr_count($html, '/delete"'));
        self::assertStringContainsString('href="/admin/agencies/create"', $html);
    }

    public function testAgencyCreationRedirectsToListWithFlash(): void
    {
        $response = $this->agencyController(['name' => 'Dijon'])->store();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/agencies', $response->getTargetUrl());
        self::assertSame('L\'agence « Dijon » a été créée.', $this->services->flash->consume()[0]['message']);
        self::assertTrue($this->agencies->existsByName('Dijon'));
    }

    public function testAgencyCreationErrorRedisplaysForm(): void
    {
        $response = $this->agencyController(['name' => 'Paris'])->store();

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('Une agence porte déjà ce nom.', (string) $response->getContent());
        self::assertStringContainsString('value="Paris"', (string) $response->getContent());
    }

    public function testAgencyEditAndUpdate(): void
    {
        self::assertStringContainsString('value="Reims"', (string) $this->agencyController()->edit(12)->getContent());

        $response = $this->agencyController(['name' => 'Reims Centre'])->update(12);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('L\'agence « Reims Centre » a été modifiée.', $this->services->flash->consume()[0]['message']);
    }

    public function testAgencyUpdateErrorRedisplaysForm(): void
    {
        $response = $this->agencyController(['name' => ''])->update(12);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('action="/admin/agencies/12"', (string) $response->getContent());
    }

    public function testDeletingUsedAgencyRedirectsWithErrorFlash(): void
    {
        $response = $this->agencyController()->delete(1);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/agencies', $response->getTargetUrl());
        $message = $this->services->flash->consume()[0];
        self::assertSame('danger', $message['type']);
        self::assertStringContainsString('ne peut pas être supprimée', $message['message']);
    }

    public function testDeletingUnusedAgencyRedirectsWithSuccessFlash(): void
    {
        $id = $this->agencies->create('Dijon');

        $this->agencyController()->delete($id);

        self::assertSame('success', $this->services->flash->consume()[0]['type']);
        self::assertNull($this->agencies->findById($id));
    }

    public function testTripListShowsAllTripsWithStatus(): void
    {
        $html = (string) $this->tripController()->index()->getContent();

        self::assertSame(16, substr_count($html, 'action="/admin/trips/'));
        self::assertStringContainsString('Passé', $html);
    }

    public function testAdministratorDeletesTripAndReturnsToAdminList(): void
    {
        $id = $this->trips->create(self::tripData(), 3);

        $response = $this->tripController()->delete($id);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/trips', $response->getTargetUrl());
        self::assertSame('Le trajet a été supprimé.', $this->services->flash->consume()[0]['message']);
        self::assertNull($this->trips->findById($id));
    }

    /**
     * @param array<string, mixed> $post Corps de la requête.
     */
    private function agencyController(array $post = []): AgencyController
    {
        return new AgencyController(
            Request::create('/admin/agencies', 'POST', $post),
            $this->services->view,
            $this->agencies,
            new AgencyService($this->agencies, new AgencyValidator($this->agencies)),
            $this->services->flash,
        );
    }

    private function tripController(): TripController
    {
        return new TripController(
            $this->services->view,
            $this->trips,
            new TripService($this->trips, new TripValidator($this->agencies, $this->clock), $this->clock),
            $this->services->auth,
            $this->services->flash,
            $this->clock,
        );
    }
}
