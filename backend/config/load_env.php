<?php
/**
 * Simple .env file loader
 * Loads environment variables from .env file if it exists
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Only set if not already set (environment variables take precedence)
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// Load .env from the backend directory (one level up from config/)
loadEnv(__DIR__ . '/../.env');
?>
