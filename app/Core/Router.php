<?php

namespace App\Core;

use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;

final class Router
{
    private const MIDDLEWARE = [
        'auth'  => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
        'admin' => AdminMiddleware::class,
    ];

    /** Runs on every state-changing request. */
    private const GLOBAL_POST_MIDDLEWARE = [CsrfMiddleware::class];

    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, string $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, string $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    /**
     * @param array{prefix?: string, middleware?: string[]} $attributes
     */
    public function group(array $attributes, callable $routes): void
    {
        $this->groupStack[] = $attributes;
        $routes($this);
        array_pop($this->groupStack);
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setParams($params);

            try {
                $middleware = $method === 'POST' ? self::GLOBAL_POST_MIDDLEWARE : [];
                foreach ($route['middleware'] as $alias) {
                    $middleware[] = self::MIDDLEWARE[$alias] ?? throw new \RuntimeException("Unknown middleware [$alias]");
                }
                foreach ($middleware as $class) {
                    $response = (new $class())->handle($request);
                    if ($response instanceof Response) {
                        return $response;
                    }
                }

                return $this->callAction($route['action'], $request, $params);
            } catch (ValidationException $e) {
                return $e->response;
            }
        }

        if ($allowed) {
            throw new HttpException(405, 'Method not allowed.');
        }

        throw new HttpException(404, 'Page not found.');
    }

    private function add(string $method, string $path, string $action, array $middleware): void
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'] ?? '';
            $middleware = array_merge($group['middleware'] ?? [], $middleware);
        }

        $full = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $full);

        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'action'     => $action,
            'middleware' => array_values(array_unique($middleware)),
        ];
    }

    private function callAction(string $action, Request $request, array $params): Response
    {
        [$class, $method] = explode('@', $action);
        $class = 'App\\Controllers\\' . $class;
        $controller = new $class($request);
        $result = $controller->$method(...array_values($params));

        return $result instanceof Response ? $result : Response::html((string) $result);
    }
}
