<?php
/**
 * Generic modal shell. Content can be passed in or filled by app.js (confirm dialogs).
 * @var string      $id
 * @var string|null $title
 * @var string|null $body    raw HTML
 * @var string|null $footer  raw HTML
 * @var string      $class
 */
?>
<div class="modal <?= e($class ?? '') ?>" id="<?= e($id) ?>" role="dialog" aria-modal="true" aria-labelledby="<?= e($id) ?>-title" hidden>
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__panel" tabindex="-1">
        <button class="modal__close" type="button" data-modal-close aria-label="Close"><?= icon('close') ?></button>
        <h2 class="modal__title" id="<?= e($id) ?>-title"><?= e($title ?? '') ?></h2>
        <div class="modal__body"><?= $body ?? '' ?></div>
        <div class="modal__footer">
            <?= $footer ?? '<button type="button" class="btn btn--ghost btn--md" data-modal-close><span>Cancel</span></button>'
                . '<button type="button" class="btn btn--danger btn--md" data-modal-confirm><span>Confirm</span></button>' ?>
        </div>
    </div>
</div>
