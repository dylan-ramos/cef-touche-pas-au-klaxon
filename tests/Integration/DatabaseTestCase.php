<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Core\Config;
use App\Core\Database;
use App\Entity\TripData;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Base des tests d'intégration sur la base de test.
 *
 * Le schéma et le jeu d'essais officiels (`database/*.sql`) sont chargés une
 * fois par exécution ; chaque test s'exécute ensuite dans une transaction
 * annulée à la fin, ce qui garantit des données identiques d'un test à l'autre.
 */
abstract class DatabaseTestCase extends TestCase
{
    private static ?Database $sharedDatabase = null;

    protected Database $database;

    protected function setUp(): void
    {
        $this->database = self::database();
        $this->database->pdo()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->database->pdo()->inTransaction()) {
            $this->database->pdo()->rollBack();
        }
    }

    /**
     * Données de trajet valides, modifiables par paramètres nommés.
     *
     * @param int    $departureAgencyId Agence de départ.
     * @param int    $arrivalAgencyId   Agence d'arrivée.
     * @param string $departureAt       Départ (format accepté par DateTimeImmutable).
     * @param string $arrivalAt         Arrivée.
     * @param int    $totalSeats        Places totales.
     * @param int    $availableSeats    Places disponibles.
     *
     * @return TripData
     */
    protected static function tripData(
        int $departureAgencyId = 1,
        int $arrivalAgencyId = 2,
        string $departureAt = '2030-06-01 08:00:00',
        string $arrivalAt = '2030-06-01 12:00:00',
        int $totalSeats = 4,
        int $availableSeats = 3,
    ): TripData {
        return new TripData(
            $departureAgencyId,
            $arrivalAgencyId,
            new DateTimeImmutable($departureAt),
            new DateTimeImmutable($arrivalAt),
            $totalSeats,
            $availableSeats,
        );
    }

    /**
     * Connexion à la base de test, initialisée au premier appel.
     *
     * @return Database
     */
    private static function database(): Database
    {
        if (self::$sharedDatabase === null) {
            $rootDir = dirname(__DIR__, 2);
            $database = Database::fromConfig(Config::load($rootDir), 'DB_TEST_NAME', 'DB_TEST_USER', 'DB_TEST_PASSWORD');
            foreach (['01_schema.sql', '02_seed.sql'] as $script) {
                self::runScript($database, $rootDir . '/database/' . $script);
            }
            self::$sharedDatabase = $database;
        }

        return self::$sharedDatabase;
    }

    /**
     * Exécute un script SQL du projet sur la base de test, sans ses
     * instructions de sélection ou de création de la base de production.
     *
     * @param Database $database Base de test.
     * @param string   $file     Chemin du script.
     *
     * @return void
     */
    private static function runScript(Database $database, string $file): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException(sprintf('Script illisible : %s.', $file));
        }

        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        $statements = preg_split('/;\s*$/m', $sql);
        foreach ($statements === false ? [] : $statements as $statement) {
            $statement = trim($statement);
            if ($statement === '' || preg_match('/^(CREATE DATABASE|USE)\b/i', $statement) === 1) {
                continue;
            }
            $database->pdo()->exec($statement);
        }
    }
}
