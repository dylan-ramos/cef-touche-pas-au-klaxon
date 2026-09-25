<?php

/**
 * Liste des utilisateurs (lecture seule).
 *
 * @var list<\App\Entity\User> $users
 */
?>
<h1 class="h2 mb-3">Utilisateurs</h1>
<p class="text-body-secondary">Les comptes proviennent du système d'information RH et ne sont pas modifiables dans l'application.</p>

<table class="table table-striped table-bordered table-app">
    <caption class="visually-hidden">Liste des utilisateurs</caption>
    <thead>
    <tr>
        <th scope="col">Nom</th>
        <th scope="col">Prénom</th>
        <th scope="col">Email</th>
        <th scope="col">Téléphone</th>
        <th scope="col">Rôle</th>
    </tr>
    </thead>
    <tbody>
<?php foreach ($users as $listedUser) : ?>
    <tr>
        <td><?= e($listedUser->lastName) ?></td>
        <td><?= e($listedUser->firstName) ?></td>
        <td><a href="mailto:<?= e($listedUser->email) ?>"><?= e($listedUser->email) ?></a></td>
        <td><?= e($listedUser->formattedPhone()) ?></td>
        <td><?= e($listedUser->role->label()) ?></td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>
