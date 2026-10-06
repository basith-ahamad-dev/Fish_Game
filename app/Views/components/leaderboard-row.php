<?php
/** @var array $row rank, id, username, avatar, level, score, games, badges, is_me  @var bool $detached */
$rank = (int) $row['rank'];
$medal = [1 => '🥇', 2 => '🥈', 3 => '🥉'][$rank] ?? null;
?>
<tr class="lb-row <?= $rank <= 3 ? "lb-row--top lb-row--$rank" : '' ?> <?= !empty($row['is_me']) ? 'is-me' : '' ?> <?= !empty($detached) ? 'lb-row--detached' : '' ?>">
    <td class="lb-rank">
        <?php if ($medal): ?>
            <span class="lb-medal" aria-label="Rank <?= $rank ?>"><?= $medal ?></span>
        <?php else: ?>
            <span class="lb-rank__num">#<?= $rank ?></span>
        <?php endif; ?>
    </td>
    <td>
        <a class="lb-player" href="<?= e(url('/players/' . rawurlencode($row['username']))) ?>">
            <?= component('avatar', ['user' => $row, 'size' => 'sm']) ?>
            <span class="lb-player__name"><?= e($row['username']) ?></span>
            <?php if (!empty($row['is_me'])): ?><span class="tag tag--cyan">You</span><?php endif; ?>
        </a>
    </td>
    <td class="lb-level"><span class="level-pill">Lv <?= (int) $row['level'] ?></span></td>
    <td class="lb-score"><?= number($row['score']) ?></td>
    <td class="lb-hide-sm"><?= number($row['games']) ?></td>
    <td class="lb-hide-sm"><span class="lb-badges"><?= icon('medal') ?> <?= (int) $row['badges'] ?></span></td>
</tr>
