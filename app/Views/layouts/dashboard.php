<?php
/** App shell for signed-in players: sidebar (desktop), top bar, bottom nav (mobile). @var string $content */
$user = auth_user();
?>
<?= component('head', ['title' => $title ?? null, 'styles' => ['dashboard']]) ?>
<body class="app" data-sound="<?= !empty($user['sound_enabled']) ? 'on' : 'off' ?>">
    <a class="skip-link" href="#main">Skip to content</a>
    <?= component('ocean') ?>
    <?= component('sidebar') ?>
    <div class="app__main">
        <?= component('topbar', ['title' => $title ?? '']) ?>
        <main id="main" class="page" tabindex="-1">
            <?= $content ?>
        </main>
    </div>
    <?= component('bottom-nav') ?>
    <?= component('scripts', ['scripts' => ['dashboard']]) ?>
</body>
</html>
