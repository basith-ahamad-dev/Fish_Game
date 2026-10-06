<?php
/** Leaderboard body: podium + table. Rendered on page load and by /api/leaderboard. @var array $board */
$rows = $board['rows'];
$podium = array_slice($rows, 0, 3);
?>
<div class="leaderboard__meta">
    <span class="tag"><?= icon('calendar', 'icon icon--xs') ?> <?= e($board['note']) ?></span>
    <span class="muted"><?= count($rows) ?> ranked</span>
</div>

<?php if (!$rows): ?>
    <?= component('empty-state', [
        'title'   => 'No catches here yet',
        'message' => 'Nobody has scored in this board yet. Be the first!',
        'icon'    => 'trophy',
        'action'  => auth_user() ? 'Play now' : null,
        'href'    => url('/play'),
    ]) ?>
<?php else: ?>
    <div class="podium" aria-hidden="true">
        <?php foreach ([1, 0, 2] as $i): if (!isset($podium[$i])) continue; $p = $podium[$i]; ?>
            <div class="podium__place podium__place--<?= $p['rank'] ?> <?= $p['is_me'] ? 'is-me' : '' ?>">
                <span class="podium__medal"><?= [1 => '🥇', 2 => '🥈', 3 => '🥉'][$p['rank']] ?? '#' . $p['rank'] ?></span>
                <?= component('avatar', ['user' => $p, 'size' => $p['rank'] === 1 ? 'xl' : 'lg']) ?>
                <strong class="podium__name"><?= e($p['username']) ?></strong>
                <span class="podium__score"><?= number($p['score']) ?></span>
                <div class="podium__block"><span>#<?= $p['rank'] ?></span></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table class="table lb-table">
            <thead>
            <tr>
                <th scope="col">Rank</th>
                <th scope="col">Player</th>
                <th scope="col">Level</th>
                <th scope="col" class="num">Score</th>
                <th scope="col" class="lb-hide-sm">Games</th>
                <th scope="col" class="lb-hide-sm">Badges</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?= component('leaderboard-row', ['row' => $row]) ?>
            <?php endforeach; ?>
            <?php if ($board['me']): ?>
                <tr class="lb-gap" aria-hidden="true"><td colspan="6">···</td></tr>
                <?= component('leaderboard-row', ['row' => $board['me'], 'detached' => true]) ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
