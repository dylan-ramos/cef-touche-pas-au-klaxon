<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Controller\TripController;
use App\Core\Clock\FrozenClock;
use App\Core\Http\ForbiddenException;
use App\Repository\AgencyRepository;
use App\Repository\TripRepository;
use App\Service\TripService;
use App\Tests\Integration\DatabaseTestCase;
use App\Tests\Integration\ServiceFactory;
use App\Validator\TripValidator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests d'intégration du contrôleur de trajets (parcours employé).
 */
#[CoversClass(TripController::class)]
final class TripControllerTest extends DatabaseTestCase
{
    private const array INPUT = [
        'departure_agency_id' => '1',
        'arrival_agency_id' => '10',
        'departure_at' => '2030-02-01T18:00',
        'arrival_at' => '2030-02-01T20:30',
        'total_seats' => '3',
        'available_seats' => '3',
    ];

    private ServiceFactory $services;

    private TripRepository $trips;

    protected function setUp(): void
    {
        parent::setUp();
        $this->services = new ServiceFactory($this->database);
        $this->services->session->set('_user_id', 1);
        $this->trips = new TripRepository($this->database);
    }

    public function testCreateFormShowsReadOnlyAuthorAndAgencies(): void
    {
        $html = (string) $this->controller()->create()->getContent();

        self::assertStringContainsString('action="/trips"', $html);
        self::assertStringContainsString('value="Martin" disabled', $html);
        self::assertStringContainsString('value="alexandre.martin@email.fr" disabled', $html);
        self::assertSame(2 * 12, substr_count($html, '<option value="') - 2);
        self::assertStringContainsString('min="2030-01-01T12:00"', $html);
    }

    public function testStoreCreatesTripAndRedirectsWithFlash(): void
    {
        $post = self::INPUT + ['author_email' => 'pirate@email.fr', 'auteur_id' => '5'];

        $response = $this->controller($post)->store();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/', $response->getTargetUrl());
        self::assertSame('Le trajet a été créé.', $this->services->flash->consume()[0]['message']);
        $created = $this->trips->findAll()[0];
        self::assertSame('2030-02-01 18:00', $created->departureAt->format('Y-m-d H:i'));
        self::assertSame(1, $created->author->id);
    }

    public function testStoreRedisplaysFormWithErrors(): void
    {
        $response = $this->controller(['arrival_agency_id' => '1'] + self::INPUT)->store();
        $html = (string) $response->getContent();

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('différente de l&apos;agence de départ', $html);
        self::assertStringContainsString('value="2030-02-01T18:00"', $html);
        self::assertSame([], $this->services->flash->consume());
    }

    public function testEditFormIsPrefilledForAuthor(): void
    {
        $id = $this->trips->create(self::tripData(4, 9, '2030-05-01 09:00', '2030-05-01 11:30', 3, 2), 1);

        $html = (string) $this->controller()->edit($id)->getContent();

        self::assertStringContainsString('action="/trips/' . $id . '"', $html);
        self::assertStringContainsString('<option value="4" selected>Toulouse</option>', $html);
        self::assertStringContainsString('<option value="9" selected>Bordeaux</option>', $html);
        self::assertStringContainsString('value="2030-05-01T09:00"', $html);
    }

    public function testUpdateRedirectsWithFlash(): void
    {
        $id = $this->trips->create(self::tripData(), 1);

        $response = $this->controller(self::INPUT)->update($id);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('Le trajet a été modifié.', $this->services->flash->consume()[0]['message']);
        self::assertSame('Lille', $this->trips->findById($id)?->arrivalAgency->name);
    }

    public function testEditingSomeoneElsesTripIsForbidden(): void
    {
        $id = $this->trips->create(self::tripData(), 2);

        $this->expectException(ForbiddenException::class);

        $this->controller()->edit($id);
    }

    public function testDeleteRedirectsWithFlash(): void
    {
        $id = $this->trips->create(self::tripData(), 1);

        $response = $this->controller()->delete($id);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('Le trajet a été supprimé.', $this->services->flash->consume()[0]['message']);
        self::assertNull($this->trips->findById($id));
    }

    /**
     * @param array<string, mixed> $post Corps de la requête.
     */
    private function controller(array $post = []): TripController
    {
        $clock = new FrozenClock(new DateTimeImmutable('2030-01-01 12:00:00'));
        $agencies = new AgencyRepository($this->database);

        return new TripController(
            Request::create('/trips', 'POST', $post),
            $this->services->view,
            $this->services->auth,
            new TripService($this->trips, new TripValidator($agencies, $clock), $clock),
            $agencies,
            $this->services->flash,
            $clock,
        );
    }
}
