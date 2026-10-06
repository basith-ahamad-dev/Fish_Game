<?php
/** @var array $user  @var array $data  @var array $leaders  @var array|null $me */
$progress = $data['progress'];
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
?>
<section class="welcome glass">
    <div class="welcome__intro">
        <?= component('avatar', ['user' => $user, 'size' => 'xl']) ?>
        <div>
            <p class="eyebrow"><?= $greeting ?></p>
            <h1 class="welcome__title">Welcome back, <?= e($user['username']) ?> <span class="wave-emoji" aria-hidden="true">👋</span></h1>
            <p class="welcome__sub"><?= e($data['title']) ?><?php if ($data['rank']): ?> · Global rank <strong>#<?= $data['rank'] ?></strong><?php endif; ?></p>
        </div>
    </div>
    <div class="welcome__xp">
        <div class="level-badge" aria-label="Level <?= $progress['level'] ?>">
            <span>LEVEL</span>
            <strong><?= $progress['level'] ?></strong>
        </div>
        <div class="welcome__xp-bar">
            <?= component('xp-panel', ['progress' => $progress]) ?>
        </div>
    </div>
    <div class="welcome__cta">
        <?= component('button', ['label' => $user['games_played'] ? 'Continue playing' : 'Play your first game', 'href' => url('/play'), 'icon' => 'play', 'size' => 'lg']) ?>
    </div>
</section>

<section class="stat-grid" aria-label="Your stats">
    <?= component('stat-card', ['label' => 'Total score', 'value' => (int) $user['total_score'], 'icon' => 'star', 'tone' => 'blue']) ?>
    <?= component('stat-card', ['label' => 'Games played', 'value' => (int) $user['games_played'], 'icon' => 'play', 'tone' => 'cyan', 'hint' => (int) $user['games_won'] . ' won']) ?>
    <?= component('stat-card', ['label' => 'Best score', 'value' => (int) $user['best_score'], 'icon' => 'trophy', 'tone' => 'gold']) ?>
    <?= component('stat-card', ['label' => 'Fish caught', 'value' => (int) $user['fish_caught'], 'icon' => 'fish', 'tone' => 'green']) ?>
    <?= component('stat-card', ['label' => 'Global rank', 'value' => $data['rank'] ? '#' . $data['rank'] : '—', 'icon' => 'crown', 'tone' => 'violet', 'hint' => $data['rank'] ? null : 'Finish a game to rank']) ?>
    <?= component('stat-card', ['label' => 'Win rate', 'value' => $data['win_rate'], 'suffix' => '%', 'icon' => 'target', 'tone' => 'rose', 'hint' => 'Streak ' . (int) $user['current_streak'] . ' · best ' . (int) $user['best_streak']]) ?>
</section>

<section class="quick-play glass" aria-label="Quick play">
    <div>
        <h2 class="card__title"><?= icon('bolt') ?> Quick play</h2>
        <p class="muted">Jump straight into a mode.</p>
    </div>
    <div class="quick-play__modes">
        <?php foreach (difficulty_config() as $key => $mode): ?>
            <a class="quick-mode quick-mode--<?= $key ?>" href="<?= e(url('/play', ['difficulty' => $key])) ?>">
                <span class="quick-mode__stars"><?= str_repeat('★', $mode['stars']) ?></span>
                <strong><?= e($mode['label']) ?></strong>
                <small><?= number_format($mode['score_mult'], 1) ?>× score</small>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<div class="grid-2">
    <section class="card glass">
        <div class="card__head">
            <h2 class="card__title"><?= icon('history') ?> Recent games</h2>
            <a class="link" href="<?= e(url('/history')) ?>">View all <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <?php if ($data['recent_games']): ?>
            <ul class="game-list">
                <?php foreach ($data['recent_games'] as $game): ?>
                    <?= component('game-row', ['game' => $game]) ?>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <?= component('empty-state', ['title' => 'No games yet', 'message' => 'Your catches will appear here.', 'action' => 'Play now', 'href' => url('/play')]) ?>
        <?php endif; ?>
    </section>

    <section class="card glass">
        <div class="card__head">
            <h2 class="card__title"><?= icon('trophy') ?> Leaderboard position</h2>
            <a class="link" href="<?= e(url('/leaderboard')) ?>">Full board <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <?php if ($leaders): ?>
            <ol class="mini-board">
                <?php foreach (array_merge($leaders, $me ? [$me] : []) as $i => $row): ?>
                    <?php if ($me && $i === count($leaders)): ?><li class="mini-board__gap" aria-hidden="true">···</li><?php endif; ?>
                    <li class="mini-board__row <?= !empty($row['is_me']) ? 'is-me' : '' ?>">
                        <span class="mini-board__rank"><?= [1 => '🥇', 2 => '🥈', 3 => '🥉'][$row['rank']] ?? '#' . $row['rank'] ?></span>
                        <?= component('avatar', ['user' => $row, 'size' => 'sm']) ?>
                        <span class="mini-board__name"><?= e($row['username']) ?> <small>Lv <?= (int) $row['level'] ?></small></span>
                        <strong class="mini-board__score"><?= number($row['score']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <?= component('empty-state', ['title' => 'Board is empty', 'message' => 'Finish a game to claim the #1 spot.', 'icon' => 'trophy']) ?>
        <?php endif; ?>
    </section>
</div>

<div class="grid-2">
    <section class="card glass">
        <div class="card__head">
            <h2 class="card__title"><?= icon('medal') ?> Recent achievements</h2>
            <span class="tag"><?= $data['badge_count'] ?> / <?= $data['badge_total'] ?> unlocked</span>
        </div>
        <?php if ($data['recent_badges']): ?>
            <div class="badge-row">
                <?php foreach ($data['recent_badges'] as $badge): ?>
                    <?= component('badge', ['badge' => $badge + ['unlocked' => true], 'mini' => true]) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">No badges yet — complete your first game to earn <strong>First Catch</strong>.</p>
        <?php endif; ?>
        <?= component('progress-bar', ['percent' => $data['badge_total'] ? $data['badge_count'] / $data['badge_total'] * 100 : 0, 'tone' => 'gold', 'label' => 'Achievements unlocked']) ?>
        <a class="link link--block" href="<?= e(url('/achievements')) ?>">See all achievements <?= icon('arrow-right', 'icon icon--xs') ?></a>
    </section>

    <section class="card glass">
        <div class="card__head">
            <h2 class="card__title"><?= icon('chart') ?> This week</h2>
            <span class="tag"><?= array_sum(array_column($data['activity'], 'games')) ?> games</span>
        </div>
        <div class="chart chart--bars" data-chart="bars"
             data-series='<?= e(json_encode(array_map(fn ($d) => ['label' => $d['label'], 'title' => $d['date'], 'value' => $d['games']], $data['activity']))) ?>'
             data-unit="games" role="img" aria-label="Games played per day this week"></div>
    </section>
</div>
