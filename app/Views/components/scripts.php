<?php
/** @var string[] $scripts layout scripts; views add more with View::push('scripts', …) */
$scripts = array_unique(array_merge($scripts ?? [], \App\Core\View::stack('scripts')));
?>
<?= component('toast') ?>
<?= component('modal', ['id' => 'confirm-modal']) ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= asset("js/$script.js") ?>" defer></script>
<?php endforeach; ?>
