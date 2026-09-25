<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Security;

use App\Core\Http\InvalidCsrfTokenException;
use App\Core\Security\Csrf;
use App\Core\Session\ArraySession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la protection CSRF.
 */
#[CoversClass(Csrf::class)]
#[CoversClass(InvalidCsrfTokenException::class)]
final class CsrfTest extends TestCase
{
    public function testTokenIsStableWithinSession(): void
    {
        $csrf = new Csrf(new ArraySession());
        $token = $csrf->token();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        self::assertSame($token, $csrf->token());
        self::assertTrue($csrf->isValid($token));
    }

    public function testRejectsMissingForgedOrNonStringTokens(): void
    {
        $csrf = new Csrf(new ArraySession());
        $token = $csrf->token();

        self::assertFalse($csrf->isValid(null));
        self::assertFalse($csrf->isValid(''));
        self::assertFalse($csrf->isValid(strrev($token)));
        self::assertFalse($csrf->isValid([$token]));
    }

    public function testNoTokenInSessionRejectsEverything(): void
    {
        self::assertFalse((new Csrf(new ArraySession()))->isValid(''));
    }

    public function testRegenerateInvalidatesPreviousToken(): void
    {
        $csrf = new Csrf(new ArraySession());
        $old = $csrf->token();
        $new = $csrf->regenerate();

        self::assertNotSame($old, $new);
        self::assertFalse($csrf->isValid($old));
        self::assertTrue($csrf->isValid($new));
    }

    public function testAssertValidThrows419(): void
    {
        $csrf = new Csrf(new ArraySession());

        try {
            $csrf->assertValid('faux');
            self::fail('Une exception était attendue.');
        } catch (InvalidCsrfTokenException $exception) {
            self::assertSame(419, $exception->getStatusCode());
        }

        $csrf->assertValid($csrf->token());
    }
}
