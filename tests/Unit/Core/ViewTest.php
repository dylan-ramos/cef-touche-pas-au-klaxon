<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\View;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests du moteur de rendu des gabarits.
 */
#[CoversClass(View::class)]
final class ViewTest extends TestCase
{
    private View $view;

    protected function setUp(): void
    {
        $this->view = new View(dirname(__DIR__, 2) . '/Fixtures/templates');
    }

    public function testRenderWrapsTemplateInLayoutAndEscapesData(): void
    {
        $response = $this->view->render('pages/hello', ['name' => '<script>alert(1)</script>'], 201);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
        $html = (string) $response->getContent();
        self::assertStringContainsString('<layout>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    public function testSharedDataAndNestedPartialsAreAvailable(): void
    {
        $this->view->share('appName', 'Klaxon');

        $html = $this->view->partial('pages/shared');

        self::assertStringContainsString('<p>Klaxon</p>', $html);
        self::assertStringContainsString('Bonjour fragment', $html);
    }

    public function testLazySharedDataIsResolvedAtRenderTimeUnlessProvided(): void
    {
        $name = 'initial';
        $this->view->share('appName', 'Klaxon');
        $this->view->shareLazy('name', static function () use (&$name): string {
            return $name;
        });
        $name = 'au rendu';

        self::assertStringContainsString('Bonjour au rendu', $this->view->partial('pages/hello'));
        self::assertStringContainsString('Bonjour explicite', $this->view->partial('pages/hello', ['name' => 'explicite']));
    }

    public function testRejectsTemplateNameOutsideTemplateDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->view->partial('../secret');
    }

    public function testMissingTemplateThrows(): void
    {
        $this->expectException(RuntimeException::class);

        $this->view->partial('pages/absent');
    }

    public function testTemplateExceptionDiscardsPartialOutput(): void
    {
        $level = ob_get_level();

        try {
            $this->view->partial('pages/failing');
            self::fail('Une exception était attendue.');
        } catch (RuntimeException $exception) {
            self::assertSame('échec du gabarit', $exception->getMessage());
        }

        self::assertSame($level, ob_get_level());
    }
}
