<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\ErrorHandler;
use App\Core\Http\MethodNotAllowedException;
use App\Core\Routing\AllowedMethods;
use App\Core\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la détection des méthodes acceptées (réponse 405).
 */
#[CoversClass(AllowedMethods::class)]
#[CoversClass(MethodNotAllowedException::class)]
final class AllowedMethodsTest extends TestCase
{
    private const array ROUTES = [
        ['route' => '/', 'method' => 'GET'],
        ['route' => '/logout', 'method' => 'POST'],
        ['route' => '/trips/:id', 'method' => 'POST'],
        ['route' => '/trips/:id/edit', 'method' => 'GET'],
        ['route' => '/login', 'method' => 'GET'],
        ['route' => '/login', 'method' => 'POST'],
        'entrée invalide',
    ];

    public function testListsMethodsOfMatchingRoutes(): void
    {
        self::assertSame(['POST'], AllowedMethods::for(self::ROUTES, '/logout'));
        self::assertSame(['GET', 'HEAD', 'POST'], AllowedMethods::for(self::ROUTES, '/login'));
        self::assertSame(['POST'], AllowedMethods::for(self::ROUTES, '/trips/12'));
        self::assertSame(['GET', 'HEAD'], AllowedMethods::for(self::ROUTES, '/trips/12/edit'));
    }

    public function testUnknownPathHasNoMethod(): void
    {
        self::assertSame([], AllowedMethods::for(self::ROUTES, '/trips/abc'));
        self::assertSame([], AllowedMethods::for(self::ROUTES, '/inconnue'));
    }

    public function testErrorHandlerAddsAllowHeader(): void
    {
        $handler = new ErrorHandler(new View(dirname(__DIR__, 2) . '/Fixtures/templates'));

        $response = $handler->handle(new MethodNotAllowedException(['POST']));

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('POST', $response->headers->get('Allow'));
        self::assertStringContainsString('Méthode non autorisée', (string) $response->getContent());
    }
}
