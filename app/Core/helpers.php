<?php

use App\Core\Env;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Services\LevelService;

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

/** Read config with dot notation: config('app.session.name'). */
function config(string $key, mixed $default = null): mixed
{
    static $cache = [];

    $segments = explode('.', $key);
    $file = array_shift($segments);
    if (!array_key_exists($file, $cache)) {
        $path = BASE_PATH . "/config/$file.php";
        $cache[$file] = is_file($path) ? require $path : [];
    }

    $value = $cache[$file];
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/', array $query = []): string
{
    $url = Request::basePath() . '/' . ltrim($path, '/');
    if ($url === '') {
        $url = '/';
    }

    return $query ? $url . '?' . http_build_query($query) : $url;
}

/** Public asset URL with a cache-busting version. */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $version = is_file($file) ? filemtime($file) : '1';

    return url('assets/' . ltrim($path, '/')) . '?v=' . $version;
}

function csrf_token(): string
{
    return Session::csrfToken();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::getFlash('old', []);

    return $old[$key] ?? $default;
}

function error_for(string $field): ?string
{
    return Session::getFlash('errors', [])[$field] ?? null;
}

function auth_user(): ?array
{
    return AuthService::user();
}

function is_admin(): bool
{
    return (auth_user()['role'] ?? null) === 'admin';
}

/** Render a reusable view component from app/Views/components. */
function component(string $name, array $data = []): string
{
    return View::renderFile('components/' . $name, $data);
}

function icon(string $name, string $class = 'icon'): string
{
    static $icons = null;
    $icons ??= require BASE_PATH . '/app/Views/components/icons.php';
    $paths = $icons[$name] ?? $icons['fish'];

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

function number(int|float|null $value, int $decimals = 0): string
{
    return number_format((float) $value, $decimals);
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }

    $diff = time() - strtotime($datetime);

    return match (true) {
        $diff < 60      => 'just now',
        $diff < 3600    => floor($diff / 60) . 'm ago',
        $diff < 86400   => floor($diff / 3600) . 'h ago',
        $diff < 604800  => floor($diff / 86400) . 'd ago',
        default         => date('M j, Y', strtotime($datetime)),
    };
}

function difficulty_config(?string $key = null): array
{
    $all = config('game.difficulties');

    return $key === null ? $all : ($all[$key] ?? []);
}

/** @return array{total:int, page:int, per_page:int, pages:int, from:int, to:int} */
function paginate(int $total, int $page, int $perPage): array
{
    $pages = max(1, (int) ceil($total / $perPage));

    return [
        'total'    => $total,
        'page'     => min($page, $pages),
        'per_page' => $perPage,
        'pages'    => $pages,
        'from'     => $total ? ($page - 1) * $perPage + 1 : 0,
        'to'       => min($total, $page * $perPage),
    ];
}

function stars(int $count, int $max = 3): string
{
    return str_repeat('★', $count) . str_repeat('☆', $max - $count);
}

/** Level and XP-bar data for the signed-in player, used by the app shell. */
function player_progress(): ?array
{
    $user = auth_user();

    return $user ? (new LevelService())->getXPProgress((int) $user['xp']) : null;
}

function current_path(): string
{
    static $path = null;

    return $path ??= (new Request($_GET, [], $_SERVER, [], []))->path();
}

function is_active_path(string $prefix, bool $exact = false): bool
{
    $path = current_path();

    if ($exact || $prefix === '/') {
        return $path === $prefix;
    }

    return $path === $prefix || str_starts_with($path, $prefix . '/');
}
