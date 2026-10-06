<?php
/** @var array $user  @var string $section */
\App\Core\View::push('scripts', 'auth'); // password strength + show/hide helpers
$tabs = ['profile' => ['Profile', 'user'], 'security' => ['Security', 'shield'], 'preferences' => ['Preferences', 'settings']];
?>
<?= component('page-header', ['eyebrow' => 'Account', 'title' => 'Settings', 'subtitle' => 'Manage your profile, security and preferences.']) ?>

<div class="settings">
    <nav class="settings__nav glass" aria-label="Settings sections">
        <?php foreach ($tabs as $key => [$label, $ico]): ?>
            <a class="settings__link <?= $section === $key ? 'is-active' : '' ?>" href="<?= e(url('/settings', ['tab' => $key])) ?>" <?= $section === $key ? 'aria-current="page"' : '' ?>>
                <?= icon($ico) ?><span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <section class="settings__panel card glass">
        <?php if ($section === 'profile'): ?>
            <h2 class="card__title">Profile</h2>
            <p class="muted">This is how other anglers see you on leaderboards and your public profile.</p>
            <form method="post" action="<?= e(url('/settings')) ?>" enctype="multipart/form-data" class="form" data-auth-form>
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="profile">

                <div class="avatar-upload <?= error_for('avatar') ? 'has-error' : '' ?>">
                    <div class="avatar-upload__preview" data-avatar-preview>
                        <?= component('avatar', ['user' => $user, 'size' => 'xl']) ?>
                    </div>
                    <div class="avatar-upload__controls">
                        <label class="btn btn--secondary btn--sm">
                            <?= icon('edit') ?><span>Upload new avatar</span>
                            <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only" data-avatar-input>
                        </label>
                        <?php if ($user['avatar']): ?>
                            <label class="check check--sm">
                                <input type="checkbox" name="remove_avatar" value="1">
                                <span class="check__box"><?= icon('check') ?></span> Remove current avatar
                            </label>
                        <?php endif; ?>
                        <p class="field__hint">JPG, PNG, WEBP or GIF · up to <?= round(config('app.avatars.max_kb') / 1024, 1) ?> MB · cropped to a square.</p>
                        <?php if (error_for('avatar')): ?><p class="field__error" role="alert"><?= e(error_for('avatar')) ?></p><?php endif; ?>
                    </div>
                </div>

                <?= component('field', [
                    'name'  => 'username',
                    'label' => 'Username',
                    'icon'  => 'user',
                    'value' => old('username', $user['username']),
                    'attrs' => 'required minlength="3" maxlength="20" pattern="[A-Za-z0-9_]{3,20}"',
                ]) ?>
                <?= component('field', [
                    'name'  => 'bio',
                    'label' => 'Bio',
                    'type'  => 'textarea',
                    'value' => old('bio', $user['bio'] ?? ''),
                    'hint'  => 'Up to 160 characters.',
                    'attrs' => 'maxlength="160" placeholder="Hard mode or nothing. 🎣"',
                ]) ?>
                <div class="form__readonly">
                    <span class="field__label">Email</span>
                    <p><?= e($user['email']) ?></p>
                </div>
                <div class="form__actions">
                    <button class="btn btn--primary btn--md" type="submit" data-loading-text="Saving…"><?= icon('check') ?><span>Save profile</span></button>
                </div>
            </form>

        <?php elseif ($section === 'security'): ?>
            <h2 class="card__title">Change password</h2>
            <p class="muted">Changing your password signs out "remember me" sessions on other devices.</p>
            <form method="post" action="<?= e(url('/settings')) ?>" class="form" data-auth-form>
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="security">
                <?= component('field', ['name' => 'current_password', 'label' => 'Current password', 'type' => 'password', 'icon' => 'lock', 'attrs' => 'autocomplete="current-password" required']) ?>
                <?= component('field', ['name' => 'password', 'label' => 'New password', 'type' => 'password', 'icon' => 'lock', 'attrs' => 'autocomplete="new-password" required minlength="8" maxlength="72" data-strength']) ?>
                <div class="strength" data-strength-meter hidden>
                    <div class="strength__bars"><span></span><span></span><span></span><span></span></div>
                    <p class="strength__label">Use 8+ characters with upper &amp; lowercase letters and a number.</p>
                </div>
                <?= component('field', ['name' => 'password_confirmation', 'label' => 'Confirm new password', 'type' => 'password', 'icon' => 'lock', 'attrs' => 'autocomplete="new-password" required data-match="password"']) ?>
                <div class="form__actions">
                    <button class="btn btn--primary btn--md" type="submit" data-loading-text="Updating…"><?= icon('shield') ?><span>Update password</span></button>
                </div>
            </form>

        <?php else: ?>
            <h2 class="card__title">Preferences</h2>
            <p class="muted">Tune the experience to your taste.</p>
            <form method="post" action="<?= e(url('/settings')) ?>" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="preferences">

                <label class="toggle-row">
                    <span class="toggle-row__text"><strong><?= icon('sound') ?> Sound effects</strong><small>Play sounds for correct answers, misses and level-ups.</small></span>
                    <input type="checkbox" name="sound_enabled" value="1" class="switch" <?= $user['sound_enabled'] ? 'checked' : '' ?>>
                </label>
                <label class="toggle-row">
                    <span class="toggle-row__text"><strong><?= icon('sparkles') ?> Animations</strong><small>Swimming fish, bubbles and celebratory effects.</small></span>
                    <input type="checkbox" name="animations_enabled" value="1" class="switch" <?= $user['animations_enabled'] ? 'checked' : '' ?>>
                </label>

                <fieldset class="theme-picker">
                    <legend class="field__label">Theme</legend>
                    <?php foreach (['dark' => ['Deep Ocean', 'Dark, high-contrast gaming theme'], 'light' => ['Shallow Reef', 'Bright theme for daytime play']] as $key => [$label, $desc]): ?>
                        <label class="theme-option theme-option--<?= $key ?>">
                            <input type="radio" name="theme" value="<?= $key ?>" <?= $user['theme'] === $key ? 'checked' : '' ?>>
                            <span class="theme-option__swatch" aria-hidden="true"><span></span><span></span><span></span></span>
                            <span class="theme-option__text"><strong><?= $label ?></strong><small><?= $desc ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <div class="form__actions">
                    <button class="btn btn--primary btn--md" type="submit"><?= icon('check') ?><span>Save preferences</span></button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>
