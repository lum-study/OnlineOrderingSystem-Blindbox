<?php
// core/Router.php
require_once __DIR__ . '/../includes/error_handler.php';
class Router
{
    /** @var array<string, array<string, callable|array>> */
    private array $routes = [];

    public function get(string $path, callable|array $callback): void
    {
        $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, callable|array $callback): void
    {
        $this->addRoute('POST', $path, $callback);
    }

    public function addRoute(string $method, string $path, callable|array $callback): void
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = "#^" . rtrim($pattern, '/') . "/*$#";
        $this->routes[$method][$pattern] = $callback;
    }

    public function resolve(): mixed
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $path = explode('?', $uri, 2)[0];

        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && str_starts_with($path, $scriptName)) {
            $path = substr($path, strlen($scriptName));
        }
        $path = $path ?: '/';

        foreach ($this->routes[$method] ?? [] as $pattern => $callback) {
            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter(
                    $matches,
                    static fn($key) => !is_numeric($key),
                    ARRAY_FILTER_USE_KEY
                );

                if (is_array($callback)) {
                    [$class, $action] = $callback;
                    $controller = new $class();
                    return $controller->$action($params);
                }

                return $callback($params);
            }
        }

        // ============================================================
        // NO ROUTE FOUND: Use your helper functions here
        // ============================================================

        // Check if the user is trying to access an Admin URL
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/views/pages/admin')) {
            showAdminError(404); // Uses views/errors/admin/404.php
        } else {
            showError(404);      // Uses views/errors/404.php
        }
        
        return null;
    }

    /**
     * Helper to render error pages based on the path (Admin vs User)
     */
    private function renderError(int $code, string $path): void
    {
        http_response_code($code);

        // 1. Determine if this is an admin path
        // We check if the URL starts with '/admin'
        $isAdmin = str_starts_with($path, '/admin');

        // 2. Define where your views are located relative to this file
        // Assuming Router.php is in /core, we go up one level to find /views
        $baseViewDir = __DIR__ . '/../views/errors/';

        // 3. Try to load the Admin specific error page first
        if ($isAdmin) {
            $adminErrorFile = $baseViewDir . 'admin/' . $code . '.php';
            if (file_exists($adminErrorFile)) {
                require $adminErrorFile;
                return; // Stop here if we found the admin page
            }
        }

        // 4. Fallback: Load the standard/member error page
        $stdErrorFile = $baseViewDir . $code . '.php';

        if (file_exists($stdErrorFile)) {
            require $stdErrorFile;
        } else {
            // 5. Last resort if NO view files exist at all
            echo "Error $code";
        }
    }
}
