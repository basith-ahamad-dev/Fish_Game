<?php
/** @var array $user  @var array $difficulties  @var string $selected  @var int $winRate */
use App\Core\View;

View::push('styles', 'game');
View::push('scripts', 'game');
$progress = player_progress();
?>
<div class="game" data-game
     data-start-url="<?= e(url('/game/start')) ?>"
     data-round-url="<?= e(url('/game/round')) ?>"
     data-answer-url="<?= e(url('/game/answer')) ?>"
     data-result-url="<?= e(url('/game/result')) ?>"
     data-abandon-url="<?= e(url('/game/abandon')) ?>"
     data-best="<?= (int) $user['best_score'] ?>">

    <!-- ============ 1. Choose difficulty ============ -->
    <section class="game-screen game-select" data-screen="select">
        <header class="game-select__head">
            <p class="eyebrow">The Fish Game</p>
            <h1 class="game-select__title">Choose your waters</h1>
            <p class="muted">Count the fish in each aquarium before time runs out. Treasure chests don't count!</p>
        </header>

        <div class="difficulty-grid" role="radiogroup" aria-label="Difficulty">
            <?php foreach ($difficulties as $key => $mode): ?>
                <label class="difficulty-card difficulty-card--<?= $key ?>">
                    <input type="radio" name="difficulty" value="<?= $key ?>" <?= $key === $selected ? 'checked' : '' ?>>
                    <span class="difficulty-card__inner">
                        <span class="difficulty-card__check"><?= icon('check') ?></span>
                        <span class="difficulty-card__stars" aria-label="<?= $mode['stars'] ?> star difficulty"><?= str_repeat(icon('star'), $mode['stars']) ?></span>
                        <strong class="difficulty-card__name"><?= e(strtoupper($mode['label'])) ?></strong>
                        <span class="difficulty-card__facts">
                            <span><?= icon('fish') ?> <?= $mode['rounds'] ?> pictures</span>
                            <span><?= icon('clock') ?> <?= $mode['time_limit'] ?>s each</span>
                            <span><?= icon('heart') ?> <?= $mode['lives'] ?> <?= $mode['lives'] === 1 ? 'life' : 'lives' ?></span>
                        </span>
                        <span class="difficulty-card__mult">
                            <span><small>Score</small><?= number_format($mode['score_mult'], 1) ?>×</span>
                            <span><small>XP</small><?= number_format($mode['xp_mult'], 1) ?>×</span>
                        </span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="game-select__actions">
            <button class="btn btn--primary btn--xl" type="button" data-action="start">
                <?= icon('play') ?><span>Start game</span>
            </button>
            <p class="game-select__tip"><?= icon('sparkles', 'icon icon--xs') ?> Tip: type the number and press <kbd>Enter</kbd> to answer faster.</p>
        </div>
    </section>

    <!-- ============ 2. Playing ============ -->
    <section class="game-screen game-play" data-screen="play" hidden>
        <div class="hud glass">
            <div class="hud__group">
                <span data-hud="difficulty"></span>
                <span class="hud__round">Round <strong data-hud="round">1</strong>/<span data-hud="rounds">5</span></span>
            </div>
            <div class="hud__lives" data-hud="lives" aria-label="Lives remaining"></div>
            <div class="hud__timer" data-hud="timer" aria-live="off">
                <svg viewBox="0 0 36 36" aria-hidden="true"><circle class="hud__timer-track" cx="18" cy="18" r="15.5"/><circle class="hud__timer-ring" cx="18" cy="18" r="15.5" pathLength="100"/></svg>
                <span data-hud="seconds">0</span>
            </div>
            <div class="hud__score">
                <small>Score</small>
                <strong data-hud="score">0</strong>
            </div>
        </div>

        <div class="aquarium" data-aquarium>
            <img class="aquarium__img" data-puzzle alt="Aquarium picture: count the fish" hidden>
            <div class="aquarium__bubbles" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>

            <div class="aquarium__overlay aquarium__overlay--loading" data-overlay="loading">
                <div class="loader-fish" aria-hidden="true"><?= icon('fish') ?></div>
                <p>Preparing your fishing spot…</p>
            </div>

            <div class="aquarium__overlay aquarium__overlay--error" data-overlay="error" hidden role="alert">
                <div class="error-icon"><?= icon('wave') ?></div>
                <h3>Unable to connect to the game server.</h3>
                <p data-error-message>Please try again.</p>
                <button class="btn btn--primary btn--md" type="button" data-action="retry"><?= icon('refresh') ?><span>RETRY</span></button>
            </div>

            <div class="aquarium__overlay aquarium__overlay--feedback" data-overlay="feedback" hidden aria-live="assertive">
                <div class="feedback">
                    <span class="feedback__icon" data-feedback-icon></span>
                    <strong class="feedback__title" data-feedback-title></strong>
                    <span class="feedback__detail" data-feedback-detail></span>
                    <span class="feedback__points" data-feedback-points></span>
                </div>
            </div>
        </div>

        <form class="answer" data-answer-form autocomplete="off">
            <label class="answer__label" for="answer-input">How many fish?</label>
            <div class="answer__row">
                <input id="answer-input" class="answer__input" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                       placeholder="?" aria-describedby="answer-help" data-answer-input>
                <button class="btn btn--primary btn--lg answer__submit" type="submit" data-answer-submit><?= icon('hook') ?><span>Catch!</span></button>
            </div>
            <div class="keypad" aria-label="Number pad">
                <?php foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $n): ?>
                    <button type="button" class="keypad__key" data-key="<?= $n ?>"><?= $n ?></button>
                <?php endforeach; ?>
                <button type="button" class="keypad__key keypad__key--muted" data-key="clear" aria-label="Clear"><?= icon('x') ?></button>
                <button type="button" class="keypad__key" data-key="0">0</button>
                <button type="button" class="keypad__key keypad__key--muted" data-key="back" aria-label="Delete last digit"><?= icon('chevron-left') ?></button>
            </div>
            <p id="answer-help" class="sr-only">Type or tap the number of fish, then press Catch.</p>
        </form>

        <div class="game-play__footer">
            <button class="btn btn--ghost btn--sm" type="button" data-action="quit"><?= icon('logout') ?><span>Quit game</span></button>
        </div>
    </section>

    <!-- Score | Best | XP strip -->
    <section class="game-strip glass" aria-label="Your progress">
        <div class="game-strip__item"><small>Score</small><strong data-strip="score">0</strong></div>
        <div class="game-strip__item"><small>Best</small><strong data-strip="best"><?= number($user['best_score']) ?></strong></div>
        <div class="game-strip__item game-strip__item--xp">
            <small>Level <span data-strip="level"><?= $progress['level'] ?></span> · <span data-strip="xp"><?= number($progress['current']) ?></span>/<span data-strip="xp-needed"><?= number($progress['needed']) ?></span> XP</small>
            <div data-strip="xp-bar"><?= component('progress-bar', ['percent' => $progress['percent'], 'size' => 'sm', 'label' => 'XP progress']) ?></div>
        </div>
        <div class="game-strip__item"><small>Win rate</small><strong><?= $winRate ?>%</strong></div>
    </section>
</div>

<?= component('game-result-modal') ?>
<?= component('modal', [
    'id'     => 'quit-modal',
    'title'  => 'Leave this game?',
    'body'   => '<p>Your game will be recorded as <strong>abandoned</strong>. You keep XP for correct answers, but your win streak resets.</p>',
    'footer' => '<button type="button" class="btn btn--ghost btn--md" data-modal-close><span>Keep playing</span></button>'
        . '<a class="btn btn--danger btn--md" href="' . e(url('/dashboard')) . '" data-quit-confirm><span>Leave game</span></a>',
]) ?>
