<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\HttpException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;

define('BASE_PATH', dirname(__DIR__));

// Let PHP's built-in dev server serve real files (php -S localhost:8000 -t public public/index.php).
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require BASE_PATH . '/app/Core/helpers.php';

Env::load(BASE_PATH . '/.env');
date_default_timezone_set(config('app.timezone', 'UTC'));

$debug = (bool) config('app.debug');
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-errors.log');

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        Logger::warning($message, ['file' => "$file:$line"]);
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$request = Request::capture();

/** Render a friendly error page (or JSON) without leaking internals. */
$renderError = function (int $status, string $message) use ($request): Response {
    if ($request->wantsJson()) {
        return Response::json(['message' => $message], $status);
    }

    $titles = [403 => 'Access denied', 404 => 'Lost at sea', 405 => 'Not allowed', 419 => 'Session expired', 500 => 'Something went wrong'];
    $html = View::render('errors/error', [
        'title'   => $titles[$status] ?? 'Error',
        'status'  => $status,
        'message' => $message,
    ], 'main');

    return Response::html($html, $status);
};

try {
    Session::start();
    AuthService::bootstrap($request);

    $router = new Router();
    require BASE_PATH . '/routes/web.php';
    require BASE_PATH . '/routes/api.php';

    $response = $router->dispatch($request);
} catch (HttpException $e) {
    $response = $renderError($e->status, $e->getMessage());
} catch (Throwable $e) {
    Logger::error($e->getMessage(), [
        'exception' => get_class($e),
        'file'      => $e->getFile() . ':' . $e->getLine(),
        'path'      => $request->path(),
        'trace'     => array_slice(explode("\n", $e->getTraceAsString()), 0, 8),
    ]);

    $message = $debug ? $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
        : 'An unexpected error occurred. Our crew has been notified — please try again.';

    try {
        $response = $renderError(500, $message);
    } catch (Throwable) {
        $response = Response::html('<h1>Something went wrong</h1><p>Please try again later.</p>', 500);
    }
}

$response
    ->withHeader('X-Content-Type-Options', 'nosniff')
    ->withHeader('X-Frame-Options', 'SAMEORIGIN')
    ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
    ->send();
