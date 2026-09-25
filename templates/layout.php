<?php
/**
 * Mise en page commune à toutes les pages.
 *
 * @var string      $appName
 * @var string      $copyrightHolder
 * @var int         $currentYear
 * @var string      $content
 * @var string|null $pageTitle
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(isset($pageTitle) ? $pageTitle . ' — ' . $appName : $appName) ?></title>
</head>
<body>
<header>
    <a href="/"><?= e($appName) ?></a>
</header>
<main>
    <?= $content ?>
</main>
<footer>
    <?= e($appName) ?> — &copy; <?= e($currentYear) ?> <?= e($copyrightHolder) ?>
</footer>
</body>
</html>
