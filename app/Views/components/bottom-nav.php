<?php
$items = [
    ['/dashboard', 'Home', 'grid'],
    ['/leaderboard', 'Ranks', 'trophy'],
    ['/play', 'Play', 'play'],
    ['/achievements', 'Badges', 'medal'],
    ['/profile', 'Profile', 'user'],
];
?>
<nav class="bottom-nav" aria-label="Quick navigation">
    <?php foreach ($items as [$path, $label, $icon]):
        $active = is_active_path($path) || ($path === '/play' && is_active_path('/game')); ?>
        <a class="bottom-nav__item <?= $path === '/play' ? 'bottom-nav__item--play' : '' ?> <?= $active ? 'is-active' : '' ?>"
           href="<?= e(url($path)) ?>" <?= $active ? 'aria-current="page"' : '' ?>>
            <span class="bottom-nav__icon"><?= icon($icon) ?></span>
            <span class="bottom-nav__label"><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
