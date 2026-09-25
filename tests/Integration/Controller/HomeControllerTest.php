<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Controller\HomeController;
use App\Core\Clock\FrozenClock;
use App\Repository\TripRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Tests\Integration\ServiceFactory;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests d'intégration de la page d'accueil.
 *
 * L'horloge est figée en 2030 et les trajets affichés sont créés par le
 * test : le jeu d'essais, daté autour de la date d'import, est alors passé.
 */
#[CoversClass(HomeController::class)]
final class HomeControllerTest extends DatabaseTestCase
{
    private ServiceFactory $services;

    private TripRepository $trips;

    private int $martinTrip;

    private int $duboisTrip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->services = new ServiceFactory($this->database);
        $this->trips = new TripRepository($this->database);
        $lyonMarseille = self::tripData(2, 3, '2030-06-02 07:30', '2030-06-02 11:00');
        $parisLyon = self::tripData(1, 2, '2030-06-01 08:00', '2030-06-01 12:30');
        $full = self::tripData(1, 2, '2030-06-03 08:00', '2030-06-03 09:00', availableSeats: 0);

        $this->duboisTrip = $this->trips->create($lyonMarseille, 2);
        $this->martinTrip = $this->trips->create($parisLyon, 1);
        $this->trips->create($full, 3);
    }

    public function testVisitorSeesTripsWithoutPersonalDataOrActions(): void
    {
        $html = $this->render();

        self::assertStringContainsString('veuillez vous connecter', $html);
        self::assertStringContainsString('<td>01/06/2030</td>', $html);
        self::assertStringContainsString('<td>08:00</td>', $html);
        self::assertStringContainsString('<td>12:30</td>', $html);
        self::assertSame(2, substr_count($html, '<tr>') - 1);
        self::assertStringNotContainsString('0612345678', $html);
        self::assertStringNotContainsString('@email.fr', $html);
        self::assertStringNotContainsString('data-bs-toggle="modal"', $html);
        self::assertStringNotContainsString('/delete', $html);
    }

    public function testTripsAreSortedByDeparture(): void
    {
        $html = $this->render();

        self::assertLessThan(strpos($html, '02/06/2030'), strpos($html, '01/06/2030'));
    }

    public function testConnectedUserSeesDetailsModalWithAuthorContact(): void
    {
        $html = $this->render(2);

        self::assertStringContainsString('Trajets proposés', $html);
        self::assertStringContainsString('data-bs-target="#trip-' . $this->martinTrip . '"', $html);
        self::assertStringContainsString('id="trip-' . $this->martinTrip . '"', $html);
        self::assertStringContainsString('Alexandre Martin', $html);
        self::assertStringContainsString("06\u{00A0}12\u{00A0}34\u{00A0}56\u{00A0}78", $html);
        self::assertStringContainsString('mailto:alexandre.martin@email.fr', $html);
        self::assertStringContainsString('Nombre total de places :</dt> <dd class="d-inline">4</dd>', $html);
    }

    public function testAuthorActionsAreShownOnlyOnOwnTrips(): void
    {
        $html = $this->render(2);

        self::assertStringContainsString('href="/trips/' . $this->duboisTrip . '/edit"', $html);
        self::assertStringContainsString('action="/trips/' . $this->duboisTrip . '/delete"', $html);
        self::assertStringNotContainsString('href="/trips/' . $this->martinTrip . '/edit"', $html);
        self::assertStringNotContainsString('action="/trips/' . $this->martinTrip . '/delete"', $html);
        self::assertStringContainsString('name="_csrf" value="' . $this->services->csrf->token() . '"', $html);
    }

    public function testEmptyStateWhenNoTripIsAvailable(): void
    {
        $html = $this->render(null, '2031-01-01 00:00:00');

        self::assertStringContainsString('Aucun trajet disponible pour le moment.', $html);
        self::assertStringNotContainsString('<table', $html);
    }

    private function render(?int $userId = null, string $now = '2030-01-01 00:00:00'): string
    {
        if ($userId !== null) {
            $this->services->session->set('_user_id', $userId);
        }

        $controller = new HomeController(
            $this->services->view,
            $this->trips,
            $this->services->auth,
            new FrozenClock(new DateTimeImmutable($now)),
        );

        return (string) $controller->index()->getContent();
    }
}
