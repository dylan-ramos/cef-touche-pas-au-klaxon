<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\Format;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests des formats d'affichage.
 */
#[CoversClass(Format::class)]
final class FormatTest extends TestCase
{
    public function testFormatsDateAndTime(): void
    {
        $date = new DateTimeImmutable('2026-10-05 08:05:00');

        self::assertSame('05/10/2026', Format::date($date));
        self::assertSame('08:05', Format::time($date));
    }

    public function testFormatsPhoneNumbersByPairs(): void
    {
        self::assertSame("06\u{00A0}12\u{00A0}34\u{00A0}56\u{00A0}78", Format::phone('0612345678'));
        self::assertSame('+33 6 12', Format::phone('+33 6 12'));
    }
}
