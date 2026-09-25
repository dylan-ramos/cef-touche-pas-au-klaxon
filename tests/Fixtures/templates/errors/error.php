<?php /** @var string $title @var int $status @var string $message @var string|null $details */ ?>
<error status="<?= e($status) ?>" title="<?= e($title) ?>"><?= e($message) ?><?php if ($details !== null) : ?>[details]<?= e($details) ?><?php endif; ?></error>
