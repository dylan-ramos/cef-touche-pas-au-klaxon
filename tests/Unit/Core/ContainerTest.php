<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\Container;
use App\Core\Exception\ContainerException;
use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests du conteneur de services.
 */
#[CoversClass(Container::class)]
final class ContainerTest extends TestCase
{
    public function testBuildsServiceOnceAndSharesIt(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set(stdClass::class, static function () use (&$calls): stdClass {
            $calls++;

            return new stdClass();
        });

        $first = $container->get(stdClass::class);
        $second = $container->get(stdClass::class);

        self::assertSame($first, $second);
        self::assertSame(1, $calls);
    }

    public function testFactoryReceivesContainerToResolveDependencies(): void
    {
        $container = new Container();
        $container->instance(stdClass::class, new stdClass());
        $container->set(ArrayObject::class, static fn (Container $c): ArrayObject => new ArrayObject([$c->get(stdClass::class)]));

        self::assertSame($container->get(stdClass::class), $container->get(ArrayObject::class)[0]);
    }

    public function testUnknownServiceThrows(): void
    {
        $this->expectException(ContainerException::class);

        (new Container())->get(stdClass::class);
    }

    public function testServiceOfWrongTypeThrows(): void
    {
        $container = new Container();
        $container->instance(ArrayObject::class, new stdClass());

        $this->expectException(ContainerException::class);

        $container->get(ArrayObject::class);
    }

    public function testHasReportsDeclaredServices(): void
    {
        $container = new Container();
        $container->set(stdClass::class, static fn (): stdClass => new stdClass());

        self::assertTrue($container->has(stdClass::class));
        self::assertFalse($container->has(ArrayObject::class));
    }
}
