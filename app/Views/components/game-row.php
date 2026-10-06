<?php
/** Compact game list item (dashboard / profile). @var array $game  @var bool $public  no link to the owner-only result page */
$tag = empty($public) ? 'a' : 'div';
$href = empty($public) ? ' href="' . e(url('/game/result/' . (int) $game['id'])) . '"' : '';
$resultIcon = ['won' => 'trophy', 'lost' => 'wave'][$game['result']] ?? 'clock';
?>
<li class="game-item">
    <<?= $tag ?> class="game-item__link"<?= $href ?>>
        <span class="game-item__icon game-item__icon--<?= e($game['result']) ?>"><?= icon($resultIcon) ?></span>
        <span class="game-item__main">
            <span class="game-item__title"><?= component('difficulty-pill', ['difficulty' => $game['difficulty']]) ?> <?= component('result-pill', ['result' => $game['result']]) ?></span>
            <span class="game-item__meta"><?= (int) $game['correct_answers'] ?>/<?= (int) $game['rounds_played'] ?> correct · <?= e(time_ago($game['played_at'])) ?></span>
        </span>
        <span class="game-item__score">
            <strong><?= number($game['score']) ?></strong>
            <small>+<?= number($game['xp_earned']) ?> XP</small>
        </span>
    </<?= $tag ?>>
</li>
