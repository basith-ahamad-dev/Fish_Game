<?php /** @var array $games  @var bool $actions */ ?>
<?php if (!$games): ?>
    <?= component('empty-state', ['title' => 'No games found', 'message' => 'Completed games will appear here.', 'icon' => 'history']) ?>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table--hover">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Player</th>
                <th scope="col">Difficulty</th>
                <th scope="col" class="num">Score</th>
                <th scope="col" class="num hide-sm">XP</th>
                <th scope="col">Result</th>
                <th scope="col" class="hide-sm">Played</th>
                <?php if ($actions): ?><th scope="col"><span class="sr-only">Actions</span></th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($games as $game): ?>
                <tr>
                    <td class="muted"><?= (int) $game['id'] ?></td>
                    <td>
                        <a class="lb-player" href="<?= e(url('/admin/players/' . (int) $game['user_id'])) ?>">
                            <?= component('avatar', ['user' => $game, 'size' => 'xs']) ?><span><?= e($game['username']) ?></span>
                        </a>
                    </td>
                    <td><?= component('difficulty-pill', ['difficulty' => $game['difficulty']]) ?></td>
                    <td class="num strong"><?= number($game['score']) ?></td>
                    <td class="num hide-sm">+<?= number($game['xp_earned']) ?></td>
                    <td><?= component('result-pill', ['result' => $game['result']]) ?></td>
                    <td class="hide-sm muted"><?= e(time_ago($game['played_at'])) ?></td>
                    <?php if ($actions): ?>
                        <td class="num">
                            <form method="post" action="<?= e(url('/admin/games/' . (int) $game['id'] . '/delete')) ?>"
                                  data-confirm="Delete game #<?= (int) $game['id'] ?>? The player's totals will be recalculated.">
                                <?= csrf_field() ?>
                                <button class="icon-btn icon-btn--sm icon-btn--danger" type="submit" aria-label="Delete game #<?= (int) $game['id'] ?>"><?= icon('trash') ?></button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
