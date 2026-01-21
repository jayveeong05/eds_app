<?php
/**
 * Application Router
 * Handles URL to Controller mapping
 */
class Router {
    private $routes = [];

    /**
     * Add a route
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path URL path
     * @param string $handler Controller@method format
     */
    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Dispatch request to appropriate controller
     */
    public function dispatch($method, $uri) {
        $method = strtoupper($method);

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                return $this->callHandler($route['handler']);
            }
        }

        // No route found - 404
        $this->handleNotFound($uri);
    }

    /**
     * Call the controller method
     */
    private function callHandler($handler) {
        // Support closures (anonymous functions)
        if ($handler instanceof Closure) {
            $handler();
            return;
        }
        
        // Support string handlers like 'Controller@method'
        list($controller, $method) = explode('@', $handler);
        
        $controllerFile = __DIR__ . '/Controllers/' . $controller . '.php';
        
        if (!file_exists($controllerFile)) {
            $this->handleError("Controller not found: $controller");
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controller)) {
            $this->handleError("Controller class not found: $controller");
            return;
        }

        $controllerInstance = new $controller();

        if (!method_exists($controllerInstance, $method)) {
            $this->handleError("Method not found: $controller@$method");
            return;
        }

        $controllerInstance->$method();
    }

    /**
     * Handle 404 errors
     */
    private function handleNotFound($uri) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route not found: ' . $uri
        ]);
    }

    /**
     * Handle internal errors
     */
    private function handleError($message) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
}
