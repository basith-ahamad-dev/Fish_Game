<?php /** @var array $tabs  @var array $board */ ?>
<?= component('page-header', [
    'eyebrow'  => 'Administration',
    'title'    => 'Leaderboard management',
    'subtitle' => 'Inspect every board. Totals are kept on each player; rebuild them from game history if anything looks off.',
    'actions'  => '<form method="post" action="' . e(url('/admin/leaderboard/recalculate')) . '" data-confirm="Rebuild totals, levels and streaks for every player from their game history?">'
        . csrf_field() . '<button class="btn btn--gold btn--md" type="submit">' . icon('refresh') . '<span>Recalculate all</span></button></form>',
]) ?>

<div class="tabs" role="tablist" aria-label="Leaderboard type">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tabs__tab <?= $board['tab'] === $key ? 'is-active' : '' ?>" role="tab" aria-selected="<?= $board['tab'] === $key ? 'true' : 'false' ?>"
           href="<?= e(url('/admin/leaderboard', ['tab' => $key])) ?>"><?= e(strtoupper($label)) ?></a>
    <?php endforeach; ?>
</div>

<section class="card glass leaderboard">
    <?= \App\Core\View::renderFile('leaderboard/_table', ['board' => $board]) ?>
</section>
