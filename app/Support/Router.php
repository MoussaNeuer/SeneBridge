<?php

declare(strict_types=1);

namespace App\Support;

use App\Middlewares\MiddlewareContract;
use Closure;

/**
 * Routeur HTTP : routing centralisé, middlewares, paramètres nommés.
 *
 * Route : ['GET', '/projets/{publicId}', [Controller::class, 'action'], 'nom', ['middleware']]
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, params:array<int,string>, action:mixed, name:?string, middleware:array<int,string>}> */
    private static array $routes = [];

    /** @var array<string, callable|string> Nom => résolution de middleware. */
    private static array $middlewareAliases = [
        'auth' => \App\Middlewares\AuthMiddleware::class,
        'guest' => \App\Middlewares\GuestMiddleware::class,
        'csrf' => \App\Middlewares\CsrfMiddleware::class,
        'verified' => \App\Middlewares\EmailVerifiedMiddleware::class,
        'role' => \App\Middlewares\RoleMiddleware::class,
        'permission' => \App\Middlewares\PermissionMiddleware::class,
        'staff' => \App\Middlewares\AdminAreaMiddleware::class,
        'api' => \App\Middlewares\ApiMiddleware::class,
    ];

    public static function get(string $path, mixed $action, ?string $name = null, array $middleware = []): void
    {
        self::add('GET', $path, $action, $name, $middleware);
    }

    public static function post(string $path, mixed $action, ?string $name = null, array $middleware = []): void
    {
        self::add('POST', $path, $action, $name, $middleware);
    }

    public static function put(string $path, mixed $action, ?string $name = null, array $middleware = []): void
    {
        self::add('PUT', $path, $action, $name, $middleware);
    }

    public static function delete(string $path, mixed $action, ?string $name = null, array $middleware = []): void
    {
        self::add('DELETE', $path, $action, $name, $middleware);
    }

    public static function any(string $path, mixed $action, ?string $name = null, array $middleware = []): void
    {
        self::add('ANY', $path, $action, $name, $middleware);
    }

    /**
     * Vide le registre des routes (appelé à chaque requête, notamment sous le
     * serveur intégré PHP où les statiques persistent entre requêtes).
     */
    public static function reset(): void
    {
        self::$routes = [];
    }

    public static function group(array $attributes, callable $routes): void
    {
        // Préfixe + middleware de groupe ajouté aux routes du callback.
        $savedRoutes = self::$routes;
        self::$routes = [];
        $routes();

        $prefix = rtrim((string) ($attributes['prefix'] ?? ''), '/');
        $groupMiddleware = $attributes['middleware'] ?? [];

        foreach (self::$routes as $key => $route) {
            // Normalise la concaténation (le pattern '/project' + '/' doit donner '/project').
            $path = rtrim($prefix . '/' . ltrim($route['pattern'], '/'), '/');
            $path = $path !== '' ? $path : '/';
            $middleware = array_merge($groupMiddleware, $route['middleware']);
            self::$routes[$key] = [
                ...$route,
                'pattern' => $path,
                'middleware' => $middleware,
            ];
        }

        self::$routes = array_merge($savedRoutes, self::$routes);
    }

    private static function add(string $method, string $path, mixed $action, ?string $name = null, array $middleware = []): void
    {
        self::$routes[] = [
            'method' => $method,
            'pattern' => $path === '/' ? '/' : rtrim($path, '/'),
            'regex' => '',
            'params' => [],
            'action' => $action,
            'name' => $name,
            'middleware' => $middleware,
        ];
    }

    /**
     * Compile la liste des routes immédiatement avant la résolution.
     */
    private static function compile(): void
    {
        foreach (self::$routes as &$route) {
            if ($route['regex'] !== '') {
                continue;
            }

            [$regex, $params] = self::pathToRegex($route['pattern']);
            $route['regex'] = $regex;
            $route['params'] = $params;
        }
        unset($route);
    }

    /**
     * @return array{0:string, 1:array<int,string>}
     */
    private static function pathToRegex(string $path): array
    {
        $params = [];
        // La contrainte inline peut contenir des quantificateurs {n} (ex. [a-f0-9]{64}).
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::((?:[^{}]|\{[0-9]+\})+))?\}/', function ($m) use (&$params) {
            $params[] = $m[1];
            $pattern = $m[2] ?? '[^/]+';

            return '(' . $pattern . ')';
        }, $path) ?? $path;

        return ['#^' . str_replace('#', '\#', $regex) . '$#', $params];
    }

    public static function resolve(Request $request): mixed
    {
        self::compile();
        $path = $request->path();

        // Le routeur est servi sous /public ; on normalise le chemin éventuel.
        $matches = [];

        foreach (self::$routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $request->method()) {
                continue;
            }

            if (preg_match($route['regex'], $path, $m) !== 1) {
                continue;
            }

            $params = [];
            foreach ($route['params'] as $index => $name) {
                $params[$name] = $m[$index + 1];
            }

            self::runMiddleware($route['middleware'], $request);

            return self::callAction($route['action'], $params);
        }

        // 404 : version JSON pour l'API, page HTML sinon.
        if (str_starts_with($path, '/api/')) {
            return Response::json([
                'success' => false,
                'data' => null,
                'message' => 'Ressource introuvable.',
                'errors' => [],
            ], 404);
        }

        return Response::notFound('La page demandée n\'existe pas.');
    }

    public static function routeName(string $name): ?array
    {
        self::compile();

        foreach (self::$routes as $route) {
            if ($route['name'] === $name) {
                return $route;
            }
        }

        return null;
    }

    public static function url(string $name, array $params = []): string
    {
        $route = self::routeName($name);

        if ($route === null) {
            throw new \RuntimeException(sprintf('Route introuvable : %s', $name));
        }

        $path = $route['pattern'];

        foreach ($params as $key => $value) {
            $escaped = preg_quote((string) $key, '/');
            $path = preg_replace(
                '/\{' . $escaped . '(?::(?:[^{}]|\{[0-9]+\})+)?\}/',
                (string) $value,
                $path
            ) ?? $path;
        }

        return app_url(ltrim($path, '/'));
    }

    private static function runMiddleware(array $middleware, Request $request): void
    {
        foreach ($middleware as $alias) {
            // Les middlewares paramétrés utilisent la syntaxe « alias:valeur »
            // (ex. permission:projects.create, role:admin).
            [$aliasName, $parameter] = array_pad(explode(':', $alias, 2), 2, null);
            $resolved = self::$middlewareAliases[$aliasName] ?? $aliasName;

            if (!class_exists($resolved)) {
                throw new \RuntimeException(sprintf('Middleware introuvable : %s', $alias));
            }

            $instance = new $resolved();

            if ($instance instanceof MiddlewareContract) {
                $result = $instance->handle($request, fn () => null, $parameter);
                if ($result instanceof Response) {
                    $result->send();
                    exit;
                }
            }
        }
    }

    private static function callAction(mixed $action, array $params): Response
    {
        if ($action instanceof Closure) {
            $result = $action(...array_values($params));

            return $result instanceof Response ? $result : Response::text((string) $result);
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);

            $controller = new $class();
            $result = $controller->{$method}(...array_values($params));

            return $result instanceof Response ? $result : Response::text((string) $result);
        }

        if (is_array($action) && isset($action[0], $action[1])) {
            [$class, $method] = $action;
            $controller = new $class();
            $result = $controller->{$method}(...array_values($params));

            return $result instanceof Response ? $result : Response::text((string) $result);
        }

        throw new \RuntimeException('Action de route invalide.');
    }
}