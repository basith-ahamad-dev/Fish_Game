<?php /** Decorative animated background: light rays, bubbles and drifting fish silhouettes. */ ?>
<div class="ocean" aria-hidden="true">
    <div class="ocean__rays"></div>
    <div class="ocean__bubbles">
        <?php foreach ([[8, 9, 0], [18, 13, 3], [31, 11, 6], [47, 15, 2], [62, 10, 5], [74, 14, 1], [86, 12, 4], [94, 16, 7]] as [$left, $dur, $delay]): ?>
            <span style="left:<?= $left ?>%;animation-duration:<?= $dur ?>s;animation-delay:<?= $delay ?>s"></span>
        <?php endforeach; ?>
    </div>
    <svg class="ocean__fish ocean__fish--1" viewBox="0 0 24 24"><path d="M6.5 12c3-5 9.5-6 14.5 0-5 6-11.5 5-14.5 0Z"/><path d="M6.5 12 2 8.5v7Z"/></svg>
    <svg class="ocean__fish ocean__fish--2" viewBox="0 0 24 24"><path d="M6.5 12c3-5 9.5-6 14.5 0-5 6-11.5 5-14.5 0Z"/><path d="M6.5 12 2 8.5v7Z"/></svg>
    <svg class="ocean__fish ocean__fish--3" viewBox="0 0 24 24"><path d="M6.5 12c3-5 9.5-6 14.5 0-5 6-11.5 5-14.5 0Z"/><path d="M6.5 12 2 8.5v7Z"/></svg>
</div>
