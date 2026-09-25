<?php

/**
 * Pied de page : nom de l'application et copyright.
 *
 * @var string $appName
 * @var string $copyrightHolder
 * @var int    $currentYear
 */
?>
<footer class="app-footer text-center py-3">
    <?= e($appName) ?> &middot; &copy; <?= e($currentYear) ?> <?= e($copyrightHolder) ?>
</footer>
