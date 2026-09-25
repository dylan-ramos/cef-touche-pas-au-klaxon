<?php

/**
 * Messages flash consécutifs à une action (création, modification, suppression…).
 *
 * @var list<array{type: string, message: string}> $messages
 */
?>
<?php foreach ($messages as $flashMessage) : ?>
    <div class="alert alert-<?= e($flashMessage['type']) ?> alert-dismissible fade show" role="<?= $flashMessage['type'] === 'danger' ? 'alert' : 'status' ?>">
        <?= e($flashMessage['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endforeach; ?>
