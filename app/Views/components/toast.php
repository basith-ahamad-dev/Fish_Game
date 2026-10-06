<?php
use App\Core\Session;

$toast = Session::getFlash('toast');
?>
<div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="false">
    <?php if ($toast): ?>
        <div class="toast toast--<?= e($toast['type']) ?>" role="status" data-autodismiss>
            <span class="toast__icon"><?= icon(['success' => 'check', 'error' => 'x', 'info' => 'sparkles'][$toast['type']] ?? 'sparkles') ?></span>
            <p class="toast__msg"><?= e($toast['message']) ?></p>
            <button class="toast__close" type="button" aria-label="Dismiss"><?= icon('close') ?></button>
        </div>
    <?php endif; ?>
</div>
