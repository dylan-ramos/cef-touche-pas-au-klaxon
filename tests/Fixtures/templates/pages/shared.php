<?php /** @var string $appName */ ?>
<p><?= e($appName) ?></p><?= $view->partial('pages/hello', ['name' => 'fragment']) ?>
