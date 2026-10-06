<footer class="footer">
    <div class="container footer__inner">
        <div class="footer__brand">
            <?= component('logo') ?>
            <p>A competitive platform built around Marc Conrad's
                <a href="https://marcconrad.com/uob/fish/" target="_blank" rel="noopener">Fish Game API</a>.</p>
        </div>
        <nav class="footer__links" aria-label="Footer">
            <a href="<?= e(url('/leaderboard')) ?>">Leaderboard</a>
            <a href="<?= e(url(auth_user() ? '/play' : '/register')) ?>">Play</a>
            <a href="<?= e(url(auth_user() ? '/dashboard' : '/login')) ?>"><?= auth_user() ? 'Dashboard' : 'Sign in' ?></a>
        </nav>
        <p class="footer__copy">&copy; <?= date('Y') ?> <?= e(config('app.name')) ?>. Game images &amp; puzzles by the Fish Game API.</p>
    </div>
</footer>
