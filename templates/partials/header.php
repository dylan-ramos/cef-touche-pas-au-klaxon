<?php

/**
 * En-tête : nom de l'application et actions selon le profil connecté.
 *
 * - visiteur       : bouton de connexion ;
 * - employé        : création de trajet, identité, déconnexion ;
 * - administrateur : menu du tableau de bord, identité, déconnexion.
 *
 * @var string                $appName
 * @var \App\Entity\User|null $currentUser
 * @var string|null           $csrfToken
 */

$csrfToken ??= '';
?>
<header class="app-header navbar px-3 py-2 mb-4">
    <a class="navbar-brand app-brand" href="<?= $currentUser?->isAdmin() === true ? '/admin' : '/' ?>"><?= e($appName) ?></a>

    <nav class="d-flex align-items-center gap-3" aria-label="Navigation principale">
        <?php if ($currentUser === null) : ?>
            <a class="btn btn-dark" href="/login">Connexion</a>
        <?php else : ?>
            <?php if ($currentUser->isAdmin()) : ?>
                <a class="btn btn-secondary" href="/admin/users">Utilisateurs</a>
                <a class="btn btn-secondary" href="/admin/agencies">Agences</a>
                <a class="btn btn-secondary" href="/admin/trips">Trajets</a>
            <?php else : ?>
                <a class="btn btn-dark" href="/trips/create">Créer un trajet</a>
            <?php endif; ?>
            <span>Bonjour <?= e($currentUser->fullName()) ?></span>
            <form method="post" action="/logout" class="m-0">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-dark">Déconnexion</button>
            </form>
        <?php endif; ?>
    </nav>
</header>
