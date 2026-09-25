<?php

/**
 * Formulaire de proposition ou de modification d'un trajet.
 *
 * Les informations de l'auteur sont affichées en lecture seule : elles ne
 * font pas partie des données envoyées.
 *
 * @var string                     $pageTitle
 * @var string                     $action
 * @var \App\Entity\User           $author
 * @var list<\App\Entity\Agency>   $agencies
 * @var array<string, string>      $old
 * @var array<string, string>      $errors
 * @var string                     $minDateTime
 * @var int                        $maxSeats
 * @var string                     $csrfToken
 */

$value = static fn (string $field): string => $old[$field] ?? '';
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
$describedBy = static fn (string $field): string => isset($errors[$field]) ? ' aria-describedby="' . $field . '-error"' : '';
$error = static fn (string $field): string => isset($errors[$field])
    ? '<div id="' . $field . '-error" class="invalid-feedback">' . e($errors[$field]) . '</div>'
    : '';
?>
<h1 class="h2 mb-4"><?= e($pageTitle) ?></h1>

<?php if (isset($errors['form'])) : ?>
<div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" novalidate>
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

    <fieldset class="mb-4">
        <legend class="h5">Personne à contacter</legend>
        <div class="row g-3">
            <div class="col-md-3">
                <label for="author-lastname" class="form-label">Nom</label>
                <input type="text" id="author-lastname" class="form-control" value="<?= e($author->lastName) ?>" disabled>
            </div>
            <div class="col-md-3">
                <label for="author-firstname" class="form-label">Prénom</label>
                <input type="text" id="author-firstname" class="form-control" value="<?= e($author->firstName) ?>" disabled>
            </div>
            <div class="col-md-3">
                <label for="author-email" class="form-label">Email</label>
                <input type="email" id="author-email" class="form-control" value="<?= e($author->email) ?>" disabled>
            </div>
            <div class="col-md-3">
                <label for="author-phone" class="form-label">Téléphone</label>
                <input type="tel" id="author-phone" class="form-control" value="<?= e($author->formattedPhone()) ?>" disabled>
            </div>
        </div>
    </fieldset>

    <fieldset class="mb-4">
        <legend class="h5">Trajet</legend>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="departure_agency_id" class="form-label">Agence de départ</label>
                <select id="departure_agency_id" name="departure_agency_id" required
                        class="form-select<?= $invalid('departure_agency_id') ?>"<?= $describedBy('departure_agency_id') ?>>
                    <option value="">Choisir une agence</option>
<?php foreach ($agencies as $agency) : ?>
                    <option value="<?= e($agency->id) ?>"<?= $value('departure_agency_id') === (string) $agency->id ? ' selected' : '' ?>><?= e($agency->name) ?></option>
<?php endforeach; ?>
                </select>
                <?= $error('departure_agency_id') ?>
            </div>
            <div class="col-md-6">
                <label for="arrival_agency_id" class="form-label">Agence d'arrivée</label>
                <select id="arrival_agency_id" name="arrival_agency_id" required
                        class="form-select<?= $invalid('arrival_agency_id') ?>"<?= $describedBy('arrival_agency_id') ?>>
                    <option value="">Choisir une agence</option>
<?php foreach ($agencies as $agency) : ?>
                    <option value="<?= e($agency->id) ?>"<?= $value('arrival_agency_id') === (string) $agency->id ? ' selected' : '' ?>><?= e($agency->name) ?></option>
<?php endforeach; ?>
                </select>
                <?= $error('arrival_agency_id') ?>
            </div>

            <div class="col-md-6">
                <label for="departure_at" class="form-label">Date et heure de départ</label>
                <input type="datetime-local" id="departure_at" name="departure_at" required min="<?= e($minDateTime) ?>"
                       class="form-control<?= $invalid('departure_at') ?>" value="<?= e($value('departure_at')) ?>"<?= $describedBy('departure_at') ?>>
                <?= $error('departure_at') ?>
            </div>
            <div class="col-md-6">
                <label for="arrival_at" class="form-label">Date et heure d'arrivée</label>
                <input type="datetime-local" id="arrival_at" name="arrival_at" required min="<?= e($minDateTime) ?>"
                       class="form-control<?= $invalid('arrival_at') ?>" value="<?= e($value('arrival_at')) ?>"<?= $describedBy('arrival_at') ?>>
                <?= $error('arrival_at') ?>
            </div>

            <div class="col-md-6">
                <label for="total_seats" class="form-label">Nombre total de places</label>
                <input type="number" id="total_seats" name="total_seats" required min="1" max="<?= e($maxSeats) ?>" step="1"
                       class="form-control<?= $invalid('total_seats') ?>" value="<?= e($value('total_seats')) ?>"<?= $describedBy('total_seats') ?>>
                <?= $error('total_seats') ?>
            </div>
            <div class="col-md-6">
                <label for="available_seats" class="form-label">Nombre de places disponibles</label>
                <input type="number" id="available_seats" name="available_seats" required min="0" max="<?= e($maxSeats) ?>" step="1"
                       class="form-control<?= $invalid('available_seats') ?>" value="<?= e($value('available_seats')) ?>"<?= $describedBy('available_seats') ?>>
                <?= $error('available_seats') ?>
            </div>
        </div>
    </fieldset>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a class="btn btn-outline-secondary" href="/">Annuler</a>
    </div>
</form>
