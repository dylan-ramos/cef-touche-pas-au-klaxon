<?php

/**
 * Page d'erreur générique.
 *
 * @var string      $title
 * @var int         $status
 * @var string      $message
 * @var string|null $details Détail technique, présent uniquement en mode débogage.
 */
?>
<div class="py-4">
    <h1 class="h2 mb-3"><?= e($title) ?> <small class="text-body-secondary">(<?= e($status) ?>)</small></h1>
    <p class="lead"><?= e($message) ?></p>
    <a class="btn btn-primary" href="/">Retour à l'accueil</a>
    <?php if ($details !== null) : ?>
        <pre class="mt-4 p-3 bg-light border rounded small"><?= e($details) ?></pre>
    <?php endif; ?>
</div>
