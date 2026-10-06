<?php
/**
 * @var array $player  @var bool $isOwn  @var array $progress  @var string $levelTitle
 * @var int|null $rank  @var int $winRate  @var array $badges  @var array $recent
 */
$guest = !auth_user();
?>
<div class="<?= $guest ? 'container section' : '' ?>">
    <section class="profile-hero">
        <div class="profile-hero__banner" aria-hidden="true">
            <svg class="profile-hero__waves" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="M0 60c150-40 300 40 450 0s300-40 450 0 300 40 300 0v60H0Z"/></svg>
        </div>
        <div class="profile-hero__body">
            <div class="profile-hero__avatar">
                <?= component('avatar', ['user' => $player, 'size' => 'xxl']) ?>
                <span class="profile-hero__level">Lv <?= $progress['level'] ?></span>
            </div>
            <div class="profile-hero__info">
                <h1 class="profile-hero__name">
                    <?= e($player['username']) ?>
                    <?php if ($player['role'] === 'admin'): ?><span class="tag tag--cyan">Admin</span><?php endif; ?>
                </h1>
                <p class="profile-hero__title"><?= icon('anchor', 'icon icon--xs') ?> <?= e($levelTitle) ?> · Joined <?= e(date('F Y', strtotime($player['created_at']))) ?></p>
                <?php if ($player['bio']): ?><p class="profile-hero__bio"><?= e($player['bio']) ?></p><?php endif; ?>
            </div>
            <div class="profile-hero__rank">
                <small>Global rank</small>
                <strong><?= $rank ? '#' . $rank : '—' ?></strong>
            </div>
            <?php if ($isOwn): ?>
                <div class="profile-hero__actions">
                    <?= component('button', ['label' => 'Edit profile', 'href' => url('/settings'), 'variant' => 'secondary', 'size' => 'sm', 'icon' => 'edit']) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="profile-hero__xp">
            <?= component('xp-panel', ['progress' => $progress]) ?>
        </div>
    </section>

    <section class="stat-grid stat-grid--4" aria-label="Player stats">
        <?= component('stat-card', ['label' => 'Total score', 'value' => (int) $player['total_score'], 'icon' => 'star', 'tone' => 'blue']) ?>
        <?= component('stat-card', ['label' => 'Best score', 'value' => (int) $player['best_score'], 'icon' => 'trophy', 'tone' => 'gold']) ?>
        <?= component('stat-card', ['label' => 'Games played', 'value' => (int) $player['games_played'], 'icon' => 'play', 'tone' => 'cyan', 'hint' => $winRate . '% win rate']) ?>
        <?= component('stat-card', ['label' => 'Fish caught', 'value' => (int) $player['fish_caught'], 'icon' => 'fish', 'tone' => 'green']) ?>
        <?= component('stat-card', ['label' => 'Total XP', 'value' => (int) $player['xp'], 'icon' => 'bolt', 'tone' => 'violet']) ?>
        <?= component('stat-card', ['label' => 'Best streak', 'value' => (int) $player['best_streak'], 'icon' => 'flame', 'tone' => 'rose', 'hint' => 'Current: ' . (int) $player['current_streak']]) ?>
        <?= component('stat-card', ['label' => 'Badges', 'value' => count($badges), 'icon' => 'medal', 'tone' => 'gold']) ?>
        <?= component('stat-card', ['label' => 'Wins', 'value' => (int) $player['games_won'], 'icon' => 'crown', 'tone' => 'blue']) ?>
    </section>

    <div class="grid-2">
        <section class="card glass">
            <div class="card__head">
                <h2 class="card__title"><?= icon('medal') ?> Badges</h2>
                <?php if ($isOwn): ?><a class="link" href="<?= e(url('/achievements')) ?>">All achievements <?= icon('arrow-right', 'icon icon--xs') ?></a><?php endif; ?>
            </div>
            <?php if ($badges): ?>
                <div class="badge-row">
                    <?php foreach ($badges as $badge): ?>
                        <?= component('badge', ['badge' => $badge, 'mini' => true]) ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">No badges unlocked yet.</p>
            <?php endif; ?>
        </section>

        <section class="card glass">
            <div class="card__head">
                <h2 class="card__title"><?= icon('history') ?> Recent games</h2>
                <?php if ($isOwn): ?><a class="link" href="<?= e(url('/history')) ?>">History <?= icon('arrow-right', 'icon icon--xs') ?></a><?php endif; ?>
            </div>
            <?php if ($recent): ?>
                <ul class="game-list">
                    <?php foreach ($recent as $game): ?>
                        <?= component('game-row', ['game' => $game, 'public' => !$isOwn]) ?>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">No games played yet.</p>
            <?php endif; ?>
        </section>
    </div>
</div>
