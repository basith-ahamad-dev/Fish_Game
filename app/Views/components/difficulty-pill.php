<?php
/** @var string $difficulty */
$config = difficulty_config($difficulty);
?>
<span class="diff-pill diff-pill--<?= e($difficulty) ?>">
    <span class="diff-pill__stars" aria-hidden="true"><?= str_repeat('★', (int) ($config['stars'] ?? 1)) ?></span>
    <?= e($config['label'] ?? ucfirst($difficulty)) ?>
</span>
