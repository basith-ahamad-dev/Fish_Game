<?php
/** @var array $badges  @var int $unlocked  @var string $filter */
$total = count($badges);
$visible = array_filter($badges, fn ($b) => $filter === 'all' || ($filter === 'unlocked') === $b['unlocked']);
?>
<?= component('page-header', [
    'eyebrow'  => 'Achievements',
    'title'    => 'Trophy cabinet',
    'subtitle' => 'Unlock badges by playing, winning and climbing the ranks.',
]) ?>

<section class="achievement-summary glass">
    <div class="achievement-summary__ring" style="--pct: <?= $total ? round($unlocked / $total * 100) : 0 ?>">
        <strong><?= $unlocked ?><small>/<?= $total ?></small></strong>
    </div>
    <div class="achievement-summary__text">
        <h2><?= $unlocked === $total && $total ? 'Every badge collected — legendary!' : 'Keep casting — ' . ($total - $unlocked) . ' badge' . ($total - $unlocked === 1 ? '' : 's') . ' to go' ?></h2>
        <?= component('progress-bar', ['percent' => $total ? $unlocked / $total * 100 : 0, 'tone' => 'gold', 'label' => 'Achievements unlocked']) ?>
    </div>
    <nav class="segmented" aria-label="Filter achievements">
        <?php foreach (['all' => 'All', 'unlocked' => 'Unlocked', 'locked' => 'Locked'] as $key => $label): ?>
            <a class="segmented__item <?= $filter === $key ? 'is-active' : '' ?>" href="<?= e(url('/achievements', $key === 'all' ? [] : ['show' => $key])) ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
</section>

<?php if ($visible): ?>
    <section class="badge-grid">
        <?php foreach ($visible as $badge): ?>
            <?= component('badge', ['badge' => $badge]) ?>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <?= component('empty-state', ['title' => 'Nothing here yet', 'message' => 'Play a few games to start filling your cabinet.', 'icon' => 'medal', 'action' => 'Play now', 'href' => url('/play')]) ?>
<?php endif; ?>
