<?php
/** App header: page title, level/XP chip, play shortcut and user menu. @var string|null $title */
$user = auth_user();
$progress = player_progress();
?>
<header class="topbar">
    <button class="icon-btn topbar__menu" type="button" data-sidebar-open aria-label="Open menu" aria-controls="sidebar"><?= icon('menu') ?></button>
    <div class="topbar__brand-mobile"><?= component('logo', ['href' => url('/dashboard')]) ?></div>
    <p class="topbar__title"><?= e($title ?? '') ?></p>

    <div class="topbar__right">
        <?php if ($progress): ?>
            <div class="topbar-xp" title="<?= number($progress['remaining']) ?> XP to level <?= $progress['level'] + 1 ?>">
                <span class="level-chip level-chip--sm">LV <strong><?= $progress['level'] ?></strong></span>
                <div class="topbar-xp__bar">
                    <?= component('progress-bar', ['percent' => $progress['percent'], 'size' => 'sm', 'label' => 'XP progress']) ?>
                    <span><?= number($progress['current']) ?> / <?= number($progress['needed']) ?> XP</span>
                </div>
            </div>
            <div class="topbar-score" title="Total score">
                <?= icon('star') ?><span><?= number($user['total_score']) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!is_active_path('/play') && !is_active_path('/game')): ?>
            <?= component('button', ['label' => 'Play', 'href' => url('/play'), 'icon' => 'play', 'size' => 'sm', 'class' => 'topbar__play']) ?>
        <?php endif; ?>

        <?= component('dropdown', [
            'id'      => 'user-menu',
            'trigger' => component('avatar', ['user' => $user, 'size' => 'sm']) . icon('chevron-down', 'icon icon--xs'),
            'items'   => array_merge([
                ['label' => $user['username'] . ' · Profile', 'href' => url('/profile'), 'icon' => 'user'],
                ['label' => 'Achievements', 'href' => url('/achievements'), 'icon' => 'medal'],
                ['label' => 'Settings', 'href' => url('/settings'), 'icon' => 'settings'],
            ], is_admin() ? [['label' => 'Admin panel', 'href' => url('/admin'), 'icon' => 'shield']] : [], [
                ['divider' => true],
                ['label' => 'Sign out', 'form' => url('/logout'), 'icon' => 'logout'],
            ]),
        ]) ?>
    </div>
</header>
