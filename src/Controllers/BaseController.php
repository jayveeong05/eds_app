<?php
/**
 * Base Controller
 * Provides common functionality for all controllers
 */
class BaseController {
    protected $basePath;

    public function __construct() {
        // Set base path for file includes
        // Note: When running from public/, we need to go up one level
        $this->basePath = __DIR__ . '/../..';
    }


    /**
     * Render a legacy PHP file
     * This is a bridge method - eventually we'll convert to views
     */
    protected function render($file) {
        $fullPath = $this->basePath . $file;
        
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "File not found: $file";
            return;
        }

        // Change working directory to match the file's location
        // This ensures relative includes still work
        $oldCwd = getcwd();
        chdir(dirname($fullPath));
        
        require $fullPath;
        
        // Restore working directory
        chdir($oldCwd);
    }

    /**
     * Send JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
