<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\Container;
use App\Core\Http\NotFoundException;
use App\Core\Routing\ControllerDispatcher;
use App\Tests\Fixtures\FakeController;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests du répartiteur entre le routeur et les contrôleurs.
 */
#[CoversClass(ControllerDispatcher::class)]
final class ControllerDispatcherTest extends TestCase
{
    private ControllerDispatcher $dispatcher;

    protected function setUp(): void
    {
        $container = new Container();
        $container->set(FakeController::class, static fn (): FakeController => new FakeController());
        $this->dispatcher = new ControllerDispatcher($container);
    }

    public function testCallsActionWithoutParameter(): void
    {
        $response = ($this->dispatcher->to(FakeController::class, 'index'))();

        self::assertSame('index', $response->getContent());
    }

    public function testConvertsRouteIdentifierToInteger(): void
    {
        $response = ($this->dispatcher->to(FakeController::class, 'show'))('42');

        self::assertSame('show:42', $response->getContent());
    }

    public function testGuardResponseStopsDispatch(): void
    {
        $guard = static fn (): Response => new Response('refusé', 403);
        $passing = static fn (): ?Response => null;

        $response = ($this->dispatcher->to(FakeController::class, 'index', [$passing, $guard]))();

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUnknownActionThrows(): void
    {
        $this->expectException(LogicException::class);

        ($this->dispatcher->to(FakeController::class, 'absente'))();
    }

    public function testActionMustReturnResponse(): void
    {
        $this->expectException(LogicException::class);

        ($this->dispatcher->to(FakeController::class, 'notAResponse'))();
    }

    #[DataProvider('invalidIdentifiers')]
    public function testRejectsInvalidIdentifiers(string $id): void
    {
        $this->expectException(NotFoundException::class);

        ControllerDispatcher::parseId($id);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIdentifiers(): iterable
    {
        yield 'zéro' => ['0'];
        yield 'zéro initial' => ['007'];
        yield 'négatif' => ['-1'];
        yield 'texte' => ['abc'];
        yield 'dépassement INT UNSIGNED' => ['4294967296'];
        yield 'trop long' => ['12345678901'];
    }

    public function testAcceptsMaximalIdentifier(): void
    {
        self::assertSame(4294967295, ControllerDispatcher::parseId('4294967295'));
    }
}
