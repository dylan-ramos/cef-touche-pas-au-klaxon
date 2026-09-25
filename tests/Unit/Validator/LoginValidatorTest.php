<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Core\Validation\ValidationException;
use App\Validator\LoginValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests de la validation du formulaire de connexion.
 */
#[CoversClass(LoginValidator::class)]
#[CoversClass(ValidationException::class)]
final class LoginValidatorTest extends TestCase
{
    public function testNormalizesEmailAndKeepsPasswordAsIs(): void
    {
        $result = (new LoginValidator())->validate(['email' => '  Sophie.Dubois@Email.fr ', 'password' => ' mot de passe ']);

        self::assertSame(['email' => 'sophie.dubois@email.fr', 'password' => ' mot de passe '], $result);
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>         $fields
     */
    #[DataProvider('invalidInputs')]
    public function testRejectsInvalidInput(array $data, array $fields): void
    {
        try {
            (new LoginValidator())->validate($data);
            self::fail('Une exception était attendue.');
        } catch (ValidationException $exception) {
            self::assertSame($fields, array_keys($exception->errors()));
            self::assertArrayNotHasKey('password', $exception->old());
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'vide' => [[], ['email', 'password']];
        yield 'e-mail invalide' => [['email' => 'pas-un-email', 'password' => 'x'], ['email']];
        yield 'e-mail trop long' => [['email' => str_repeat('a', 250) . '@b.fr', 'password' => 'x'], ['email']];
        yield 'mot de passe manquant' => [['email' => 'a@b.fr'], ['password']];
        yield 'mot de passe trop long' => [['email' => 'a@b.fr', 'password' => str_repeat('x', 256)], ['password']];
        yield 'champs tableaux' => [['email' => ['a@b.fr'], 'password' => ['x']], ['email', 'password']];
    }
}
