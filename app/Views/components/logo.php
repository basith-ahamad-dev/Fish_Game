<?php /** @var string|null $href */ ?>
<a class="logo" href="<?= e($href ?? url('/')) ?>" aria-label="<?= e(config('app.name')) ?> home">
    <span class="logo__mark"><?= icon('fish') ?></span>
    <span class="logo__text"><?= e(config('app.name')) ?></span>
</a>
