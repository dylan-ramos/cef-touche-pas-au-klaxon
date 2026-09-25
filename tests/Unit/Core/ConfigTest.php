<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\Config;
use App\Core\Exception\ConfigException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la lecture typée de la configuration.
 */
#[CoversClass(Config::class)]
final class ConfigTest extends TestCase
{
    public function testReturnsStringValue(): void
    {
        $config = new Config(['APP_NAME' => 'Klaxon']);

        self::assertSame('Klaxon', $config->string('APP_NAME'));
    }

    public function testReturnsDefaultWhenMissing(): void
    {
        self::assertSame('défaut', (new Config([]))->string('ABSENT', 'défaut'));
    }

    public function testMissingMandatoryValueThrows(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('ABSENT');

        (new Config([]))->string('ABSENT');
    }

    public function testParsesBooleans(): void
    {
        $config = new Config(['A' => 'true', 'B' => '1', 'C' => 'false', 'D' => 'non']);

        self::assertTrue($config->bool('A'));
        self::assertTrue($config->bool('B'));
        self::assertFalse($config->bool('C'));
        self::assertFalse($config->bool('D'));
        self::assertTrue($config->bool('ABSENT', true));
    }

    public function testParsesIntegers(): void
    {
        $config = new Config(['PORT' => '3306']);

        self::assertSame(3306, $config->int('PORT'));
        self::assertSame(42, $config->int('ABSENT', 42));
    }

    public function testRejectsNonIntegerValue(): void
    {
        $this->expectException(ConfigException::class);

        (new Config(['PORT' => 'abc']))->int('PORT');
    }

    public function testLoadReadsDotEnvFileAndEnvironmentOverridesIt(): void
    {
        $dir = sys_get_temp_dir() . '/klaxon-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/.env', "KLAXON_FROM_FILE=fichier\nKLAXON_OVERRIDDEN=fichier\n");
        putenv('KLAXON_OVERRIDDEN=environnement');

        try {
            $config = Config::load($dir);

            self::assertSame('fichier', $config->string('KLAXON_FROM_FILE'));
            self::assertSame('environnement', $config->string('KLAXON_OVERRIDDEN'));
        } finally {
            putenv('KLAXON_OVERRIDDEN');
            unlink($dir . '/.env');
            rmdir($dir);
        }
    }
}
