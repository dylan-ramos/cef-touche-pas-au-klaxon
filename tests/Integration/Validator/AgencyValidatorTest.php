<?php

declare(strict_types=1);

namespace App\Tests\Integration\Validator;

use App\Core\Validation\ValidationException;
use App\Repository\AgencyRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Validator\AgencyValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests de la validation du nom d'agence.
 */
#[CoversClass(AgencyValidator::class)]
final class AgencyValidatorTest extends DatabaseTestCase
{
    private AgencyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AgencyValidator(new AgencyRepository($this->database));
    }

    public function testNormalizesAndAcceptsValidNames(): void
    {
        self::assertSame('Aix-en-Provence', $this->validator->validate(['name' => '  Aix-en-Provence ']));
        self::assertSame('Saint Étienne', $this->validator->validate(['name' => "Saint \t  Étienne"]));
        self::assertSame('L\'Isle-d\'Abeau', $this->validator->validate(['name' => 'L\'Isle-d\'Abeau']));
    }

    public function testCurrentNameIsAcceptedWhenEditingThatAgency(): void
    {
        self::assertSame('Paris', $this->validator->validate(['name' => 'Paris'], 1));
    }

    #[DataProvider('invalidNames')]
    public function testRejectsInvalidNames(mixed $name, string $message): void
    {
        try {
            $this->validator->validate(['name' => $name]);
            self::fail('Une exception de validation était attendue.');
        } catch (ValidationException $exception) {
            self::assertStringContainsString($message, $exception->errors()['name'] ?? '');
        }
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidNames(): iterable
    {
        yield 'vide' => ['   ', 'obligatoire'];
        yield 'tableau forgé' => [['Paris'], 'obligatoire'];
        yield 'trop long' => [str_repeat('a', 101), 'limité à 100'];
        yield 'balise HTML' => ['<script>', 'que des lettres'];
        yield 'chiffres' => ['Paris 15', 'que des lettres'];
        yield 'commence par un tiret' => ['-Lyon', 'que des lettres'];
        yield 'doublon' => ['Lyon', 'déjà ce nom'];
        yield 'doublon casse et accents' => ['REÏMS', 'déjà ce nom'];
    }
}
