<?php /** @var array $players  @var array $filters  @var array $pagination */ ?>
<?= component('page-header', ['eyebrow' => 'Administration', 'title' => 'Players', 'subtitle' => 'Search, review and manage player accounts.']) ?>

<form class="filters glass" method="get" action="<?= e(url('/admin/players')) ?>">
    <label class="filters__field filters__field--grow">
        <span>Search</span>
        <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Username or email">
    </label>
    <label class="filters__field">
        <span>Status</span>
        <select name="status" class="input input--select">
            <option value="">All</option>
            <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="disabled" <?= $filters['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option>
        </select>
    </label>
    <label class="filters__field">
        <span>Role</span>
        <select name="role" class="input input--select">
            <option value="">All</option>
            <option value="player" <?= $filters['role'] === 'player' ? 'selected' : '' ?>>Player</option>
            <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </label>
    <div class="filters__actions">
        <button class="btn btn--secondary btn--sm" type="submit"><?= icon('search') ?><span>Search</span></button>
    </div>
</form>

<section class="card glass">
    <?php if ($players): ?>
        <div class="table-wrap">
            <table class="table table--hover">
                <thead>
                <tr>
                    <th scope="col">Player</th>
                    <th scope="col" class="hide-sm">Email</th>
                    <th scope="col">Level</th>
                    <th scope="col" class="num">Score</th>
                    <th scope="col" class="num hide-sm">Games</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="hide-sm">Joined</th>
                    <th scope="col"><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($players as $p): $isSelf = (int) $p['id'] === (int) auth_user()['id']; ?>
                    <tr>
                        <td>
                            <a class="lb-player" href="<?= e(url('/admin/players/' . (int) $p['id'])) ?>">
                                <?= component('avatar', ['user' => $p, 'size' => 'sm']) ?>
                                <span class="lb-player__name"><?= e($p['username']) ?></span>
                                <?php if ($p['role'] === 'admin'): ?><span class="tag tag--cyan">Admin</span><?php endif; ?>
                            </a>
                        </td>
                        <td class="hide-sm muted"><?= e($p['email']) ?></td>
                        <td><span class="level-pill">Lv <?= (int) $p['level'] ?></span></td>
                        <td class="num strong"><?= number($p['total_score']) ?></td>
                        <td class="num hide-sm"><?= number($p['games_played']) ?></td>
                        <td><span class="status-pill status-pill--<?= e($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                        <td class="hide-sm muted"><?= e(date('M j, Y', strtotime($p['created_at']))) ?></td>
                        <td class="num">
                            <div class="row-actions">
                                <a class="icon-btn icon-btn--sm" href="<?= e(url('/admin/players/' . (int) $p['id'])) ?>" aria-label="View <?= e($p['username']) ?>"><?= icon('eye') ?></a>
                                <?php if (!$isSelf): ?>
                                    <form method="post" action="<?= e(url('/admin/players/' . (int) $p['id'] . '/status')) ?>"
                                          data-confirm="<?= $p['status'] === 'active' ? 'Disable' : 'Enable' ?> <?= e($p['username']) ?>?">
                                        <?= csrf_field() ?>
                                        <button class="icon-btn icon-btn--sm <?= $p['status'] === 'active' ? 'icon-btn--danger' : '' ?>" type="submit"
                                                aria-label="<?= $p['status'] === 'active' ? 'Disable' : 'Enable' ?> <?= e($p['username']) ?>"><?= icon($p['status'] === 'active' ? 'ban' : 'check') ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= component('pagination', ['pagination' => $pagination, 'path' => '/admin/players', 'query' => $filters]) ?>
    <?php else: ?>
        <?= component('empty-state', ['title' => 'No players found', 'message' => 'Try a different search or filter.', 'icon' => 'users']) ?>
    <?php endif; ?>
</section>
