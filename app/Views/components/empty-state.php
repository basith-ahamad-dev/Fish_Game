<?php
/** @var string $title  @var string $message  @var string|null $icon  @var string|null $action  @var string|null $href */
?>
<div class="empty-state">
    <div class="empty-state__icon"><?= icon($icon ?? 'fish') ?></div>
    <h3><?= e($title) ?></h3>
    <p><?= e($message) ?></p>
    <?php if (!empty($action) && !empty($href)): ?>
        <?= component('button', ['label' => $action, 'href' => $href, 'icon' => 'play', 'size' => 'sm']) ?>
    <?php endif; ?>
</div>
