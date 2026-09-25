<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Data;

use App\Core\Data\Row;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * Tests de la lecture typée des lignes SQL.
 */
#[CoversClass(Row::class)]
final class RowTest extends TestCase
{
    public function testReadsTypedValues(): void
    {
        $row = new Row(['id' => 3, 'total' => '12', 'nom' => 'Nice', 'depart' => '2030-05-01 08:15:00']);

        self::assertSame(3, $row->int('id'));
        self::assertSame(12, $row->int('total'));
        self::assertSame('Nice', $row->string('nom'));
        self::assertSame('2030-05-01 08:15', $row->dateTime('depart')->format('Y-m-d H:i'));
    }

    public function testMissingColumnThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new Row([]))->string('nom');
    }

    public function testWrongTypesThrow(): void
    {
        $row = new Row(['id' => 'abc', 'nom' => 5, 'date' => '01/05/2030']);

        $reads = [fn (): int => $row->int('id'), fn (): string => $row->string('nom'), fn () => $row->dateTime('date')];

        foreach ($reads as $read) {
            try {
                $read();
                self::fail('Une exception était attendue.');
            } catch (UnexpectedValueException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
