<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, string $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'handler' => $handler,
            'pattern' => $this->pathToPattern($path),
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            // Named captures become route params
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setRouteParams($params);

            return $this->callHandler($route['handler'], $request);
        }

        return Response::notFound();
    }

    private function pathToPattern(string $path): string
    {
        // Convert {param} placeholders to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function callHandler(string $handler, Request $request): Response
    {
        [$class, $method] = explode('@', $handler);
        $fqcn = 'App\\Controllers\\' . $class;

        if (!class_exists($fqcn)) {
            return Response::notFound('Controller not found: ' . $class);
        }

        $controller = new $fqcn();

        if (!method_exists($controller, $method)) {
            return Response::notFound('Method not found: ' . $method);
        }

        return $controller->$method($request);
    }
}
