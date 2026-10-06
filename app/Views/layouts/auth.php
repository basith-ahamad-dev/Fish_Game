<?php /** Split-screen layout for sign in / register. @var string $content */ ?>
<?= component('head', ['title' => $title ?? null, 'styles' => ['auth']]) ?>
<body class="auth">
    <a class="skip-link" href="#main">Skip to form</a>
    <div class="auth__grid">
        <section class="auth__hero" aria-hidden="true">
            <?= component('ocean') ?>
            <div class="auth__hero-top"><?= component('logo') ?></div>
            <div class="auth__hero-copy">
                <p class="eyebrow">Count · Compete · Conquer</p>
                <h2>Every fish counts.<br><span class="text-gradient">Every second matters.</span></h2>
                <ul class="auth__perks">
                    <li><?= icon('bolt') ?> Three difficulty modes with up to 2× score</li>
                    <li><?= icon('trophy') ?> Global, weekly and monthly leaderboards</li>
                    <li><?= icon('medal') ?> Unlock achievements as you level up</li>
                </ul>
            </div>
            <div class="auth__hero-card">
                <div class="auth__hero-fish"><?= icon('fish') ?><?= icon('fish') ?><?= icon('fish') ?></div>
                <p>How many fish can you spot before the timer runs out?</p>
            </div>
        </section>
        <main id="main" class="auth__panel">
            <div class="auth__mobile-logo"><?= component('logo') ?></div>
            <?= $content ?>
        </main>
    </div>
    <?= component('scripts', ['scripts' => ['auth']]) ?>
</body>
</html>
