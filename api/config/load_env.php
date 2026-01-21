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
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue; // Skip invalid lines
        
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        
        // Only set if not already set (environment variables take precedence)
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// Load environment variables from .env file (Project Root)
// We look 2 levels up: api/config/ -> api/ -> root/
$rootEnv = __DIR__ . '/../../.env';
$localEnv = __DIR__ . '/.env';

if (file_exists($rootEnv)) {
    loadEnv($rootEnv);
} elseif (file_exists($localEnv)) {
    loadEnv($localEnv);
}
