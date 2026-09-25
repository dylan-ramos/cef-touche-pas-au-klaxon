<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Role;
use App\Repository\UserRepository;
use App\Tests\Integration\DatabaseTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests d'intégration du dépôt des utilisateurs.
 */
#[CoversClass(UserRepository::class)]
final class UserRepositoryTest extends DatabaseTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->database);
    }

    public function testFindAllReturnsEmployeesAndAdministratorSortedByName(): void
    {
        $users = $this->users->findAll();

        self::assertCount(21, $users);
        self::assertSame(21, $this->users->count());
        self::assertSame('Administrateur', $users[0]->lastName);
        self::assertSame('Bernard', $users[1]->lastName);
    }

    public function testFindByIdKeepsAccentsAndRole(): void
    {
        $user = $this->users->findById(5);

        self::assertNotNull($user);
        self::assertSame('Lucie Lefèvre', $user->fullName());
        self::assertSame(Role::User, $user->role);
        self::assertNull($this->users->findById(999));
    }

    public function testFindCredentialsByEmailReturnsVerifiableHash(): void
    {
        $credentials = $this->users->findCredentialsByEmail('ALEXANDRE.MARTIN@email.fr');

        self::assertNotNull($credentials);
        self::assertSame(1, $credentials->user->id);
        self::assertTrue(password_verify('Covoiturage#2026', $credentials->passwordHash));
    }

    public function testFindCredentialsOfAdministrator(): void
    {
        $credentials = $this->users->findCredentialsByEmail('admin@touche-pas-au-klaxon.fr');

        self::assertNotNull($credentials);
        self::assertTrue($credentials->user->isAdmin());
        self::assertTrue(password_verify('Admin#Klaxon2026', $credentials->passwordHash));
    }

    public function testUnknownEmailReturnsNull(): void
    {
        self::assertNull($this->users->findCredentialsByEmail('inconnu@email.fr'));
    }
}
