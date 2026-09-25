<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Security;

use App\Core\Clock\FrozenClock;
use App\Core\Security\Throttle\ArrayThrottleStore;
use App\Core\Security\Throttle\FileThrottleStore;
use App\Core\Security\Throttle\LoginThrottle;
use App\Core\Security\Throttle\ThrottleStore;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la limitation des tentatives de connexion.
 */
#[CoversClass(LoginThrottle::class)]
#[CoversClass(ArrayThrottleStore::class)]
#[CoversClass(FileThrottleStore::class)]
final class LoginThrottleTest extends TestCase
{
    private const string KEY = 'login|a@b.fr|10.0.0.1';

    public function testBlocksAfterMaxAttemptsUntilWindowEnds(): void
    {
        $store = new ArrayThrottleStore();
        $throttle = self::throttle($store, '12:00:00');

        for ($i = 0; $i < 3; $i++) {
            self::assertSame(0, $throttle->secondsUntilAvailable(self::KEY));
            $throttle->hit(self::KEY);
        }

        self::assertSame(600, $throttle->secondsUntilAvailable(self::KEY));
        self::assertSame(1, self::throttle($store, '12:09:59')->secondsUntilAvailable(self::KEY));
        self::assertSame(0, self::throttle($store, '12:10:00')->secondsUntilAvailable(self::KEY));
    }

    public function testExpiredWindowRestartsCounting(): void
    {
        $store = new ArrayThrottleStore();
        self::throttle($store, '12:00:00')->hit(self::KEY);
        self::throttle($store, '12:00:00')->hit(self::KEY);

        self::throttle($store, '12:30:00')->hit(self::KEY);

        self::assertSame(1, $store->get(self::KEY)['attempts'] ?? null);
    }

    public function testClearResetsCounter(): void
    {
        $store = new ArrayThrottleStore();
        $throttle = self::throttle($store, '12:00:00');
        $throttle->hit(self::KEY);
        $throttle->clear(self::KEY);

        self::assertNull($store->get(self::KEY));
    }

    public function testKeyIgnoresCaseAndSurroundingSpaces(): void
    {
        self::assertSame(LoginThrottle::key(' A@B.fr ', '10.0.0.1'), LoginThrottle::key('a@b.fr', '10.0.0.1'));
        self::assertNotSame(LoginThrottle::key('a@b.fr', '10.0.0.1'), LoginThrottle::key('a@b.fr', '10.0.0.2'));
    }

    public function testFileStorePersistsHashedKeysAndIgnoresCorruptedFiles(): void
    {
        $dir = sys_get_temp_dir() . '/klaxon-throttle-' . bin2hex(random_bytes(4));
        $store = new FileThrottleStore($dir);

        $store->put(self::KEY, ['attempts' => 2, 'resetAt' => 123]);
        self::assertSame(['attempts' => 2, 'resetAt' => 123], (new FileThrottleStore($dir))->get(self::KEY));

        $files = glob($dir . '/*.json');
        self::assertIsArray($files);
        self::assertCount(1, $files);
        self::assertStringNotContainsString('a@b.fr', $files[0]);

        file_put_contents($files[0], '{corrompu');
        self::assertNull($store->get(self::KEY));

        $store->forget(self::KEY);
        self::assertNull($store->get(self::KEY));
        rmdir($dir);
    }

    private static function throttle(ThrottleStore $store, string $time): LoginThrottle
    {
        return new LoginThrottle($store, new FrozenClock(new DateTimeImmutable('2030-01-01 ' . $time)), 3, 600);
    }
}
