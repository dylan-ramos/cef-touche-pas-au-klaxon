<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

/**
 * Tests des fonctions utilitaires des gabarits.
 */
#[CoversFunction('e')]
final class HelpersTest extends TestCase
{
    public function testEscapesHtmlSpecialCharacters(): void
    {
        self::assertSame('&lt;a href=&quot;x&quot;&gt;L&apos;agence&lt;/a&gt;', e('<a href="x">L\'agence</a>'));
    }

    public function testConvertsScalarsAndNull(): void
    {
        self::assertSame('', e(null));
        self::assertSame('42', e(42));
        self::assertSame('1', e(true));
        self::assertSame('Lefèvre', e('Lefèvre'));
    }

    public function testReplacesInvalidUtf8(): void
    {
        self::assertSame("a\u{FFFD}b", e("a\xC3b"));
    }
}
