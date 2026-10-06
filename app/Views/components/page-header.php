<?php
/** @var string $title  @var string|null $subtitle  @var string|null $eyebrow  @var string|null $actions raw HTML */
?>
<header class="page-header">
    <div>
        <?php if (!empty($eyebrow)): ?><p class="eyebrow"><?= e($eyebrow) ?></p><?php endif; ?>
        <h1 class="page-header__title"><?= e($title) ?></h1>
        <?php if (!empty($subtitle)): ?><p class="page-header__subtitle"><?= e($subtitle) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($actions)): ?><div class="page-header__actions"><?= $actions ?></div><?php endif; ?>
</header>
