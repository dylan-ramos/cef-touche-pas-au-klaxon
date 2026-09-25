<?php

/**
 * Page d'accueil : trajets à venir disposant de places.
 *
 * @var list<\App\Entity\Trip>  $trips
 * @var \App\Entity\User|null   $user
 * @var string                  $csrfToken
 */

use App\Core\Format;

$connected = $user !== null;
?>
<h1 class="h2 mb-3"><?= $connected ? 'Trajets proposés' : 'Pour obtenir plus d\'informations sur un trajet, veuillez vous connecter' ?></h1>

<?php if ($trips === []) : ?>
<p class="alert alert-light border">Aucun trajet disponible pour le moment.</p>
<?php else : ?>
<table class="table table-striped table-bordered table-app">
    <caption class="visually-hidden">Trajets à venir disposant de places disponibles, par date de départ</caption>
    <thead>
    <tr>
        <th scope="col">Départ</th>
        <th scope="col">Date</th>
        <th scope="col">Heure</th>
        <th scope="col">Destination</th>
        <th scope="col">Date</th>
        <th scope="col">Heure</th>
        <th scope="col">Places</th>
<?php if ($connected) : ?>
        <th scope="col"><span class="visually-hidden">Actions</span></th>
<?php endif; ?>
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
        <td><?= e($trip->availableSeats) ?></td>
<?php if ($connected) : ?>
        <td class="text-nowrap">
            <button type="button" class="btn btn-icon" data-bs-toggle="modal" data-bs-target="#trip-<?= e($trip->id) ?>"
                    title="Détails" aria-label="Détails du trajet <?= e($label) ?>">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
<?php if ($trip->isAuthoredBy($user)) : ?>
            <a class="btn btn-icon" href="/trips/<?= e($trip->id) ?>/edit"
               title="Modifier" aria-label="Modifier le trajet <?= e($label) ?>">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>
            </a>
            <form method="post" action="/trips/<?= e($trip->id) ?>/delete" class="d-inline"
                  data-confirm="Supprimer le trajet <?= e($label) ?> ?">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-icon btn-icon-danger"
                        title="Supprimer" aria-label="Supprimer le trajet <?= e($label) ?>">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                </button>
            </form>
<?php endif; ?>
        </td>
<?php endif; ?>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>

<?php if ($connected) : ?>
<?php foreach ($trips as $trip) : ?>
<?= $view->partial('home/trip-modal', ['trip' => $trip]) ?>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
