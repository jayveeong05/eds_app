<?php
/**
 * Vercel Entry Point
 * 
 * Vercel requires serverless functions to be in the /api directory.
 * This file acts as a bridge to the main Front Controller in public/index.php.
 */

// Include the main Front Controller
require __DIR__ . '/../public/index.php';
