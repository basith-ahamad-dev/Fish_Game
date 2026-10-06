<?php
/** @var array $summary  @var array $rounds */
$headline = match ($summary['result']) {
    'won'   => $summary['perfect'] ? 'Perfect catch!' : 'Game complete!',
    'lost'  => 'Out of lives',
    default => 'Game abandoned',
};
?>
<?= component('page-header', [
    'eyebrow'  => 'Game #' . $summary['game_id'] . ' · ' . date('M j, Y · H:i', strtotime((string) $summary['played_at'])),
    'title'    => $headline,
    'subtitle' => $summary['difficulty_label'] . ' mode · ' . $summary['correct'] . ' of ' . $summary['rounds_played'] . ' pictures counted correctly',
    'actions'  => component('button', ['label' => 'Play again', 'href' => url('/play', ['difficulty' => $summary['difficulty']]), 'icon' => 'refresh'])
        . component('button', ['label' => 'History', 'href' => url('/history'), 'variant' => 'secondary', 'icon' => 'history']),
]) ?>

<section class="result-hero glass">
    <div class="result-hero__score">
        <small>Score</small>
        <strong data-count-to="<?= $summary['score'] ?>"><?= number($summary['score']) ?></strong>
        <div class="result-hero__tags">
            <?= component('difficulty-pill', ['difficulty' => $summary['difficulty']]) ?>
            <?= component('result-pill', ['result' => $summary['result']]) ?>
            <?php if ($summary['personal_best']): ?><span class="tag tag--gold"><?= icon('sparkles', 'icon icon--xs') ?> Personal best</span><?php endif; ?>
            <?php if ($summary['perfect']): ?><span class="tag tag--green">Perfect +<?= number($summary['perfect_bonus']) ?></span><?php endif; ?>
        </div>
    </div>
    <div class="result-hero__stats">
        <div><small>XP earned</small><strong>+<?= number($summary['xp_earned']) ?></strong></div>
        <div><small>Fish caught</small><strong><?= number($summary['fish_caught']) ?></strong></div>
        <div><small>Rank after game</small><strong><?= $summary['rank'] ? '#' . $summary['rank'] : '—' ?></strong></div>
        <div><small>Level</small><strong><?= $summary['level_before'] ?><?= $summary['leveled_up'] ? ' → ' . $summary['level_after'] : '' ?></strong></div>
    </div>
</section>

<?php if ($summary['badges']): ?>
    <section class="card glass">
        <h2 class="card__title"><?= icon('medal') ?> Unlocked in this game</h2>
        <div class="badge-row">
            <?php foreach ($summary['badges'] as $badge): ?>
                <?= component('badge', ['badge' => $badge + ['unlocked' => true], 'mini' => true]) ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="card glass">
    <h2 class="card__title"><?= icon('fish') ?> Round by round</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th>Round</th><th>Fish</th><th>Your answer</th><th>Time</th><th>Result</th><th class="num">Points</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rounds as $round): ?>
                <tr>
                    <td>#<?= (int) $round['round_number'] ?></td>
                    <td><?= (int) $round['fish_count'] ?> <span class="muted">· <?= (int) $round['chest_count'] ?> chest<?= (int) $round['chest_count'] === 1 ? '' : 's' ?></span></td>
                    <td><?= $round['answer'] !== null ? (int) $round['answer'] : '<span class="muted">—</span>' ?></td>
                    <td><?= $round['answered_ms'] ? number(((int) $round['answered_ms'] - (int) $round['served_ms']) / 1000, 1) . 's' : '—' ?></td>
                    <td>
                        <?php if ($round['is_correct']): ?><span class="result-pill result-pill--won">Correct</span>
                        <?php elseif ($round['status'] === 'timeout'): ?><span class="result-pill result-pill--abandoned">Time up</span>
                        <?php else: ?><span class="result-pill result-pill--lost">Missed</span><?php endif; ?>
                    </td>
                    <td class="num"><?= number($round['points']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($summary['perfect_bonus']): ?>
                <tr class="table__total"><td colspan="5">Perfect game bonus</td><td class="num">+<?= number($summary['perfect_bonus']) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
