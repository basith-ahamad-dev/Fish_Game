<?php /** Filled in by game.js from the server's result summary. */ ?>
<div class="modal result-modal" id="result-modal" role="dialog" aria-modal="true" aria-labelledby="result-title" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__panel result" tabindex="-1">
        <div class="result__confetti" aria-hidden="true"></div>
        <p class="result__emoji" aria-hidden="true" data-r="emoji">🎣</p>
        <h2 class="result__title" id="result-title" data-r="title">GAME COMPLETE!</h2>
        <p class="result__subtitle" data-r="subtitle"></p>

        <div class="result__score">
            <small>SCORE</small>
            <strong data-r="score">0</strong>
            <span class="result__xp" data-r="xp">+0 XP</span>
        </div>

        <p class="result__pb" data-r="pb" hidden><?= icon('sparkles') ?> NEW PERSONAL BEST!</p>

        <div class="result__grid">
            <div><small>Correct</small><strong data-r="correct">0/0</strong></div>
            <div><small>Fish caught</small><strong data-r="fish">0</strong></div>
            <div><small>Global rank</small><strong data-r="rank">—</strong></div>
        </div>

        <div class="result__level">
            <div class="result__level-head">
                <span class="level-chip">LEVEL <strong data-r="level">1</strong></span>
                <span data-r="level-numbers"></span>
            </div>
            <div class="progress progress--xp progress--md"><span class="progress__fill" data-r="level-bar" style="--progress:0%"></span></div>
            <p class="result__levelup" data-r="levelup" hidden><?= icon('bolt') ?> LEVEL UP! You reached level <strong data-r="new-level"></strong></p>
        </div>

        <div class="result__badges" data-r="badges" hidden></div>

        <div class="result__actions">
            <button class="btn btn--primary btn--lg" type="button" data-action="again"><?= icon('refresh') ?><span>PLAY AGAIN</span></button>
            <a class="btn btn--secondary btn--lg" href="<?= e(url('/dashboard')) ?>"><?= icon('grid') ?><span>DASHBOARD</span></a>
        </div>
        <a class="link result__details" data-r="details" href="#">View round-by-round details <?= icon('arrow-right', 'icon icon--xs') ?></a>
    </div>
</div>

<script type="application/json" id="badge-icons"><?= json_encode(array_combine(
    \App\Services\BadgeService::ICONS,
    array_map(fn ($name) => icon($name), \App\Services\BadgeService::ICONS)
), JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<template id="badge-unlock-template">
    <div class="unlock">
        <span class="unlock__medal"><span class="badge-medal"></span></span>
        <div>
            <small>🏆 ACHIEVEMENT UNLOCKED</small>
            <strong class="unlock__name"></strong>
            <p class="unlock__desc"></p>
        </div>
    </div>
</template>
