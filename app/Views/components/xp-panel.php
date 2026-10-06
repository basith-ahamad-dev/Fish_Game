<?php
/**
 * Level + XP bar block.
 * @var array       $progress  from LevelService::getXPProgress
 * @var string|null $title     rank title
 * @var bool        $compact
 */
?>
<div class="xp-panel <?= !empty($compact) ? 'xp-panel--compact' : '' ?>">
    <div class="xp-panel__head">
        <span class="level-chip">LEVEL <strong><?= (int) $progress['level'] ?></strong></span>
        <?php if (!empty($title)): ?><span class="xp-panel__title"><?= e($title) ?></span><?php endif; ?>
        <span class="xp-panel__numbers"><strong><?= number($progress['current']) ?></strong> / <?= number($progress['needed']) ?> XP</span>
    </div>
    <?= component('progress-bar', ['percent' => $progress['percent'], 'label' => 'Experience toward next level', 'size' => !empty($compact) ? 'sm' : 'lg']) ?>
    <?php if (empty($compact)): ?>
        <p class="xp-panel__hint"><?= number($progress['remaining']) ?> XP to level <?= (int) $progress['level'] + 1 ?></p>
    <?php endif; ?>
</div>
