<?php

declare(strict_types=1);

namespace App\Tests\Integration\Validator;

use App\Core\Clock\FrozenClock;
use App\Core\Validation\ValidationException;
use App\Repository\AgencyRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Validator\TripValidator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests des contrôles de cohérence d'un trajet (agences lues dans la base de test,
 * horloge figée au 1er janvier 2030 à 12 h).
 */
#[CoversClass(TripValidator::class)]
final class TripValidatorTest extends DatabaseTestCase
{
    private TripValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TripValidator(
            new AgencyRepository($this->database),
            new FrozenClock(new DateTimeImmutable('2030-01-01 12:00:00')),
        );
    }

    public function testValidInputProducesTripData(): void
    {
        $data = $this->validator->validate(self::input());

        self::assertSame(1, $data->departureAgencyId);
        self::assertSame(2, $data->arrivalAgencyId);
        self::assertSame('2030-01-02 08:00', $data->departureAt->format('Y-m-d H:i'));
        self::assertSame('2030-01-02 12:30', $data->arrivalAt->format('Y-m-d H:i'));
        self::assertSame(4, $data->totalSeats);
        self::assertSame(3, $data->availableSeats);
    }

    public function testBoundaryValuesAreAccepted(): void
    {
        $data = $this->validator->validate(self::input([
            'departure_at' => '2030-01-01T12:01',
            'arrival_at' => '2030-01-01T12:02',
            'total_seats' => '9',
            'available_seats' => '0',
        ]));

        self::assertSame(9, $data->totalSeats);
        self::assertSame(0, $data->availableSeats);
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('invalidInputs')]
    public function testRejectsIncoherentInput(array $override, string $field, string $message): void
    {
        try {
            $this->validator->validate(self::input($override));
            self::fail('Une exception de validation était attendue.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey($field, $exception->errors());
            self::assertStringContainsString($message, $exception->errors()[$field]);
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'agence de départ manquante' => [['departure_agency_id' => ''], 'departure_agency_id', 'choisir'];
        yield 'agence de départ inexistante' => [['departure_agency_id' => '999'], 'departure_agency_id', 'n\'existe pas'];
        yield 'agence non numérique' => [['arrival_agency_id' => 'Lyon'], 'arrival_agency_id', 'choisir'];
        yield 'agences identiques' => [['arrival_agency_id' => '1'], 'arrival_agency_id', 'différente'];
        yield 'date de départ invalide' => [['departure_at' => '2030-02-30T08:00'], 'departure_at', 'valides'];
        yield 'date de départ mal formée' => [['departure_at' => '02/01/2030 08:00'], 'departure_at', 'valides'];
        yield 'départ dans le passé' => [['departure_at' => '2029-12-31T08:00'], 'departure_at', 'futur'];
        yield 'départ à l\'instant présent' => [['departure_at' => '2030-01-01T12:00'], 'departure_at', 'futur'];
        yield 'arrivée avant le départ' => [['arrival_at' => '2030-01-02T07:00'], 'arrival_at', 'postérieure'];
        yield 'arrivée égale au départ' => [['arrival_at' => '2030-01-02T08:00'], 'arrival_at', 'postérieure'];
        yield 'arrivée manquante' => [['arrival_at' => ''], 'arrival_at', 'valides'];
        yield 'aucune place' => [['total_seats' => '0'], 'total_seats', 'entre 1 et 9'];
        yield 'trop de places' => [['total_seats' => '10'], 'total_seats', 'entre 1 et 9'];
        yield 'places décimales' => [['total_seats' => '2.5'], 'total_seats', 'entre 1 et 9'];
        yield 'disponibles > totales' => [['available_seats' => '5'], 'available_seats', 'comprises entre 0'];
        yield 'disponibles négatives' => [['available_seats' => '-1'], 'available_seats', 'comprises entre 0'];
        yield 'disponibles texte' => [['available_seats' => 'trois'], 'available_seats', 'comprises entre 0'];
        yield 'champ tableau forgé' => [['total_seats' => ['4']], 'total_seats', 'entre 1 et 9'];
    }

    public function testErrorsKeepInputForRedisplay(): void
    {
        try {
            $this->validator->validate(self::input(['arrival_agency_id' => '1', 'total_seats' => '<b>4</b>']));
            self::fail('Une exception de validation était attendue.');
        } catch (ValidationException $exception) {
            self::assertSame('1', $exception->old()['departure_agency_id']);
            self::assertSame('<b>4</b>', $exception->old()['total_seats']);
            self::assertSame('2030-01-02T08:00', $exception->old()['departure_at']);
        }
    }

    public function testEmptyFormReportsEveryField(): void
    {
        try {
            $this->validator->validate([]);
            self::fail('Une exception de validation était attendue.');
        } catch (ValidationException $exception) {
            self::assertSame(TripValidator::FIELDS, array_keys($exception->errors()));
        }
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private static function input(array $override = []): array
    {
        return array_merge([
            'departure_agency_id' => '1',
            'arrival_agency_id' => '2',
            'departure_at' => '2030-01-02T08:00',
            'arrival_at' => '2030-01-02T12:30',
            'total_seats' => '4',
            'available_seats' => '3',
        ], $override);
    }
}
