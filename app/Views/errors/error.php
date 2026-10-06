<?php /** @var int $status  @var string $title  @var string $message */ ?>
<section class="error-page container">
    <div class="error-page__art" aria-hidden="true">
        <span class="error-page__code"><?= (int) $status ?></span>
        <span class="error-page__fish"><?= icon('fish') ?></span>
    </div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e($message) ?></p>
    <div class="error-page__actions">
        <?php if ($status === 419): ?>
            <button class="btn btn--primary btn--md" type="button" data-history-back><?= icon('refresh') ?><span>Go back &amp; retry</span></button>
        <?php else: ?>
            <?= component('button', ['label' => 'Back to safety', 'href' => url(auth_user() ? '/dashboard' : '/'), 'icon' => 'home']) ?>
        <?php endif; ?>
        <?= component('button', ['label' => 'Leaderboard', 'href' => url('/leaderboard'), 'variant' => 'secondary', 'icon' => 'trophy']) ?>
    </div>
</section>
