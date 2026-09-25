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
<h1><?= e($title) ?> <small>(<?= e($status) ?>)</small></h1>
<p><?= e($message) ?></p>
<p><a href="/">Retour à l'accueil</a></p>
<?php if ($details !== null) : ?>
    <pre><?= e($details) ?></pre>
<?php endif; ?>
