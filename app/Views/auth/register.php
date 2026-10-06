<div class="auth-card">
    <header class="auth-card__head">
        <h1>Create your account</h1>
        <p>Join the leaderboard in under a minute.</p>
    </header>

    <form method="post" action="<?= e(url('/register')) ?>" class="form" novalidate data-auth-form>
        <?= csrf_field() ?>
        <?= component('field', [
            'name'  => 'username',
            'label' => 'Username',
            'icon'  => 'user',
            'hint'  => '3–20 letters, numbers or underscores.',
            'attrs' => 'autocomplete="username" required minlength="3" maxlength="20" pattern="[A-Za-z0-9_]{3,20}" autofocus',
        ]) ?>
        <?= component('field', [
            'name'  => 'email',
            'label' => 'Email',
            'type'  => 'email',
            'icon'  => 'sparkles',
            'attrs' => 'autocomplete="email" required maxlength="120"',
        ]) ?>
        <?= component('field', [
            'name'  => 'password',
            'label' => 'Password',
            'type'  => 'password',
            'icon'  => 'lock',
            'attrs' => 'autocomplete="new-password" required minlength="8" maxlength="72" data-strength',
        ]) ?>
        <div class="strength" data-strength-meter hidden>
            <div class="strength__bars"><span></span><span></span><span></span><span></span></div>
            <p class="strength__label">Use 8+ characters with upper &amp; lowercase letters and a number.</p>
        </div>
        <?= component('field', [
            'name'  => 'password_confirmation',
            'label' => 'Confirm password',
            'type'  => 'password',
            'icon'  => 'lock',
            'attrs' => 'autocomplete="new-password" required data-match="password"',
        ]) ?>

        <button class="btn btn--primary btn--lg btn--block" type="submit" data-loading-text="Creating account…">
            <span>Create account</span><?= icon('arrow-right') ?>
        </button>
    </form>

    <p class="auth-card__switch">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
</div>
