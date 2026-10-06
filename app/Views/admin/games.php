<?php /** @var array $games  @var array $filters  @var array $pagination */ ?>
<?= component('page-header', ['eyebrow' => 'Administration', 'title' => 'Games', 'subtitle' => 'Every completed game on the platform.']) ?>

<form class="filters glass" method="get" action="<?= e(url('/admin/games')) ?>">
    <label class="filters__field filters__field--grow">
        <span>Player</span>
        <input class="input" type="search" name="player" value="<?= e($filters['player']) ?>" placeholder="Search username">
    </label>
    <label class="filters__field">
        <span>Difficulty</span>
        <select name="difficulty" class="input input--select">
            <option value="">All</option>
            <?php foreach (difficulty_config() as $key => $mode): ?>
                <option value="<?= $key ?>" <?= $filters['difficulty'] === $key ? 'selected' : '' ?>><?= e($mode['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="filters__field">
        <span>Result</span>
        <select name="result" class="input input--select">
            <option value="">All</option>
            <?php foreach (['won' => 'Won', 'lost' => 'Lost', 'abandoned' => 'Abandoned'] as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filters['result'] === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="filters__actions">
        <button class="btn btn--secondary btn--sm" type="submit"><?= icon('search') ?><span>Filter</span></button>
    </div>
</form>

<section class="card glass">
    <?= \App\Core\View::renderFile('admin/_games_table', ['games' => $games, 'actions' => true]) ?>
    <?= component('pagination', ['pagination' => $pagination, 'path' => '/admin/games', 'query' => $filters]) ?>
</section>
