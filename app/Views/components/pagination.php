<?php
/** @var array $pagination from paginate()  @var string $path  @var array $query current filters */
if ($pagination['pages'] <= 1) {
    return;
}
$query = array_filter($query ?? [], fn ($v) => $v !== null && $v !== '');
$link = fn (int $page) => url($path, $query + ['page' => $page]);
$current = $pagination['page'];
$window = array_unique(array_filter(
    [1, $current - 1, $current, $current + 1, $pagination['pages']],
    fn ($p) => $p >= 1 && $p <= $pagination['pages']
));
sort($window);
?>
<nav class="pagination" aria-label="Pagination">
    <p class="pagination__info">Showing <?= $pagination['from'] ?>–<?= $pagination['to'] ?> of <?= number($pagination['total']) ?></p>
    <div class="pagination__pages">
        <?php if ($current > 1): ?>
            <a class="pagination__btn" href="<?= e($link($current - 1)) ?>" aria-label="Previous page"><?= icon('chevron-left') ?></a>
        <?php endif; ?>
        <?php $prev = 0; foreach ($window as $p): ?>
            <?php if ($p - $prev > 1): ?><span class="pagination__gap">…</span><?php endif; ?>
            <a class="pagination__btn <?= $p === $current ? 'is-active' : '' ?>" href="<?= e($link($p)) ?>" <?= $p === $current ? 'aria-current="page"' : '' ?>><?= $p ?></a>
        <?php $prev = $p; endforeach; ?>
        <?php if ($current < $pagination['pages']): ?>
            <a class="pagination__btn" href="<?= e($link($current + 1)) ?>" aria-label="Next page"><?= icon('chevron-right') ?></a>
        <?php endif; ?>
    </div>
</nav>
