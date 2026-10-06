<?php
/**
 * @var float       $percent
 * @var string|null $label   accessible label
 * @var string      $tone    xp|green|gold
 * @var string      $size    sm|md|lg
 */
$percent = max(0, min(100, (float) $percent));
$tone ??= 'xp';
$size ??= 'md';
?>
<div class="progress progress--<?= e($tone) ?> progress--<?= e($size) ?>" role="progressbar"
     aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $percent ?>" aria-label="<?= e($label ?? 'Progress') ?>">
    <span class="progress__fill" style="--progress: <?= $percent ?>%"></span>
</div>
