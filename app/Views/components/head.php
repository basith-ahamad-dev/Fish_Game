<?php
/** @var string|null $title  @var string[] $styles layout styles; views add more with View::push('styles', …) */
$styles = array_unique(array_merge($styles ?? [], \App\Core\View::stack('styles')));
$user = auth_user();
$theme = $user['theme'] ?? 'dark';
$motion = ($user['animations_enabled'] ?? 1) ? 'on' : 'off';
$appName = config('app.name');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>" data-motion="<?= $motion ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(url('/')) ?>">
    <meta name="description" content="Count the fish, climb the leaderboard and unlock achievements on <?= e($appName) ?>.">
    <meta name="theme-color" content="#06111F">
    <title><?= e(($title ?? '') !== '' ? "$title · $appName" : $appName) ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%230B1B2E'/%3E%3Cpath d='M6.5 12c3-5 9.5-6 14.5 0-5 6-11.5 5-14.5 0Z' fill='%2325D9FF'/%3E%3Cpath d='M6.5 12 2 8.5v7Z' fill='%231597E5'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?php foreach ($styles ?? [] as $sheet): ?>
        <link rel="stylesheet" href="<?= asset("css/$sheet.css") ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= asset('css/responsive.css') ?>">
</head>
