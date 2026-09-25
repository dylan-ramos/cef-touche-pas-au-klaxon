<?php

/**
 * Tableau de bord de l'administrateur.
 *
 * @var int $userCount
 * @var int $agencyCount
 * @var int $upcomingTripCount
 */

$cards = [
    ['Utilisateurs', $userCount, 'utilisateurs', '/admin/users', 'bi-people', 'Voir les utilisateurs'],
    ['Agences', $agencyCount, 'agences', '/admin/agencies', 'bi-building', 'Gérer les agences'],
    ['Trajets', $upcomingTripCount, 'trajets à venir', '/admin/trips', 'bi-signpost-split', 'Gérer les trajets'],
];
?>
<h1 class="h2 mb-4">Tableau de bord</h1>

<div class="row g-4">
<?php foreach ($cards as [$title, $count, $unit, $url, $icon, $action]) : ?>
    <div class="col-md-4">
        <section class="card h-100 border-dark-subtle">
            <div class="card-body">
                <h2 class="h5 card-title"><i class="bi <?= e($icon) ?> me-2" aria-hidden="true"></i><?= e($title) ?></h2>
                <p class="display-6 mb-0"><?= e($count) ?></p>
                <p class="text-body-secondary"><?= e($unit) ?></p>
                <a class="btn btn-primary" href="<?= e($url) ?>"><?= e($action) ?></a>
            </div>
        </section>
    </div>
<?php endforeach; ?>
</div>
