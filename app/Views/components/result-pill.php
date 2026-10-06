<?php
/** @var string|null $result won|lost|abandoned */
$labels = ['won' => 'Won', 'lost' => 'Lost', 'abandoned' => 'Abandoned'];
$result ??= 'abandoned';
?>
<span class="result-pill result-pill--<?= e($result) ?>"><?= e($labels[$result] ?? ucfirst($result)) ?></span>
