<?php
/**
 * Labelled form input with inline error.
 * @var string      $name
 * @var string      $label
 * @var string      $type
 * @var string|null $value
 * @var string|null $icon
 * @var string|null $hint
 * @var string      $attrs  extra raw attributes
 */
$type ??= 'text';
$id = 'f-' . $name;
$error = error_for($name);
$value = $type === 'password' ? '' : ($value ?? old($name));
?>
<div class="field <?= $error ? 'has-error' : '' ?>">
    <label class="field__label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <div class="field__control">
        <?php if (!empty($icon)): ?><span class="field__icon"><?= icon($icon) ?></span><?php endif; ?>
        <?php if ($type === 'textarea'): ?>
            <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" class="input" rows="3" <?= $attrs ?? '' ?>><?= e($value) ?></textarea>
        <?php else: ?>
            <input id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" class="input <?= !empty($icon) ? 'input--icon' : '' ?>"
                   value="<?= e($value) ?>" <?= $error ? 'aria-invalid="true" aria-describedby="' . e($id) . '-error"' : '' ?> <?= $attrs ?? '' ?>>
        <?php endif; ?>
        <?php if ($type === 'password'): ?>
            <button type="button" class="field__toggle" data-toggle-password aria-label="Show password"><?= icon('eye') ?></button>
        <?php endif; ?>
    </div>
    <?php if ($error): ?>
        <p class="field__error" id="<?= e($id) ?>-error" role="alert"><?= e($error) ?></p>
    <?php elseif (!empty($hint)): ?>
        <p class="field__hint"><?= e($hint) ?></p>
    <?php endif; ?>
</div>
