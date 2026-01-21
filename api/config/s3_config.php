<?php
// backend/config/s3_config.php

// 1. Load environment variables
require_once __DIR__ . '/load_env.php';

// 2. Fetch from Environment (with fallbacks if needed, though usually not for secrets)
// 2. Fetch from Environment (with fallbacks if needed, though usually not for secrets)
define('AWS_ACCESS_KEY', getenv('EDS_AWS_ACCESS_KEY') ?: getenv('AWS_ACCESS_KEY') ?: '');
define('AWS_SECRET_KEY', getenv('EDS_AWS_SECRET_KEY') ?: getenv('AWS_SECRET_KEY') ?: '');
define('AWS_REGION', getenv('EDS_AWS_REGION') ?: getenv('AWS_REGION') ?: 'ap-southeast-1');
define('AWS_BUCKET', getenv('EDS_AWS_BUCKET') ?: getenv('AWS_BUCKET') ?: 'edocument-app');

// 3. Base URL for constructing public links
define('AWS_S3_BASE_URL', 'https://' . AWS_BUCKET . '.s3.' . AWS_REGION . '.amazonaws.com');
?>
