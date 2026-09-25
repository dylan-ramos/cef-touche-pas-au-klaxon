<?php

/**
 * Mise en page commune à toutes les pages.
 *
 * @var \App\Core\View              $view
 * @var string                      $appName
 * @var string                      $copyrightHolder
 * @var int                         $currentYear
 * @var \App\Core\Session\Flash     $flash
 * @var \App\Entity\User|null       $currentUser
 * @var string                      $content
 * @var string|null                 $pageTitle
 */

$currentUser ??= null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(isset($pageTitle) ? $pageTitle . ' — ' . $appName : $appName) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="d-flex flex-column min-vh-100">
<div class="container flex-grow-1 py-3">
    <?= $view->partial('partials/header', ['currentUser' => $currentUser]) ?>
    <?= $view->partial('partials/flash', ['messages' => $flash->consume()]) ?>
    <main id="contenu">
        <?= $content ?>
    </main>
</div>
<?= $view->partial('partials/footer') ?>
<script src="/assets/js/vendor/bootstrap.bundle.min.js"></script>
</body>
</html>
