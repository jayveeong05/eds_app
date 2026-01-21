<?php
/**
 * DigitalOcean AI Agent Configuration
 * 
 * IMPORTANT: Add this file to .gitignore!
 * Never commit API credentials to version control.
 */

// Load environment variables
require_once __DIR__ . '/load_env.php';

// DigitalOcean Agent API endpoint
define('DO_AGENT_BASE_URL', getenv('DO_AGENT_BASE_URL') ?: '');

// DigitalOcean Agent API key
define('DO_AGENT_API_KEY', getenv('DO_AGENT_API_KEY') ?: '');

// Printer Matcher Agent Configuration
define('DO_PRINTER_AGENT_URL', getenv('DO_PRINTER_AGENT_URL') ?: '');
define('DO_PRINTER_AGENT_KEY', getenv('DO_PRINTER_AGENT_KEY') ?: '');
?>
