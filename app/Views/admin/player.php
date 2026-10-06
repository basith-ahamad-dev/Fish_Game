<?php
/** @var array $player  @var array $progress  @var int|null $rank  @var array $games  @var array $pagination */
$isSelf = (int) $player['id'] === (int) auth_user()['id'];
?>
<?= component('page-header', [
    'eyebrow' => 'Administration · Player #' . (int) $player['id'],
    'title'   => $player['username'],
    'actions' => component('button', ['label' => 'Public profile', 'href' => url('/players/' . rawurlencode($player['username'])), 'variant' => 'secondary', 'icon' => 'eye', 'size' => 'sm'])
        . component('button', ['label' => 'All players', 'href' => url('/admin/players'), 'variant' => 'ghost', 'icon' => 'chevron-left', 'size' => 'sm']),
]) ?>

<div class="grid-2 grid-2--wide-left">
    <section class="card glass admin-player">
        <div class="admin-player__head">
            <?= component('avatar', ['user' => $player, 'size' => 'xl']) ?>
            <div>
                <h2><?= e($player['username']) ?></h2>
                <p class="muted"><?= e($player['email']) ?></p>
                <p>
                    <span class="status-pill status-pill--<?= e($player['status']) ?>"><?= e(ucfirst($player['status'])) ?></span>
                    <span class="tag <?= $player['role'] === 'admin' ? 'tag--cyan' : '' ?>"><?= e(ucfirst($player['role'])) ?></span>
                </p>
            </div>
        </div>
        <?= component('xp-panel', ['progress' => $progress]) ?>
        <dl class="kv">
            <div><dt>Global rank</dt><dd><?= $rank ? '#' . $rank : '—' ?></dd></div>
            <div><dt>Total score</dt><dd><?= number($player['total_score']) ?></dd></div>
            <div><dt>Best score</dt><dd><?= number($player['best_score']) ?></dd></div>
            <div><dt>Games (won)</dt><dd><?= number($player['games_played']) ?> (<?= number($player['games_won']) ?>)</dd></div>
            <div><dt>Fish caught</dt><dd><?= number($player['fish_caught']) ?></dd></div>
            <div><dt>Streak (best)</dt><dd><?= (int) $player['current_streak'] ?> (<?= (int) $player['best_streak'] ?>)</dd></div>
            <div><dt>Joined</dt><dd><?= e(date('M j, Y', strtotime($player['created_at']))) ?></dd></div>
            <div><dt>Last login</dt><dd><?= e(time_ago($player['last_login_at'])) ?></dd></div>
        </dl>
    </section>

    <section class="card glass">
        <h2 class="card__title"><?= icon('shield') ?> Manage account</h2>
        <?php if ($isSelf): ?>
            <p class="muted">You can't change the status or role of your own account.</p>
        <?php else: ?>
            <div class="manage">
                <div class="manage__row">
                    <div><strong>Account status</strong><p class="muted">Disabled players are signed out and cannot log in or appear on leaderboards.</p></div>
                    <form method="post" action="<?= e(url('/admin/players/' . (int) $player['id'] . '/status')) ?>"
                          data-confirm="<?= $player['status'] === 'active' ? 'Disable' : 'Enable' ?> <?= e($player['username']) ?>?">
                        <?= csrf_field() ?>
                        <?php if ($player['status'] === 'active'): ?>
                            <button class="btn btn--danger btn--sm" type="submit"><?= icon('ban') ?><span>Disable account</span></button>
                        <?php else: ?>
                            <button class="btn btn--primary btn--sm" type="submit"><?= icon('check') ?><span>Enable account</span></button>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="manage__row">
                    <div><strong>Role</strong><p class="muted">Admins can access this panel.</p></div>
                    <form method="post" action="<?= e(url('/admin/players/' . (int) $player['id'] . '/role')) ?>" class="inline-form"
                          data-confirm="Change <?= e($player['username']) ?>'s role?">
                        <?= csrf_field() ?>
                        <select name="role" class="input input--select input--sm">
                            <option value="player" <?= $player['role'] === 'player' ? 'selected' : '' ?>>Player</option>
                            <option value="admin" <?= $player['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <button class="btn btn--secondary btn--sm" type="submit"><span>Save</span></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="card glass">
    <h2 class="card__title"><?= icon('history') ?> Game history</h2>
    <?= \App\Core\View::renderFile('admin/_games_table', [
        'games'   => array_map(fn ($g) => $g + ['username' => $player['username'], 'avatar' => $player['avatar']], $games),
        'actions' => true,
    ]) ?>
    <?= component('pagination', ['pagination' => $pagination, 'path' => '/admin/players/' . (int) $player['id'], 'query' => []]) ?>
</section>
