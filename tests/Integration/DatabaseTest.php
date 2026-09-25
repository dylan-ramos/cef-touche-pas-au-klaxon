<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Core\Config;
use App\Core\Database;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests de la connexion à la base de test et de la gestion des transactions.
 */
#[CoversClass(Database::class)]
final class DatabaseTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        $this->database = Database::fromConfig(
            Config::load(dirname(__DIR__, 2)),
            'DB_TEST_NAME',
            'DB_TEST_USER',
            'DB_TEST_PASSWORD',
        );
        $this->database->pdo()->exec('CREATE TEMPORARY TABLE demo (valeur VARCHAR(20) NOT NULL)');
    }

    public function testConnectionUsesSecureOptions(): void
    {
        $pdo = $this->database->pdo();

        self::assertSame(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        self::assertEmpty($pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES));
        $statement = $pdo->query('SELECT @@character_set_client');
        self::assertNotFalse($statement);
        self::assertSame('utf8mb4', $statement->fetchColumn());
    }

    public function testTransactionalCommitsOnSuccess(): void
    {
        $result = $this->database->transactional(static function (PDO $pdo): string {
            $pdo->exec("INSERT INTO demo (valeur) VALUES ('validée')");

            return 'ok';
        });

        self::assertSame('ok', $result);
        self::assertSame(1, $this->countRows());
    }

    public function testTransactionalRollsBackAndRethrowsOnFailure(): void
    {
        try {
            $this->database->transactional(static function (PDO $pdo): never {
                $pdo->exec("INSERT INTO demo (valeur) VALUES ('annulée')");

                throw new RuntimeException('échec');
            });
        } catch (RuntimeException $exception) {
            self::assertSame('échec', $exception->getMessage());
        }

        self::assertSame(0, $this->countRows());
    }

    private function countRows(): int
    {
        $statement = $this->database->pdo()->query('SELECT COUNT(*) FROM demo');
        self::assertNotFalse($statement);

        return (int) $statement->fetchColumn();
    }
}
