<?php
/** @var array $badges  @var array|null $editing  @var array $requirements  @var array $icons */
$form = $editing ?? [];
$val = fn (string $key, $default = '') => old($key, $form[$key] ?? $default);
?>
<?= component('page-header', ['eyebrow' => 'Administration', 'title' => 'Badges', 'subtitle' => 'Create and tune achievements. Players unlock them automatically after each game.']) ?>

<div class="grid-2 grid-2--wide-left">
    <section class="card glass">
        <h2 class="card__title"><?= icon('medal') ?> All badges</h2>
        <div class="table-wrap">
            <table class="table table--hover">
                <thead>
                <tr><th scope="col">Badge</th><th scope="col" class="hide-sm">Requirement</th><th scope="col" class="num">Unlocked</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody>
                <?php foreach ($badges as $badge): ?>
                    <tr class="<?= ($editing['id'] ?? null) === $badge['id'] ? 'is-selected' : '' ?>">
                        <td>
                            <div class="badge-cell">
                                <span class="badge-medal badge-medal--sm"><?= icon($badge['icon']) ?></span>
                                <span><strong><?= e($badge['name']) ?></strong><small class="muted"><?= e($badge['description']) ?></small></span>
                            </div>
                        </td>
                        <td class="hide-sm"><?= e($requirements[$badge['requirement_type']] ?? $badge['requirement_type']) ?>: <strong><?= number($badge['requirement_value']) ?></strong></td>
                        <td class="num"><?= number($badge['unlocked_count']) ?></td>
                        <td class="num">
                            <div class="row-actions">
                                <a class="icon-btn icon-btn--sm" href="<?= e(url('/admin/badges', ['edit' => (int) $badge['id']])) ?>" aria-label="Edit <?= e($badge['name']) ?>"><?= icon('edit') ?></a>
                                <form method="post" action="<?= e(url('/admin/badges/' . (int) $badge['id'] . '/delete')) ?>"
                                      data-confirm="Delete the &quot;<?= e($badge['name']) ?>&quot; badge? Players will lose it.">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn icon-btn--sm icon-btn--danger" type="submit" aria-label="Delete <?= e($badge['name']) ?>"><?= icon('trash') ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card glass">
        <div class="card__head">
            <h2 class="card__title"><?= icon($editing ? 'edit' : 'plus') ?> <?= $editing ? 'Edit badge' : 'New badge' ?></h2>
            <?php if ($editing): ?><a class="link" href="<?= e(url('/admin/badges')) ?>">Cancel</a><?php endif; ?>
        </div>
        <form method="post" action="<?= e(url('/admin/badges')) ?>" class="form">
            <?= csrf_field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
            <?= component('field', ['name' => 'name', 'label' => 'Name', 'value' => $val('name'), 'attrs' => 'required maxlength="60"']) ?>
            <?= component('field', ['name' => 'description', 'label' => 'Description', 'value' => $val('description'), 'attrs' => 'required maxlength="255"']) ?>

            <div class="field <?= error_for('requirement_type') ? 'has-error' : '' ?>">
                <label class="field__label" for="f-requirement_type">Requirement</label>
                <select id="f-requirement_type" name="requirement_type" class="input input--select" required>
                    <?php foreach ($requirements as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $val('requirement_type') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (error_for('requirement_type')): ?><p class="field__error"><?= e(error_for('requirement_type')) ?></p><?php endif; ?>
            </div>
            <?= component('field', ['name' => 'requirement_value', 'label' => 'Target value', 'type' => 'number', 'value' => (string) $val('requirement_value', 1), 'hint' => 'For "Global rank" this is the top-N cut-off.', 'attrs' => 'required min="1"']) ?>
            <?= component('field', ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'value' => (string) $val('sort_order', 0)]) ?>

            <fieldset class="icon-picker <?= error_for('icon') ? 'has-error' : '' ?>">
                <legend class="field__label">Icon</legend>
                <?php foreach ($icons as $ico): ?>
                    <label class="icon-picker__option" title="<?= e($ico) ?>">
                        <input type="radio" name="icon" value="<?= e($ico) ?>" <?= $val('icon', 'medal') === $ico ? 'checked' : '' ?>>
                        <span class="badge-medal badge-medal--sm"><?= icon($ico) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <div class="form__actions">
                <button class="btn btn--primary btn--md" type="submit"><?= icon('check') ?><span><?= $editing ? 'Save changes' : 'Create badge' ?></span></button>
            </div>
        </form>
    </section>
</div>
