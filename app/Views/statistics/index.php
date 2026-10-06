<?php
/** @var array $stats */
$t = $stats['totals'];
$seriesJson = json_encode(array_map(fn ($g) => [
    'label' => $g['date'],
    'title' => $g['date'] . ' · ' . ucfirst($g['difficulty']) . ' · ' . ucfirst((string) $g['result']),
    'value' => $g['score'],
], $stats['series']));
$activityJson = json_encode(array_map(fn ($d) => ['label' => date('j', strtotime($d['day'])), 'title' => $d['date'], 'value' => $d['games']], $stats['activity']));
?>
<?= component('page-header', [
    'eyebrow'  => 'Statistics',
    'title'    => 'Your performance',
    'subtitle' => 'Accuracy, speed and progression across every game.',
]) ?>

<section class="stat-grid stat-grid--4">
    <?= component('stat-card', ['label' => 'Total games', 'value' => $t['games'], 'icon' => 'play', 'tone' => 'cyan', 'hint' => $t['wins'] . ' won · ' . $t['win_rate'] . '% win rate']) ?>
    <?= component('stat-card', ['label' => 'Average score', 'value' => $t['avg_score'], 'icon' => 'chart', 'tone' => 'blue']) ?>
    <?= component('stat-card', ['label' => 'Best score', 'value' => $t['best_score'], 'icon' => 'trophy', 'tone' => 'gold']) ?>
    <?= component('stat-card', ['label' => 'Total XP', 'value' => $t['total_xp'], 'icon' => 'bolt', 'tone' => 'violet']) ?>
    <?= component('stat-card', ['label' => 'Current streak', 'value' => $t['current_streak'], 'icon' => 'flame', 'tone' => 'rose', 'hint' => 'Consecutive wins']) ?>
    <?= component('stat-card', ['label' => 'Best streak', 'value' => $t['best_streak'], 'icon' => 'crown', 'tone' => 'gold']) ?>
    <?= component('stat-card', ['label' => 'Accuracy', 'value' => $t['accuracy'], 'suffix' => '%', 'icon' => 'target', 'tone' => 'green', 'hint' => 'Pictures counted correctly']) ?>
    <?= component('stat-card', ['label' => 'Avg. answer time', 'value' => $t['avg_time'] !== null ? number($t['avg_time'], 1) . 's' : '—', 'icon' => 'clock', 'tone' => 'cyan', 'hint' => 'On correct answers']) ?>
</section>

<section class="card glass">
    <div class="card__head">
        <h2 class="card__title"><?= icon('chart') ?> Score progression</h2>
        <span class="tag">Last <?= count($stats['series']) ?> games</span>
    </div>
    <?php if (count($stats['series']) >= 2): ?>
        <div class="chart chart--line" data-chart="line" data-series='<?= e($seriesJson) ?>' data-unit="points"
             role="img" aria-label="Line chart of your score over your last <?= count($stats['series']) ?> games"></div>
    <?php else: ?>
        <?= component('empty-state', ['title' => 'Not enough data yet', 'message' => 'Play at least two games to see your progression.', 'icon' => 'chart', 'action' => 'Play now', 'href' => url('/play')]) ?>
    <?php endif; ?>
</section>

<section class="perf-grid">
    <?php foreach ($stats['difficulties'] as $key => $d): ?>
        <article class="perf-card glass perf-card--<?= $key ?>">
            <header class="perf-card__head">
                <?= component('difficulty-pill', ['difficulty' => $key]) ?>
                <span class="muted"><?= $d['games'] ?> game<?= $d['games'] === 1 ? '' : 's' ?></span>
            </header>
            <div class="perf-card__ring" style="--pct: <?= $d['win_rate'] ?>">
                <strong><?= $d['win_rate'] ?>%</strong><small>win rate</small>
            </div>
            <dl class="perf-card__list">
                <div><dt>Average score</dt><dd><?= number($d['avg_score']) ?></dd></div>
                <div><dt>Best score</dt><dd><?= number($d['best_score']) ?></dd></div>
                <div><dt>Accuracy</dt><dd><?= $d['accuracy'] ?>%</dd></div>
                <div><dt>Wins</dt><dd><?= $d['wins'] ?></dd></div>
            </dl>
        </article>
    <?php endforeach; ?>
</section>

<section class="card glass">
    <div class="card__head">
        <h2 class="card__title"><?= icon('calendar') ?> Activity</h2>
        <span class="tag">Last 14 days</span>
    </div>
    <div class="chart chart--bars" data-chart="bars" data-series='<?= e($activityJson) ?>' data-unit="games"
         role="img" aria-label="Games played per day over the last 14 days"></div>
</section>
