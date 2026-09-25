<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Core\Database;
use App\Core\Http\NotFoundException;
use App\Core\Validation\ValidationException;
use App\Repository\AgencyRepository;
use App\Service\AgencyService;
use App\Service\Exception\AgencyInUseException;
use App\Tests\Integration\DatabaseTestCase;
use App\Validator\AgencyValidator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests d'intégration des écritures d'agences.
 */
#[CoversClass(AgencyService::class)]
#[CoversClass(AgencyInUseException::class)]
final class AgencyServiceTest extends DatabaseTestCase
{
    private AgencyService $service;

    private AgencyRepository $agencies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agencies = new AgencyRepository($this->database);
        $this->service = new AgencyService($this->agencies, new AgencyValidator($this->agencies));
    }

    public function testCreatePersistsNormalizedName(): void
    {
        $agency = $this->service->create(['name' => '  Clermont-Ferrand ']);

        self::assertSame('Clermont-Ferrand', $this->agencies->findById($agency->id)?->name);
        self::assertSame(13, $this->agencies->count());
    }

    public function testCreateDuplicateIsRefusedWithoutWriting(): void
    {
        try {
            $this->service->create(['name' => 'nantes']);
            self::fail('Une exception de validation était attendue.');
        } catch (ValidationException $exception) {
            self::assertSame('nantes', $exception->old()['name']);
            self::assertSame(12, $this->agencies->count());
        }
    }

    public function testUpdateRenamesAgency(): void
    {
        $agency = $this->service->update(11, ['name' => 'Rennes Atalante']);

        self::assertSame('Rennes Atalante', $agency->name);
        self::assertSame('Rennes Atalante', $this->agencies->findById(11)?->name);
    }

    public function testUpdateToExistingNameIsRefused(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->update(11, ['name' => 'Lille']);
    }

    public function testUpdateUnknownAgencyIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->update(999, ['name' => 'Dijon']);
    }

    public function testDeleteUnusedAgency(): void
    {
        $created = $this->service->create(['name' => 'Dijon']);

        $deleted = $this->service->delete($created->id);

        self::assertSame('Dijon', $deleted->name);
        self::assertNull($this->agencies->findById($created->id));
    }

    public function testDeleteAgencyUsedByTripsIsRefused(): void
    {
        try {
            $this->service->delete(1);
            self::fail('La suppression d\'une agence utilisée doit être refusée.');
        } catch (AgencyInUseException $exception) {
            self::assertStringContainsString('« Paris »', $exception->getMessage());
            self::assertNotNull($this->agencies->findById(1));
        }
    }

    public function testConcurrentDuplicateOnCreateIsReportedAsValidationError(): void
    {
        // Une autre session a déjà inséré « Dijon » ; le contrôle d'unicité, fait avant, ne le voyait pas.
        $this->agencies->create('Dijon');

        $this->expectException(ValidationException::class);

        $this->serviceValidatedBy(self::concurrentConnection())->create(['name' => 'Dijon']);
    }

    public function testConcurrentDuplicateOnUpdateIsReportedAsValidationError(): void
    {
        $this->agencies->create('Dijon');

        $this->expectException(ValidationException::class);

        $this->serviceValidatedBy(self::concurrentConnection())->update(11, ['name' => 'Dijon']);
    }

    public function testTripAddedConcurrentlyBlocksDeletion(): void
    {
        $other = self::concurrentConnection();
        $other->pdo()->exec("INSERT INTO agence (nom) VALUES ('Zzz Concurrence')");
        $agencyId = (int) $other->pdo()->lastInsertId();

        try {
            // Première lecture : l'instantané de la transaction du test est figé ici.
            self::assertNotNull($this->agencies->findById($agencyId));

            $other->pdo()->exec(sprintf(
                "INSERT INTO trajet (agence_depart_id, agence_arrivee_id, date_heure_depart, date_heure_arrivee,"
                . ' places_totales, places_disponibles, auteur_id)'
                . " VALUES (%d, 1, '2030-01-01 08:00', '2030-01-01 09:00', 2, 1, 1)",
                $agencyId,
            ));

            $this->expectException(AgencyInUseException::class);
            $this->service->delete($agencyId);
        } finally {
            $this->database->pdo()->rollBack();
            $other->pdo()->exec(sprintf('DELETE FROM trajet WHERE agence_depart_id = %d', $agencyId));
            $other->pdo()->exec(sprintf('DELETE FROM agence WHERE id = %d', $agencyId));
        }
    }

    public function testDeleteUnknownAgencyIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->delete(999);
    }

    /**
     * Service dont le validateur lit les agences par une autre connexion.
     */
    private function serviceValidatedBy(Database $connection): AgencyService
    {
        return new AgencyService($this->agencies, new AgencyValidator(new AgencyRepository($connection)));
    }
}
