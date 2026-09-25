<?php

/**
 * Liste des agences.
 *
 * @var list<\App\Entity\Agency> $agencies
 * @var string                   $csrfToken
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2 mb-0">Agences</h1>
    <a class="btn btn-primary" href="/admin/agencies/create"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nouvelle agence</a>
</div>

<?php if ($agencies === []) : ?>
<p class="alert alert-light border">Aucune agence enregistrée.</p>
<?php else : ?>
<table class="table table-striped table-bordered table-app">
    <caption class="visually-hidden">Liste des agences</caption>
    <thead>
    <tr>
        <th scope="col">Nom</th>
        <th scope="col"><span class="visually-hidden">Actions</span></th>
    </tr>
    </thead>
    <tbody>
<?php foreach ($agencies as $agency) : ?>
    <tr>
        <td><?= e($agency->name) ?></td>
        <td class="text-nowrap col-actions">
            <a class="btn btn-icon" href="/admin/agencies/<?= e($agency->id) ?>/edit"
               title="Modifier" aria-label="Modifier l'agence <?= e($agency->name) ?>">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>
            </a>
            <form method="post" action="/admin/agencies/<?= e($agency->id) ?>/delete" class="d-inline"
                  data-confirm="Supprimer l'agence <?= e($agency->name) ?> ?">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-icon btn-icon-danger"
                        title="Supprimer" aria-label="Supprimer l'agence <?= e($agency->name) ?>">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                </button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
