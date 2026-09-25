<?php

/**
 * Liste de tous les trajets (administration).
 *
 * @var list<\App\Entity\Trip> $trips
 * @var \DateTimeImmutable     $now
 * @var string                 $csrfToken
 */

use App\Core\Format;
?>
<h1 class="h2 mb-3">Trajets</h1>

<?php if ($trips === []) : ?>
<p class="alert alert-light border">Aucun trajet enregistré.</p>
<?php else : ?>
<table class="table table-striped table-bordered table-app">
    <caption class="visually-hidden">Tous les trajets, du départ le plus récent au plus ancien</caption>
    <thead>
    <tr>
        <th scope="col">Départ</th>
        <th scope="col">Date</th>
        <th scope="col">Heure</th>
        <th scope="col">Destination</th>
        <th scope="col">Date</th>
        <th scope="col">Heure</th>
        <th scope="col">Places</th>
        <th scope="col">Auteur</th>
        <th scope="col">Statut</th>
        <th scope="col"><span class="visually-hidden">Actions</span></th>
    </tr>
    </thead>
    <tbody>
<?php foreach ($trips as $trip) : ?>
<?php $label = $trip->departureAgency->name . ' → ' . $trip->arrivalAgency->name . ' du ' . Format::date($trip->departureAt); ?>
    <tr>
        <td><?= e($trip->departureAgency->name) ?></td>
        <td><?= e(Format::date($trip->departureAt)) ?></td>
        <td><?= e(Format::time($trip->departureAt)) ?></td>
        <td><?= e($trip->arrivalAgency->name) ?></td>
        <td><?= e(Format::date($trip->arrivalAt)) ?></td>
        <td><?= e(Format::time($trip->arrivalAt)) ?></td>
        <td><?= e($trip->availableSeats) ?> / <?= e($trip->totalSeats) ?></td>
        <td><?= e($trip->author->fullName()) ?></td>
        <td>
<?php if ($trip->hasDeparted($now)) : ?>
            <span class="badge text-bg-secondary">Passé</span>
<?php elseif ($trip->isFull()) : ?>
            <span class="badge text-bg-danger">Complet</span>
<?php else : ?>
            <span class="badge text-bg-success">Ouvert</span>
<?php endif; ?>
        </td>
        <td>
            <form method="post" action="/admin/trips/<?= e($trip->id) ?>/delete" class="d-inline"
                  data-confirm="Supprimer le trajet <?= e($label) ?> ?">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-icon btn-icon-danger"
                        title="Supprimer" aria-label="Supprimer le trajet <?= e($label) ?>">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                </button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
