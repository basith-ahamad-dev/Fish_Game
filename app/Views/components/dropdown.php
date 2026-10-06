<?php
/**
 * Accessible dropdown menu.
 * @var string $id
 * @var string $trigger  raw HTML for the button content
 * @var array  $items    [['label', 'href', 'icon'] | ['divider' => true] | ['label', 'form' => url, 'icon']]
 * @var string $align    left|right
 */
?>
<div class="dropdown dropdown--<?= e($align ?? 'right') ?>" data-dropdown>
    <button class="dropdown__trigger" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="<?= e($id) ?>">
        <?= $trigger ?>
    </button>
    <div class="dropdown__menu" id="<?= e($id) ?>" role="menu" hidden>
        <?php foreach ($items as $item): ?>
            <?php if (!empty($item['divider'])): ?>
                <hr class="dropdown__divider">
            <?php elseif (!empty($item['form'])): ?>
                <form method="post" action="<?= e($item['form']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="dropdown__item" role="menuitem"><?= icon($item['icon'] ?? 'chevron-right') ?><?= e($item['label']) ?></button>
                </form>
            <?php else: ?>
                <a class="dropdown__item" role="menuitem" href="<?= e($item['href']) ?>"><?= icon($item['icon'] ?? 'chevron-right') ?><?= e($item['label']) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
