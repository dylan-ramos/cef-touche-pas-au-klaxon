<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\ErrorHandler;
use App\Core\Http\ForbiddenException;
use App\Core\Http\NotFoundException;
use App\Core\View;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la transformation des exceptions en réponses HTTP.
 */
#[CoversClass(ErrorHandler::class)]
final class ErrorHandlerTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $logs = [];

    public function testHttpExceptionKeepsStatusAndMessageWithoutLogging(): void
    {
        $response = $this->handler(false)->handle(new NotFoundException('Trajet introuvable.'));

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('Trajet introuvable.', (string) $response->getContent());
        self::assertStringContainsString('title="Page introuvable"', (string) $response->getContent());
        self::assertSame([], $this->logs);
    }

    public function testForbiddenUsesDefaultMessage(): void
    {
        $response = $this->handler(false)->handle(new ForbiddenException());

        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('droits nécessaires', (string) $response->getContent());
    }

    public function testServerErrorHidesDetailsAndLogsThem(): void
    {
        $response = $this->handler(false)->handle(new LogicException('SQLSTATE secret'));

        self::assertSame(500, $response->getStatusCode());
        self::assertStringNotContainsString('SQLSTATE secret', (string) $response->getContent());
        self::assertStringContainsString('erreur inattendue', (string) $response->getContent());
        self::assertCount(1, $this->logs);
        self::assertStringContainsString('SQLSTATE secret', $this->logs[0]);
    }

    public function testServerErrorShowsDetailsInDebugMode(): void
    {
        $response = $this->handler(true)->handle(new LogicException('détail technique'));

        self::assertStringContainsString('[details]', (string) $response->getContent());
        self::assertStringContainsString('détail technique', (string) $response->getContent());
    }

    public function testFallsBackToPlainTextWhenErrorTemplateFails(): void
    {
        $handler = new ErrorHandler(new View('/chemin/inexistant'), false, function (string $message): void {
            $this->logs[] = $message;
        });

        $response = $handler->handle(new NotFoundException());

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertStringStartsWith('Page introuvable', (string) $response->getContent());
    }

    private function handler(bool $debug): ErrorHandler
    {
        $view = new View(dirname(__DIR__, 2) . '/Fixtures/templates');

        return new ErrorHandler($view, $debug, function (string $message): void {
            $this->logs[] = $message;
        });
    }
}
