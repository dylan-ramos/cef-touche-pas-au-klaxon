<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Agency;
use App\Entity\Role;
use App\Entity\Trip;
use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests des comportements portés par les entités.
 */
#[CoversClass(User::class)]
#[CoversClass(Trip::class)]
#[CoversClass(Role::class)]
final class EntityTest extends TestCase
{
    public function testUserPresentation(): void
    {
        $user = self::user(1, Role::User);

        self::assertSame('Chloé Roux', $user->fullName());
        self::assertSame("06\u{00A0}33\u{00A0}22\u{00A0}11\u{00A0}99", $user->formattedPhone());
        self::assertFalse($user->isAdmin());
        self::assertTrue(self::user(2, Role::Admin)->isAdmin());
        self::assertSame('Administrateur', Role::Admin->label());
        self::assertSame('Employé', Role::User->label());
    }

    public function testTripAuthorshipAndState(): void
    {
        $author = self::user(1, Role::User);
        $trip = new Trip(
            10,
            new Agency(1, 'Paris'),
            new Agency(2, 'Lyon'),
            new DateTimeImmutable('2030-01-01 08:00'),
            new DateTimeImmutable('2030-01-01 12:00'),
            3,
            0,
            $author,
        );

        self::assertTrue($trip->isAuthoredBy($author));
        self::assertFalse($trip->isAuthoredBy(self::user(2, Role::Admin)));
        self::assertFalse($trip->isAuthoredBy(null));
        self::assertTrue($trip->isFull());
        self::assertFalse($trip->hasDeparted(new DateTimeImmutable('2030-01-01 07:59')));
        self::assertTrue($trip->hasDeparted(new DateTimeImmutable('2030-01-01 08:00')));
    }

    private static function user(int $id, Role $role): User
    {
        return new User($id, 'Roux', 'Chloé', '0633221199', 'chloe.roux@email.fr', $role);
    }
}
