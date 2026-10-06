<?php
/**
 * @var array  $user  needs username, optional avatar
 * @var string $size  xs|sm|md|lg|xl
 */
$size ??= 'md';
$name = (string) ($user['username'] ?? '?');
// Stable hue per player for the initials fallback.
$hue = hexdec(substr(md5($name), 0, 2)) / 255 * 360;
?>
<span class="avatar avatar--<?= e($size) ?>" style="--avatar-hue: <?= (int) $hue ?>" title="<?= e($name) ?>">
    <?php if (!empty($user['avatar'])): ?>
        <img src="<?= e(url($user['avatar'])) ?>" alt="" loading="lazy">
    <?php else: ?>
        <span><?= e(mb_strtoupper(mb_substr($name, 0, 2))) ?></span>
    <?php endif; ?>
</span>
