<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Core\Data\Exception\ForeignKeyConstraintViolationException;
use App\Core\Data\Exception\UniqueConstraintViolationException;
use App\Repository\AgencyRepository;
use App\Tests\Integration\DatabaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests d'intégration du dépôt des agences.
 */
#[CoversClass(AgencyRepository::class)]
final class AgencyRepositoryTest extends DatabaseTestCase
{
    private AgencyRepository $agencies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agencies = new AgencyRepository($this->database);
    }

    public function testFindAllReturnsSeededAgenciesAlphabetically(): void
    {
        $names = array_map(static fn ($agency): string => $agency->name, $this->agencies->findAll());

        self::assertCount(12, $names);
        self::assertSame('Bordeaux', $names[0]);
        self::assertSame('Toulouse', $names[11]);
        self::assertSame(12, $this->agencies->count());
    }

    public function testFindById(): void
    {
        self::assertSame('Paris', $this->agencies->findById(1)?->name);
        self::assertNull($this->agencies->findById(999));
    }

    public function testCreatePersistsAgency(): void
    {
        $id = $this->agencies->create('Dijon');

        self::assertSame('Dijon', $this->agencies->findById($id)?->name);
        self::assertSame(13, $this->agencies->count());
    }

    public function testCreateRejectsDuplicateNameIgnoringCase(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $this->agencies->create('PARIS');
    }

    public function testExistsByNameIgnoresCaseAndCanExcludeAnAgency(): void
    {
        self::assertTrue($this->agencies->existsByName('lyon'));
        self::assertFalse($this->agencies->existsByName('Lyon', 2));
        self::assertFalse($this->agencies->existsByName('Dijon'));
    }

    public function testUpdateRenamesAgency(): void
    {
        self::assertTrue($this->agencies->update(12, 'Reims Centre'));
        self::assertSame('Reims Centre', $this->agencies->findById(12)?->name);
    }

    public function testUpdateWithSameNameSucceeds(): void
    {
        self::assertTrue($this->agencies->update(12, 'Reims'));
    }

    public function testUpdateUnknownAgencyReturnsFalse(): void
    {
        self::assertFalse($this->agencies->update(999, 'Nulle part'));
    }

    public function testUpdateRejectsDuplicateName(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        $this->agencies->update(12, 'Lille');
    }

    public function testDeleteUnusedAgency(): void
    {
        $id = $this->agencies->create('Dijon');

        self::assertFalse($this->agencies->isUsedByTrips($id));
        self::assertTrue($this->agencies->delete($id));
        self::assertNull($this->agencies->findById($id));
    }

    public function testDeleteUnknownAgencyReturnsFalse(): void
    {
        self::assertFalse($this->agencies->delete(999));
    }

    public function testDeleteAgencyUsedByTripsIsRefused(): void
    {
        self::assertTrue($this->agencies->isUsedByTrips(1));

        $this->expectException(ForeignKeyConstraintViolationException::class);

        $this->agencies->delete(1);
    }
}
