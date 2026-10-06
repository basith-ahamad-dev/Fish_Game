<?php
/**
 * @var string          $label
 * @var int|string      $value    numbers count up on load
 * @var string          $icon
 * @var string          $tone     blue|cyan|green|gold|violet|rose
 * @var string|null     $hint
 * @var string          $suffix
 * @var string          $prefix
 */
$tone ??= 'blue';
$isNumber = is_int($value) || is_float($value);
?>
<article class="stat-card stat-card--<?= e($tone) ?>">
    <div class="stat-card__icon"><?= icon($icon ?? 'star') ?></div>
    <div class="stat-card__body">
        <p class="stat-card__label"><?= e($label) ?></p>
        <p class="stat-card__value">
            <?= e($prefix ?? '') ?><?php if ($isNumber): ?><span data-count-to="<?= e($value) ?>"><?= number($value) ?></span><?php else: ?><?= e($value) ?><?php endif; ?><?= e($suffix ?? '') ?>
        </p>
        <?php if (!empty($hint)): ?><p class="stat-card__hint"><?= e($hint) ?></p><?php endif; ?>
    </div>
</article>
