<?php
/** @var array $games  @var array $filters  @var array $pagination */
?>
<?= component('page-header', [
    'eyebrow'  => 'History',
    'title'    => 'Game history',
    'subtitle' => 'Every game you have finished, newest first.',
]) ?>

<form class="filters glass" method="get" action="<?= e(url('/history')) ?>" data-auto-submit>
    <label class="filters__field">
        <span>Difficulty</span>
        <select name="difficulty" class="input input--select">
            <option value="">All difficulties</option>
            <?php foreach (difficulty_config() as $key => $mode): ?>
                <option value="<?= $key ?>" <?= $filters['difficulty'] === $key ? 'selected' : '' ?>><?= e($mode['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="filters__field">
        <span>Result</span>
        <select name="result" class="input input--select">
            <option value="">All results</option>
            <?php foreach (['won' => 'Won', 'lost' => 'Lost', 'abandoned' => 'Abandoned'] as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filters['result'] === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="filters__actions">
        <button class="btn btn--secondary btn--sm" type="submit"><span>Apply</span></button>
        <?php if ($filters['difficulty'] || $filters['result']): ?>
            <a class="link" href="<?= e(url('/history')) ?>">Clear</a>
        <?php endif; ?>
    </div>
</form>

<section class="card glass">
    <?php if ($games): ?>
        <div class="table-wrap">
            <table class="table table--hover">
                <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Difficulty</th>
                    <th scope="col" class="num">Score</th>
                    <th scope="col" class="num">XP</th>
                    <th scope="col">Result</th>
                    <th scope="col" class="num hide-sm">Correct</th>
                    <th scope="col" class="num">Rank</th>
                    <th scope="col"><span class="sr-only">Details</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td><span class="table__date"><?= e(date('M j, Y', strtotime($game['played_at']))) ?></span><small class="muted"><?= e(date('H:i', strtotime($game['played_at']))) ?></small></td>
                        <td><?= component('difficulty-pill', ['difficulty' => $game['difficulty']]) ?></td>
                        <td class="num strong"><?= number($game['score']) ?></td>
                        <td class="num text-cyan">+<?= number($game['xp_earned']) ?></td>
                        <td><?= component('result-pill', ['result' => $game['result']]) ?></td>
                        <td class="num hide-sm"><?= (int) $game['correct_answers'] ?>/<?= (int) $game['rounds_played'] ?></td>
                        <td class="num"><?= $game['rank_after'] ? '#' . (int) $game['rank_after'] : '—' ?></td>
                        <td class="num"><a class="icon-btn icon-btn--sm" href="<?= e(url('/game/result/' . (int) $game['id'])) ?>" aria-label="View game details"><?= icon('chevron-right') ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= component('pagination', ['pagination' => $pagination, 'path' => '/history', 'query' => $filters]) ?>
    <?php else: ?>
        <?= component('empty-state', [
            'title'   => $filters['difficulty'] || $filters['result'] ? 'No games match these filters' : 'No games yet',
            'message' => 'Finished games show up here with score, XP and rank.',
            'icon'    => 'history',
            'action'  => 'Play now',
            'href'    => url('/play'),
        ]) ?>
    <?php endif; ?>
</section>
