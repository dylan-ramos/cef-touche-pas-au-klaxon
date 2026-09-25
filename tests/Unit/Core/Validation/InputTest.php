<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Validation;

use App\Core\Validation\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la lecture défensive des formulaires.
 */
#[CoversClass(Input::class)]
final class InputTest extends TestCase
{
    public function testReadsStringsDefensively(): void
    {
        $input = new Input(['nom' => '  Lyon ', 'tableau' => ['x'], 'mdp' => ' secret ']);

        self::assertSame('Lyon', $input->string('nom'));
        self::assertSame('', $input->string('absent'));
        self::assertSame('', $input->string('tableau'));
        self::assertSame(' secret ', $input->raw('mdp'));
    }

    public function testParsesBoundedIntegers(): void
    {
        $input = new Input(['a' => '3', 'b' => '0', 'c' => '3.5', 'd' => 'abc', 'e' => ' 7 ', 'f' => '12']);

        self::assertSame(3, $input->positiveInt('a'));
        self::assertNull($input->positiveInt('b'));
        self::assertNull($input->positiveInt('c'));
        self::assertNull($input->positiveInt('d'));
        self::assertSame(7, $input->positiveInt('e'));
        self::assertSame(0, $input->intBetween('b', 0, 9));
        self::assertNull($input->intBetween('f', 0, 9));
    }
}
