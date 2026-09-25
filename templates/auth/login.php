<?php

/**
 * Formulaire de connexion.
 *
 * @var array<string, string> $errors
 * @var array<string, string> $old
 * @var string                $csrfToken
 */

$emailError = $errors['email'] ?? null;
$passwordError = $errors['password'] ?? null;
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h1 class="h2 mb-4">Connexion</h1>

<?php if (isset($errors['credentials'])) : ?>
            <div class="alert alert-danger" role="alert"><?= e($errors['credentials']) ?></div>
<?php endif; ?>

        <form method="post" action="/login" novalidate>
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

            <div class="mb-3">
                <label for="email" class="form-label">Adresse e-mail</label>
                <input type="email" id="email" name="email" required maxlength="255" autocomplete="username"
                       class="form-control<?= $emailError !== null ? ' is-invalid' : '' ?>"
                       value="<?= e($old['email'] ?? '') ?>"<?= $emailError !== null ? ' aria-describedby="email-error"' : '' ?>>
<?php if ($emailError !== null) : ?>
                    <div id="email-error" class="invalid-feedback"><?= e($emailError) ?></div>
<?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" id="password" name="password" required maxlength="255" autocomplete="current-password"
                       class="form-control<?= $passwordError !== null ? ' is-invalid' : '' ?>"<?= $passwordError !== null ? ' aria-describedby="password-error"' : '' ?>>
<?php if ($passwordError !== null) : ?>
                    <div id="password-error" class="invalid-feedback"><?= e($passwordError) ?></div>
<?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>
    </div>
</div>
