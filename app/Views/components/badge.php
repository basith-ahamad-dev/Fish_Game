<?php
/**
 * Achievement tile.
 * @var array $badge  name, description, icon, unlocked, unlocked_at, progress, progress_label
 * @var bool  $mini   compact chip version
 */
$unlocked = (bool) ($badge['unlocked'] ?? true);
?>
<?php if (!empty($mini)): ?>
    <div class="badge-chip <?= $unlocked ? '' : 'is-locked' ?>" title="<?= e($badge['description']) ?>">
        <span class="badge-medal badge-medal--sm"><?= icon($badge['icon']) ?></span>
        <span class="badge-chip__name"><?= e($badge['name']) ?></span>
    </div>
<?php else: ?>
    <article class="badge-card <?= $unlocked ? 'is-unlocked' : 'is-locked' ?>" data-state="<?= $unlocked ? 'unlocked' : 'locked' ?>">
        <div class="badge-medal">
            <?= icon($badge['icon']) ?>
            <?php if (!$unlocked): ?><span class="badge-medal__lock"><?= icon('lock') ?></span><?php endif; ?>
        </div>
        <h3 class="badge-card__name"><?= e($badge['name']) ?></h3>
        <p class="badge-card__desc"><?= e($badge['description']) ?></p>
        <?php if ($unlocked): ?>
            <p class="badge-card__meta badge-card__meta--done"><?= icon('check') ?> Unlocked <?= e(time_ago($badge['unlocked_at'] ?? null)) ?></p>
        <?php else: ?>
            <?= component('progress-bar', ['percent' => $badge['progress'] ?? 0, 'label' => $badge['name'] . ' progress', 'size' => 'sm', 'tone' => 'gold']) ?>
            <p class="badge-card__meta"><?= e($badge['progress_label'] ?? '') ?></p>
        <?php endif; ?>
    </article>
<?php endif; ?>
