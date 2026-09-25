<?php

/**
 * Fenêtre modale : coordonnées de l'auteur d'un trajet (utilisateurs connectés).
 *
 * @var \App\Entity\Trip $trip
 */

use App\Core\Format;

$modalId = 'trip-' . $trip->id;
?>
<div class="modal fade" id="<?= e($modalId) ?>" tabindex="-1" aria-labelledby="<?= e($modalId) ?>-title">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="<?= e($modalId) ?>-title">
                    <?= e($trip->departureAgency->name) ?> → <?= e($trip->arrivalAgency->name) ?>,
                    <?= e(Format::date($trip->departureAt)) ?> à <?= e(Format::time($trip->departureAt)) ?>
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <dl class="mb-0">
                    <div class="mb-2"><dt class="d-inline fw-normal">Auteur :</dt> <dd class="d-inline fw-semibold"><?= e($trip->author->fullName()) ?></dd></div>
                    <div class="mb-2"><dt class="d-inline fw-normal">Téléphone :</dt> <dd class="d-inline fw-semibold"><a href="tel:<?= e($trip->author->phone) ?>"><?= e($trip->author->formattedPhone()) ?></a></dd></div>
                    <div class="mb-2"><dt class="d-inline fw-normal">Email :</dt> <dd class="d-inline fw-semibold"><a href="mailto:<?= e($trip->author->email) ?>"><?= e($trip->author->email) ?></a></dd></div>
                    <div><dt class="d-inline fw-normal">Nombre total de places :</dt> <dd class="d-inline"><?= e($trip->totalSeats) ?></dd></div>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
