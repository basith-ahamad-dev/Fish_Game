<?php
/** @var array $stats  @var array $leaders */
$playHref = url(auth_user() ? '/play' : '/register');
?>
<section class="hero container">
    <div class="hero__copy">
        <p class="eyebrow reveal"><span class="pulse-dot"></span> Live now · <?= number($stats['players']) ?> anglers competing</p>
        <h1 class="hero__title reveal">
            <span>CATCH.</span>
            <span class="text-gradient">COMPETE.</span>
            <span>CONQUER.</span>
        </h1>
        <p class="hero__subtitle reveal">Test your fishing skills, climb the leaderboard,<br class="hide-sm"> unlock achievements and become the ultimate angler.</p>
        <div class="hero__actions reveal">
            <?= component('button', ['label' => 'PLAY NOW', 'href' => url('/play'), 'icon' => 'play', 'size' => 'lg']) ?>
            <?php if (!auth_user()): ?>
                <?= component('button', ['label' => 'CREATE ACCOUNT', 'href' => url('/register'), 'variant' => 'secondary', 'size' => 'lg']) ?>
            <?php else: ?>
                <?= component('button', ['label' => 'DASHBOARD', 'href' => url('/dashboard'), 'variant' => 'secondary', 'size' => 'lg', 'icon' => 'grid']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero__visual reveal" aria-hidden="true">
        <div class="preview-card">
            <div class="preview-card__bar">
                <span class="diff-pill diff-pill--hard"><span class="diff-pill__stars">★★★</span> Hard</span>
                <span class="preview-card__timer"><?= icon('clock') ?> 0:08</span>
                <span class="preview-card__lives"><?= icon('heart') ?></span>
            </div>
            <div class="preview-tank">
                <?php foreach ([[14, 22, 1.1, 0], [58, 30, .9, 1.5], [30, 58, 1.2, .7], [72, 64, .8, 2.2], [44, 40, 1, 3]] as [$x, $y, $s, $d]): ?>
                    <svg class="preview-fish" style="left:<?= $x ?>%;top:<?= $y ?>%;--s:<?= $s ?>;animation-delay:-<?= $d ?>s" viewBox="0 0 24 24"><path d="M6.5 12c3-5 9.5-6 14.5 0-5 6-11.5 5-14.5 0Z"/><path d="M6.5 12 2 8.5v7Z"/><circle cx="16.5" cy="11" r=".9" fill="#06111F"/></svg>
                <?php endforeach; ?>
                <span class="preview-chest"><?= icon('treasure') ?></span>
                <span class="preview-tank__bubble" style="left:20%"></span>
                <span class="preview-tank__bubble" style="left:66%;animation-delay:1.2s"></span>
            </div>
            <div class="preview-card__answer">
                <span>How many fish?</span>
                <div class="preview-keys">
                    <?php foreach ([3, 4, 5, 6] as $n): ?><span class="<?= $n === 5 ? 'is-picked' : '' ?>"><?= $n ?></span><?php endforeach; ?>
                </div>
            </div>
            <div class="preview-card__toast"><?= icon('check') ?> Correct! <strong>+276</strong></div>
        </div>
    </div>
</section>

<section class="container live-stats" data-live-stats aria-label="Live statistics">
    <?php foreach ([
        ['players', 'Players', 'users', 'blue'],
        ['games', 'Games Played', 'play', 'cyan'],
        ['fish', 'Fish Caught', 'fish', 'green'],
        ['high_score', 'Highest Score', 'trophy', 'gold'],
    ] as [$key, $label, $ico, $tone]): ?>
        <div class="live-stat live-stat--<?= $tone ?>">
            <span class="live-stat__icon"><?= icon($ico) ?></span>
            <strong class="live-stat__value" data-stat="<?= $key ?>" data-count-to="<?= (int) $stats[$key] ?>"><?= number($stats[$key]) ?></strong>
            <span class="live-stat__label"><?= $label ?></span>
        </div>
    <?php endforeach; ?>
</section>

<section class="container section" id="how">
    <div class="section__head">
        <p class="eyebrow">How it works</p>
        <h2 class="section__title">Three steps to the top of the reef</h2>
    </div>
    <div class="steps">
        <?php foreach ([
            ['target', 'Pick your waters', 'Choose Easy, Medium or Hard. Harder modes give you less time and fewer lives — and up to 2× score and XP.'],
            ['fish', 'Count the fish', 'Each round shows a live aquarium from the Fish Game API. Count the fish — not the treasure chests — before the clock runs out.'],
            ['trophy', 'Climb & collect', 'Earn XP, level up, unlock badges and climb the global, weekly and monthly leaderboards.'],
        ] as $i => [$ico, $heading, $text]): ?>
            <article class="step-card glass">
                <span class="step-card__num">0<?= $i + 1 ?></span>
                <span class="step-card__icon"><?= icon($ico) ?></span>
                <h3><?= $heading ?></h3>
                <p><?= $text ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section" id="modes">
    <div class="section__head">
        <p class="eyebrow">Game modes</p>
        <h2 class="section__title">Choose your challenge</h2>
    </div>
    <div class="modes">
        <?php foreach (difficulty_config() as $key => $mode): ?>
            <article class="mode-card mode-card--<?= $key ?>">
                <div class="mode-card__stars"><?= str_repeat(icon('star'), $mode['stars']) ?></div>
                <h3><?= e($mode['label']) ?></h3>
                <ul class="mode-card__facts">
                    <li><span>Rounds</span><strong><?= $mode['rounds'] ?></strong></li>
                    <li><span>Time per picture</span><strong><?= $mode['time_limit'] ?>s</strong></li>
                    <li><span>Lives</span><strong><?= $mode['lives'] ?></strong></li>
                    <li><span>Score multiplier</span><strong><?= number_format($mode['score_mult'], 1) ?>×</strong></li>
                    <li><span>XP multiplier</span><strong><?= number_format($mode['xp_mult'], 1) ?>×</strong></li>
                </ul>
                <a class="btn btn--<?= $key === 'hard' ? 'gold' : 'secondary' ?> btn--md btn--block" href="<?= e(url('/play', ['difficulty' => $key])) ?>"><span>Play <?= e($mode['label']) ?></span></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section section--split">
    <div class="glass card leaderboard-preview">
        <div class="card__head">
            <h2 class="card__title"><?= icon('trophy') ?> Top anglers</h2>
            <a class="link" href="<?= e(url('/leaderboard')) ?>">Full leaderboard <?= icon('arrow-right', 'icon icon--xs') ?></a>
        </div>
        <?php if ($leaders): ?>
            <ol class="mini-board">
                <?php foreach ($leaders as $row): ?>
                    <li class="mini-board__row">
                        <span class="mini-board__rank"><?= [1 => '🥇', 2 => '🥈', 3 => '🥉'][$row['rank']] ?? '#' . $row['rank'] ?></span>
                        <?= component('avatar', ['user' => $row, 'size' => 'sm']) ?>
                        <span class="mini-board__name"><?= e($row['username']) ?> <small>Lv <?= (int) $row['level'] ?></small></span>
                        <strong class="mini-board__score"><?= number($row['score']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <?= component('empty-state', ['title' => 'The water is calm', 'message' => 'No games played yet — be the first name on the board.', 'icon' => 'trophy']) ?>
        <?php endif; ?>
    </div>
    <div class="cta-card">
        <p class="eyebrow">Achievements</p>
        <h2>Unlock badges as you master the deep</h2>
        <div class="cta-card__badges">
            <?php foreach (['hook' => 'First Catch', 'target' => 'Sharp Angler', 'flame' => 'Hot Streak', 'crown' => 'Champion', 'bolt' => 'Speed Fisher', 'wave' => 'Ocean Master'] as $ico => $name): ?>
                <span class="badge-chip"><span class="badge-medal badge-medal--sm"><?= icon($ico) ?></span><span class="badge-chip__name"><?= $name ?></span></span>
            <?php endforeach; ?>
        </div>
        <?= component('button', ['label' => auth_user() ? 'Continue playing' : 'Start for free', 'href' => $playHref, 'icon' => 'arrow-right', 'variant' => 'gold', 'size' => 'lg']) ?>
    </div>
</section>
