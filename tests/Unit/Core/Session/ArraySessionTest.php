<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Session;

use App\Core\Session\ArraySession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la session en mémoire.
 */
#[CoversClass(ArraySession::class)]
final class ArraySessionTest extends TestCase
{
    public function testStoresReadsAndRemovesValues(): void
    {
        $session = new ArraySession();
        $session->set('clé', 'valeur');

        self::assertTrue($session->has('clé'));
        self::assertSame('valeur', $session->get('clé'));
        self::assertSame('défaut', $session->get('absente', 'défaut'));

        $session->remove('clé');
        self::assertFalse($session->has('clé'));
    }

    public function testDestroyClearsDataAndRegenerateIsCounted(): void
    {
        $session = new ArraySession();
        $session->set('a', 1);
        $session->regenerate();
        $session->destroy();

        self::assertFalse($session->has('a'));
        self::assertSame(1, $session->regenerations());
    }
}
