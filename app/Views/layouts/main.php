<?php /** Public pages: landing, public leaderboard/profiles, errors. @var string $content */ ?>
<?= component('head', ['title' => $title ?? null, 'styles' => ['home', 'dashboard']]) ?>
<body class="site">
    <a class="skip-link" href="#main">Skip to content</a>
    <?= component('ocean') ?>
    <?= component('navbar') ?>
    <main id="main" class="site-main">
        <?= $content ?>
    </main>
    <?= component('footer') ?>
    <?= component('scripts') ?>
</body>
</html>
