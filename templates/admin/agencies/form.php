<?php

/**
 * Formulaire de création ou de modification d'une agence.
 *
 * @var string                $pageTitle
 * @var string                $action
 * @var array<string, string> $old
 * @var array<string, string> $errors
 * @var int                   $maxLength
 * @var string                $csrfToken
 */

$nameError = $errors['name'] ?? null;
?>
<h1 class="h2 mb-4"><?= e($pageTitle) ?></h1>

<form method="post" action="<?= e($action) ?>" novalidate class="col-md-6">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

    <div class="mb-4">
        <label for="name" class="form-label">Nom de la ville</label>
        <input type="text" id="name" name="name" required maxlength="<?= e($maxLength) ?>"
               class="form-control<?= $nameError !== null ? ' is-invalid' : '' ?>"
               value="<?= e($old['name'] ?? '') ?>"<?= $nameError !== null ? ' aria-describedby="name-error"' : '' ?>>
<?php if ($nameError !== null) : ?>
        <div id="name-error" class="invalid-feedback"><?= e($nameError) ?></div>
<?php endif; ?>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a class="btn btn-outline-secondary" href="/admin/agencies">Annuler</a>
    </div>
</form>
