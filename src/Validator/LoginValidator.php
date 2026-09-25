<?php

declare(strict_types=1);

namespace App\Validator;

use App\Core\Validation\Input;
use App\Core\Validation\ValidationException;

/**
 * Validation du formulaire de connexion.
 */
final class LoginValidator
{
    private const int EMAIL_MAX_LENGTH = 255;
    private const int PASSWORD_MAX_LENGTH = 255;

    /**
     * Valide la saisie et retourne l'e-mail et le mot de passe.
     *
     * @param array<array-key, mixed> $data Corps de la requête.
     *
     * @return array{email: string, password: string}
     *
     * @throws ValidationException Si un champ est manquant ou invalide.
     */
    public function validate(array $data): array
    {
        $input = new Input($data);
        $email = mb_strtolower($input->string('email'));
        $password = $input->raw('password');
        $errors = [];

        if ($email === '') {
            $errors['email'] = 'L\'adresse e-mail est obligatoire.';
        } elseif (mb_strlen($email) > self::EMAIL_MAX_LENGTH || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'L\'adresse e-mail n\'est pas valide.';
        }

        if ($password === '') {
            $errors['password'] = 'Le mot de passe est obligatoire.';
        } elseif (strlen($password) > self::PASSWORD_MAX_LENGTH) {
            $errors['password'] = 'Le mot de passe est trop long.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors, ['email' => $email]);
        }

        return ['email' => $email, 'password' => $password];
    }
}
