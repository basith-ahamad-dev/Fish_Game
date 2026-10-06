<?php
$user = auth_user();
$progress = player_progress();

// [path, label, icon, also-active-for]
$sections = [
    'Game' => [
        ['/dashboard', 'Dashboard', 'grid'],
        ['/play', 'Play', 'play', '/game'],
        ['/leaderboard', 'Leaderboard', 'trophy'],
        ['/achievements', 'Achievements', 'medal'],
        ['/history', 'History', 'history'],
        ['/statistics', 'Statistics', 'chart'],
    ],
    'Account' => [
        ['/profile', 'Profile', 'user'],
        ['/settings', 'Settings', 'settings'],
    ],
];
if (is_admin()) {
    $sections['Admin'] = [
        ['/admin', 'Overview', 'shield'],
        ['/admin/players', 'Players', 'users'],
        ['/admin/games', 'Games', 'history'],
        ['/admin/badges', 'Badges', 'medal'],
        ['/admin/leaderboard', 'Rankings', 'trophy'],
    ];
}
?>
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="sidebar__top">
        <?= component('logo', ['href' => url('/dashboard')]) ?>
        <button class="icon-btn sidebar__close" type="button" data-sidebar-close aria-label="Close menu"><?= icon('close') ?></button>
    </div>

    <nav class="sidebar__nav">
        <?php foreach ($sections as $heading => $links): ?>
            <p class="sidebar__label"><?= e($heading) ?></p>
            <?php foreach ($links as $link):
                [$path, $label, $icon] = $link;
                $active = is_active_path($path, $path === '/admin') || (isset($link[3]) && is_active_path($link[3]));
            ?>
                <a class="side-link <?= $active ? 'is-active' : '' ?>" href="<?= e(url($path)) ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                    <?= icon($icon) ?><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <?php if ($progress): ?>
        <a class="sidebar__player" href="<?= e(url('/profile')) ?>">
            <?= component('avatar', ['user' => $user, 'size' => 'md']) ?>
            <div class="sidebar__player-info">
                <strong><?= e($user['username']) ?></strong>
                <span>Level <?= $progress['level'] ?> · <?= number($progress['current']) ?>/<?= number($progress['needed']) ?> XP</span>
                <?= component('progress-bar', ['percent' => $progress['percent'], 'size' => 'sm', 'label' => 'XP progress']) ?>
            </div>
        </a>
    <?php endif; ?>
</aside>
<div class="sidebar-scrim" data-sidebar-close hidden></div>
