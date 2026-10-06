<div class="auth-card">
    <header class="auth-card__head">
        <h1>Welcome back</h1>
        <p>Sign in to continue your fishing career.</p>
    </header>

    <form method="post" action="<?= e(url('/login')) ?>" class="form" novalidate data-auth-form>
        <?= csrf_field() ?>
        <?= component('field', [
            'name'  => 'identifier',
            'label' => 'Username or email',
            'icon'  => 'user',
            'attrs' => 'autocomplete="username" required autofocus maxlength="120"',
        ]) ?>
        <?= component('field', [
            'name'  => 'password',
            'label' => 'Password',
            'type'  => 'password',
            'icon'  => 'lock',
            'attrs' => 'autocomplete="current-password" required',
        ]) ?>

        <label class="check">
            <input type="checkbox" name="remember" value="1" <?= old('remember') ? 'checked' : '' ?>>
            <span class="check__box"><?= icon('check') ?></span>
            Remember me for <?= (int) config('app.session.remember_days') ?> days
        </label>

        <button class="btn btn--primary btn--lg btn--block" type="submit" data-loading-text="Signing in…">
            <span>Sign in</span><?= icon('arrow-right') ?>
        </button>
    </form>

    <p class="auth-card__switch">New to <?= e(config('app.name')) ?>? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
</div>
