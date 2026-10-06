<?php
/** @var array $tabs  @var array $board */
\App\Core\View::push('scripts', 'leaderboard');
$guest = !auth_user();
?>
<div class="<?= $guest ? 'container section' : '' ?>">
    <?= component('page-header', [
        'eyebrow'  => 'Competition',
        'title'    => 'Leaderboard',
        'subtitle' => 'The best anglers in the deep. Ties share a rank.',
        'actions'  => $guest ? component('button', ['label' => 'Join the competition', 'href' => url('/register'), 'icon' => 'arrow-right']) : component('button', ['label' => 'Play', 'href' => url('/play'), 'icon' => 'play']),
    ]) ?>

    <div class="tabs" role="tablist" aria-label="Leaderboard type" data-leaderboard-tabs data-endpoint="<?= e(url('/api/leaderboard')) ?>">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="tabs__tab <?= $board['tab'] === $key ? 'is-active' : '' ?>" role="tab" href="<?= e(url('/leaderboard', ['tab' => $key])) ?>"
               data-tab="<?= $key ?>" aria-selected="<?= $board['tab'] === $key ? 'true' : 'false' ?>"><?= e(strtoupper($label)) ?></a>
        <?php endforeach; ?>
    </div>

    <section class="card glass leaderboard" data-leaderboard>
        <?= \App\Core\View::renderFile('leaderboard/_table', ['board' => $board]) ?>
    </section>
</div>
