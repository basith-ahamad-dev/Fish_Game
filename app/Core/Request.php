<?php

namespace App\Core;

final class Request
{
    private static ?string $basePath = null;

    private array $params = [];

    public function __construct(
        private array $query,
        private array $post,
        private array $server,
        private array $files,
        private array $cookies,
        private ?array $json = null,
    ) {
    }

    public static function capture(): self
    {
        $json = null;
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $decoded = json_decode(file_get_contents('php://input') ?: '', true);
            $json = is_array($decoded) ? $decoded : [];
        }

        return new self($_GET, $_POST, $_SERVER, $_FILES, $_COOKIE, $json);
    }

    /**
     * URL prefix the app is served under, e.g. "" or "/fish-game".
     * Handles both a document root pointing at /public and the root .htaccess fallback.
     */
    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $configured = (string) config('app.url');
        if ($configured !== '') {
            return self::$basePath = rtrim((string) parse_url($configured, PHP_URL_PATH), '/');
        }

        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $script = rtrim($script, '/');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if ($script !== '' && !str_starts_with($uri, $script) && str_ends_with($script, '/public')) {
            $script = substr($script, 0, -strlen('/public'));
        }

        return self::$basePath = $script;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function path(): string
    {
        $path = rawurldecode(parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = self::basePath();

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
        }

        $path = '/' . trim($path, '/');

        return $path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->json[$key] ?? $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json ?? []);
    }

    public function only(array $keys): array
    {
        $all = $this->all();
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $all[$key] ?? null;
        }

        return $out;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function bool(string $key): bool
    {
        return filter_var($this->input($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        $value = $this->query[$key] ?? $default;

        return is_scalar($value) ? $value : $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $file : null;
    }

    public function cookie(string $key): ?string
    {
        $value = $this->cookies[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $this->server[$key] ?? null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept') ?? '', 'application/json')
            || $this->json !== null
            || ($this->header('X-Requested-With') ?? '') === 'fetch';
    }

    public function referer(): ?string
    {
        return $this->header('Referer');
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
}
