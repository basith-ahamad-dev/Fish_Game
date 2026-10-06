<?php
/** @var array $stats  @var array $recent  @var array $newest */
$perDifficulty = $stats['per_difficulty'];
$totalGames = max(1, array_sum($perDifficulty));
$activityJson = json_encode(array_map(fn ($d) => ['label' => date('j', strtotime($d['day'])), 'title' => $d['date'], 'value' => $d['games']], $stats['activity']));
?>
<?= component('page-header', [
    'eyebrow'  => 'Administration',
    'title'    => 'Platform overview',
    'subtitle' => 'Health of the reef at a glance.',
    'actions'  => component('button', ['label' => 'Manage players', 'href' => url('/admin/players'), 'icon' => 'users', 'variant' => 'secondary']),
]) ?>

<section class="stat-grid">
    <?= component('stat-card', ['label' => 'Total players', 'value' => $stats['players'], 'icon' => 'users', 'tone' => 'blue', 'hint' => $stats['disabled'] . ' disabled']) ?>
    <?= component('stat-card', ['label' => 'Games played', 'value' => $stats['games'], 'icon' => 'play', 'tone' => 'cyan']) ?>
    <?= component('stat-card', ['label' => 'Active players', 'value' => $stats['active_players'], 'icon' => 'flame', 'tone' => 'rose', 'hint' => 'Played in the last 7 days']) ?>
    <?= component('stat-card', ['label' => 'Total score', 'value' => $stats['total_score'], 'icon' => 'star', 'tone' => 'gold']) ?>
    <?= component('stat-card', ['label' => 'Most popular', 'value' => $stats['popular'], 'icon' => 'target', 'tone' => 'violet', 'hint' => 'Difficulty']) ?>
    <?= component('stat-card', ['label' => 'Fish caught', 'value' => $stats['fish'], 'icon' => 'fish', 'tone' => 'green']) ?>
</section>

<div class="grid-2">
    <section class="card glass">
        <div class="card__head"><h2 class="card__title"><?= icon('chart') ?> Games per day</h2><span class="tag">14 days</span></div>
        <div class="chart chart--bars" data-chart="bars" data-series='<?= e($activityJson) ?>' data-unit="games" role="img" aria-label="Games per day over the last 14 days"></div>
    </section>
    <section class="card glass">
        <div class="card__head"><h2 class="card__title"><?= icon('target') ?> Difficulty split</h2></div>
        <div class="split-bars">
            <?php foreach (difficulty_config() as $key => $mode): $count = $perDifficulty[$key] ?? 0; ?>
                <div class="split-bars__row">
                    <?= component('difficulty-pill', ['difficulty' => $key]) ?>
                    <div class="split-bars__track"><span class="split-bars__fill split-bars__fill--<?= $key ?>" style="width: <?= round($count / $totalGames * 100) ?>%"></span></div>
                    <strong><?= number($count) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <h3 class="card__subtitle">Newest players</h3>
        <ul class="people-list">
            <?php foreach ($newest as $p): ?>
                <li>
                    <a href="<?= e(url('/admin/players/' . (int) $p['id'])) ?>">
                        <?= component('avatar', ['user' => $p, 'size' => 'sm']) ?>
                        <span><?= e($p['username']) ?><small class="muted"> · Lv <?= (int) $p['level'] ?></small></span>
                        <small class="muted"><?= e(time_ago($p['created_at'])) ?></small>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="card glass">
    <div class="card__head">
        <h2 class="card__title"><?= icon('history') ?> Latest games</h2>
        <a class="link" href="<?= e(url('/admin/games')) ?>">All games <?= icon('arrow-right', 'icon icon--xs') ?></a>
    </div>
    <?= \App\Core\View::renderFile('admin/_games_table', ['games' => $recent, 'actions' => false]) ?>
</section>
