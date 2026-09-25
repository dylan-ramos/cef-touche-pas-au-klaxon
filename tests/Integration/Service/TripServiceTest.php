<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Core\Clock\FrozenClock;
use App\Core\Http\ForbiddenException;
use App\Core\Http\NotFoundException;
use App\Core\Validation\ValidationException;
use App\Entity\User;
use App\Repository\AgencyRepository;
use App\Repository\TripRepository;
use App\Repository\UserRepository;
use App\Service\TripService;
use App\Tests\Integration\DatabaseTestCase;
use App\Validator\TripValidator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests d'intégration des écritures de trajets et des droits associés.
 */
#[CoversClass(TripService::class)]
final class TripServiceTest extends DatabaseTestCase
{
    private const array INPUT = [
        'departure_agency_id' => '7',
        'arrival_agency_id' => '12',
        'departure_at' => '2030-03-10T07:15',
        'arrival_at' => '2030-03-10T10:45',
        'total_seats' => '3',
        'available_seats' => '2',
    ];

    private TripService $service;

    private TripRepository $trips;

    private User $martin;

    private User $dubois;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $clock = new FrozenClock(new DateTimeImmutable('2030-01-01 12:00:00'));
        $this->trips = new TripRepository($this->database);
        $this->service = new TripService(
            $this->trips,
            new TripValidator(new AgencyRepository($this->database), $clock),
            $clock,
        );
        $users = new UserRepository($this->database);
        $this->martin = $users->findById(1) ?? self::fail('Utilisateur 1 absent du jeu d\'essais.');
        $this->dubois = $users->findById(2) ?? self::fail('Utilisateur 2 absent du jeu d\'essais.');
        $this->admin = $users->findById(21) ?? self::fail('Administrateur absent du jeu d\'essais.');
    }

    public function testCreatePersistsTripWithAuthor(): void
    {
        $id = $this->service->create($this->martin, self::INPUT);

        $trip = $this->trips->findById($id);
        self::assertNotNull($trip);
        self::assertSame('Strasbourg', $trip->departureAgency->name);
        self::assertSame('Reims', $trip->arrivalAgency->name);
        self::assertSame('2030-03-10 07:15', $trip->departureAt->format('Y-m-d H:i'));
        self::assertSame(3, $trip->totalSeats);
        self::assertSame(2, $trip->availableSeats);
        self::assertSame($this->martin->id, $trip->author->id);
    }

    public function testCreateWithInvalidInputWritesNothing(): void
    {
        $before = count($this->trips->findAll());

        try {
            $this->service->create($this->martin, ['arrival_agency_id' => '7'] + self::INPUT);
            self::fail('Une exception de validation était attendue.');
        } catch (ValidationException) {
            self::assertCount($before, $this->trips->findAll());
        }
    }

    public function testAuthorUpdatesTrip(): void
    {
        $id = $this->service->create($this->martin, self::INPUT);

        $this->service->update($this->martin, $id, ['available_seats' => '0', 'arrival_agency_id' => '1'] + self::INPUT);

        $trip = $this->trips->findById($id);
        self::assertNotNull($trip);
        self::assertSame(0, $trip->availableSeats);
        self::assertSame('Paris', $trip->arrivalAgency->name);
        self::assertSame($this->martin->id, $trip->author->id);
    }

    public function testNonAuthorCannotEditOrUpdate(): void
    {
        $id = $this->service->create($this->martin, self::INPUT);

        foreach ([$this->dubois, $this->admin] as $actor) {
            try {
                $this->service->update($actor, $id, ['available_seats' => '0'] + self::INPUT);
                self::fail('La modification par un non-auteur doit être refusée.');
            } catch (ForbiddenException) {
                self::assertSame(2, $this->trips->findById($id)?->availableSeats);
            }
        }

        $this->expectException(ForbiddenException::class);
        $this->service->findEditable($this->dubois, $id);
    }

    public function testDepartedTripCannotBeEdited(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('déjà parti');

        // Trajet 15 du jeu d'essais : passé, proposé par Alexandre Martin.
        $this->service->findEditable($this->martin, 15);
    }

    public function testUpdateUnknownTripIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update($this->martin, 999, self::INPUT);
    }

    public function testUpdateWithInvalidInputKeepsTripUnchanged(): void
    {
        $id = $this->service->create($this->martin, self::INPUT);

        try {
            $this->service->update($this->martin, $id, ['total_seats' => '1'] + self::INPUT);
            self::fail('Une exception de validation était attendue.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('available_seats', $exception->errors());
            self::assertSame(3, $this->trips->findById($id)?->totalSeats);
        }
    }

    public function testAuthorDeletesTrip(): void
    {
        $id = $this->service->create($this->martin, self::INPUT);

        $this->service->delete($this->martin, $id);

        self::assertNull($this->trips->findById($id));
    }

    public function testAdministratorDeletesAnyTrip(): void
    {
        $id = $this->service->create($this->martin, self::INPUT);

        $this->service->delete($this->admin, $id);

        self::assertNull($this->trips->findById($id));
    }

    public function testOtherEmployeeCannotDelete(): void
    {
        $id = $this->service->create($this->martin, self::INPUT);

        try {
            $this->service->delete($this->dubois, $id);
            self::fail('La suppression par un autre employé doit être refusée.');
        } catch (ForbiddenException) {
            self::assertNotNull($this->trips->findById($id));
        }
    }

    public function testAgencyDeletedDuringInputIsReportedAsValidationError(): void
    {
        $other = self::concurrentConnection();
        $other->pdo()->exec("INSERT INTO agence (nom) VALUES ('Zzz Concurrence')");
        $agencyId = (int) $other->pdo()->lastInsertId();
        $clock = new FrozenClock(new DateTimeImmutable('2030-01-01 12:00:00'));
        // Le validateur voit encore l'agence que la transaction du test vient de supprimer.
        $service = new TripService($this->trips, new TripValidator(new AgencyRepository($other), $clock), $clock);
        $this->database->pdo()->exec(sprintf('DELETE FROM agence WHERE id = %d', $agencyId));

        try {
            $service->create($this->martin, ['departure_agency_id' => (string) $agencyId] + self::INPUT);
            self::fail('Une exception de validation était attendue.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('form', $exception->errors());
            self::assertSame((string) $agencyId, $exception->old()['departure_agency_id']);
        } finally {
            $this->database->pdo()->rollBack();
            $other->pdo()->exec(sprintf('DELETE FROM agence WHERE id = %d', $agencyId));
        }
    }

    public function testFormValuesMatchTheFormatOfTheForm(): void
    {
        $trip = $this->trips->findById($this->service->create($this->martin, self::INPUT));
        self::assertNotNull($trip);

        self::assertSame(self::INPUT, TripService::formValues($trip));
    }

    public function testDeleteUnknownTripIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete($this->admin, 999);
    }
}
