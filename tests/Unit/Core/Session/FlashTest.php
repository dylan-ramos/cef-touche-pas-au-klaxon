<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Session;

use App\Core\Session\ArraySession;
use App\Core\Session\Flash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests des messages flash.
 */
#[CoversClass(Flash::class)]
#[CoversClass(ArraySession::class)]
final class FlashTest extends TestCase
{
    public function testMessagesAreConsumedOnceInOrder(): void
    {
        $flash = new Flash(new ArraySession());
        $flash->success('Le trajet a été créé.');
        $flash->error('Suppression impossible.');
        $flash->info('Information.');

        self::assertSame([
            ['type' => 'success', 'message' => 'Le trajet a été créé.'],
            ['type' => 'danger', 'message' => 'Suppression impossible.'],
            ['type' => 'info', 'message' => 'Information.'],
        ], $flash->consume());
        self::assertSame([], $flash->consume());
    }

    public function testMessagesSurviveUntilConsumedThroughSession(): void
    {
        $session = new ArraySession();
        (new Flash($session))->success('Conservé.');

        self::assertSame([['type' => 'success', 'message' => 'Conservé.']], (new Flash($session))->consume());
    }

    public function testMalformedSessionDataIsIgnored(): void
    {
        $session = new ArraySession();
        $session->set('_flash', ['texte brut', ['type' => 1, 'message' => 'x'], ['type' => 'info', 'message' => 'Valide']]);

        self::assertSame([['type' => 'info', 'message' => 'Valide']], (new Flash($session))->consume());
    }
}
