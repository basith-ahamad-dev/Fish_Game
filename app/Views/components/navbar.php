<?php $user = auth_user(); ?>
<header class="navbar" data-navbar>
    <div class="container navbar__inner">
        <?= component('logo') ?>
        <nav class="navbar__links" aria-label="Primary">
            <a href="<?= e(url('/')) ?>#how" class="navbar__link">How it works</a>
            <a href="<?= e(url('/leaderboard')) ?>" class="navbar__link <?= is_active_path('/leaderboard') ? 'is-active' : '' ?>">Leaderboard</a>
            <a href="<?= e(url('/')) ?>#modes" class="navbar__link">Modes</a>
        </nav>
        <div class="navbar__actions">
            <?php if ($user): ?>
                <?= component('button', ['label' => 'Dashboard', 'href' => url('/dashboard'), 'variant' => 'ghost', 'size' => 'sm', 'icon' => 'grid']) ?>
                <?= component('button', ['label' => 'Play', 'href' => url('/play'), 'size' => 'sm', 'icon' => 'play']) ?>
            <?php else: ?>
                <a href="<?= e(url('/login')) ?>" class="navbar__link navbar__signin">Sign in</a>
                <?= component('button', ['label' => 'Create account', 'href' => url('/register'), 'size' => 'sm']) ?>
            <?php endif; ?>
        </div>
    </div>
</header>
