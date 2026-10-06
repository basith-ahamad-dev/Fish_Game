<?php
/**
 * @var string      $label
 * @var string|null $href     renders a link when set
 * @var string      $variant  primary|secondary|ghost|gold|danger
 * @var string|null $icon
 * @var string      $size     sm|md|lg
 * @var string      $type     button type when not a link
 * @var string      $attrs    extra raw attributes (already escaped by caller)
 */
$variant ??= 'primary';
$size ??= 'md';
$class = trim("btn btn--$variant btn--$size " . ($class ?? ''));
$inner = (!empty($icon) ? icon($icon) : '') . '<span>' . e($label) . '</span>';
?>
<?php if (!empty($href)): ?>
    <a class="<?= e($class) ?>" href="<?= e($href) ?>" <?= $attrs ?? '' ?>><?= $inner ?></a>
<?php else: ?>
    <button class="<?= e($class) ?>" type="<?= e($type ?? 'button') ?>" <?= $attrs ?? '' ?>><?= $inner ?></button>
<?php endif; ?>
