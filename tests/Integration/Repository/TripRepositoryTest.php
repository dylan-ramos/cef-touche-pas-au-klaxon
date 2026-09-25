<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Core\Data\Exception\CheckConstraintViolationException;
use App\Core\Data\Exception\ForeignKeyConstraintViolationException;
use App\Repository\TripRepository;
use App\Tests\Integration\DatabaseTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests d'intégration du dépôt des trajets.
 *
 * Les trajets du jeu d'essais sont datés autour de la date d'import : les
 * tests de la liste d'accueil se placent en 2030 pour ne voir que leurs
 * propres trajets.
 */
#[CoversClass(TripRepository::class)]
final class TripRepositoryTest extends DatabaseTestCase
{
    private TripRepository $trips;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trips = new TripRepository($this->database);
    }

    public function testSeedUpcomingListExcludesPastAndFullTrips(): void
    {
        $upcoming = $this->trips->findUpcomingAvailable(new DateTimeImmutable());

        self::assertCount(12, $upcoming);
        foreach ($upcoming as $trip) {
            self::assertFalse($trip->isFull());
            self::assertNotContains($trip->id, [8, 13, 14, 15]);
        }
    }

    public function testUpcomingListIsFilteredAndSortedByDeparture(): void
    {
        $now = new DateTimeImmutable('2030-01-01 12:00:00');
        $later = $this->trips->create(self::tripData(departureAt: '2030-03-01 08:00', arrivalAt: '2030-03-01 10:00'), 2);
        $sooner = $this->trips->create(self::tripData(departureAt: '2030-02-01 08:00', arrivalAt: '2030-02-01 10:00'), 3);
        $full = self::tripData(departureAt: '2030-01-15 08:00', arrivalAt: '2030-01-15 10:00', availableSeats: 0);
        $this->trips->create($full, 4);
        $this->trips->create(self::tripData(departureAt: '2030-01-01 12:00', arrivalAt: '2030-01-01 14:00'), 5);

        $ids = array_map(static fn ($trip): int => $trip->id, $this->trips->findUpcomingAvailable($now));

        self::assertSame([$sooner, $later], $ids);
        self::assertSame(3, $this->trips->countUpcoming($now));
    }

    public function testCreateAndFindByIdHydratesAgenciesAndAuthor(): void
    {
        $id = $this->trips->create(self::tripData(departureAgencyId: 7, arrivalAgencyId: 12), 5);

        $trip = $this->trips->findById($id);

        self::assertNotNull($trip);
        self::assertSame('Strasbourg', $trip->departureAgency->name);
        self::assertSame('Reims', $trip->arrivalAgency->name);
        self::assertSame('2030-06-01 08:00', $trip->departureAt->format('Y-m-d H:i'));
        self::assertSame('2030-06-01 12:00', $trip->arrivalAt->format('Y-m-d H:i'));
        self::assertSame(4, $trip->totalSeats);
        self::assertSame(3, $trip->availableSeats);
        self::assertSame('Lucie Lefèvre', $trip->author->fullName());
        self::assertSame('lucie.lefevre@email.fr', $trip->author->email);
        self::assertSame('0777889900', $trip->author->phone);
    }

    public function testCreateRejectsUnknownAgency(): void
    {
        $this->expectException(ForeignKeyConstraintViolationException::class);

        $this->trips->create(self::tripData(arrivalAgencyId: 999), 1);
    }

    public function testCreateRejectsUnknownAuthor(): void
    {
        $this->expectException(ForeignKeyConstraintViolationException::class);

        $this->trips->create(self::tripData(), 999);
    }

    public function testCreateRejectsIncoherentTripThroughDatabaseConstraints(): void
    {
        $this->expectException(CheckConstraintViolationException::class);

        $this->trips->create(self::tripData(departureAgencyId: 3, arrivalAgencyId: 3), 1);
    }

    public function testUpdateChangesTripButKeepsAuthor(): void
    {
        $id = $this->trips->create(self::tripData(), 1);

        $updated = $this->trips->update($id, self::tripData(
            departureAgencyId: 10,
            arrivalAgencyId: 9,
            departureAt: '2030-07-14 09:30',
            arrivalAt: '2030-07-14 17:00',
            totalSeats: 2,
            availableSeats: 0,
        ));

        $trip = $this->trips->findById($id);
        self::assertTrue($updated);
        self::assertNotNull($trip);
        self::assertSame('Lille', $trip->departureAgency->name);
        self::assertSame('Bordeaux', $trip->arrivalAgency->name);
        self::assertSame('2030-07-14 09:30', $trip->departureAt->format('Y-m-d H:i'));
        self::assertSame(2, $trip->totalSeats);
        self::assertTrue($trip->isFull());
        self::assertSame(1, $trip->author->id);
    }

    public function testUpdateWithoutChangeStillReportsSuccess(): void
    {
        $id = $this->trips->create(self::tripData(), 1);

        self::assertTrue($this->trips->update($id, self::tripData()));
    }

    public function testUpdateUnknownTripReturnsFalse(): void
    {
        self::assertFalse($this->trips->update(999, self::tripData()));
    }

    public function testUpdateRejectsArrivalBeforeDeparture(): void
    {
        $id = $this->trips->create(self::tripData(), 1);

        $this->expectException(CheckConstraintViolationException::class);

        $this->trips->update($id, self::tripData(departureAt: '2030-06-01 12:00', arrivalAt: '2030-06-01 08:00'));
    }

    public function testDeleteRemovesTrip(): void
    {
        $id = $this->trips->create(self::tripData(), 1);

        self::assertTrue($this->trips->delete($id));
        self::assertNull($this->trips->findById($id));
        self::assertFalse($this->trips->delete($id));
    }

    public function testFindAllListsEveryTripMostRecentFirst(): void
    {
        $all = $this->trips->findAll();

        self::assertCount(16, $all);
        self::assertSame(16, $all[0]->id);
        self::assertSame(15, $all[15]->id);
    }
}
