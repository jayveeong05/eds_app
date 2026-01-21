<?php
/**
 * S3 Connection Test Script
 * Run this to test if S3 credentials and connection work
 */

require_once __DIR__ . '/config/s3_config.php';
require_once __DIR__ . '/lib/SimpleS3.php';

echo "=== S3 Connection Test ===\n\n";

// Check if credentials are set
echo "1. Checking credentials...\n";
echo "   AWS_REGION (EDS_...): " . (defined('AWS_REGION') ? AWS_REGION : 'NOT SET') . "\n";
echo "   AWS_BUCKET (EDS_...): " . (defined('AWS_BUCKET') ? AWS_BUCKET : 'NOT SET') . "\n";
echo "   AWS_ACCESS_KEY (EDS_...): " . (defined('AWS_ACCESS_KEY') && !empty(AWS_ACCESS_KEY) ? 'SET (' . strlen(AWS_ACCESS_KEY) . ' chars)' : 'NOT SET') . "\n";
echo "   AWS_SECRET_KEY (EDS_...): " . (defined('AWS_SECRET_KEY') && !empty(AWS_SECRET_KEY) ? 'SET (' . strlen(AWS_SECRET_KEY) . ' chars)' : 'NOT SET') . "\n\n";

if (!defined('AWS_ACCESS_KEY') || !defined('AWS_SECRET_KEY') || !defined('AWS_BUCKET')) {
    die("ERROR: S3 credentials not configured properly!\n");
}

// Test S3 connection with a simple test
echo "2. Testing S3 connection...\n";

try {
    $s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
    
    // Create a small test file
    $testFile = sys_get_temp_dir() . '/s3_test_' . time() . '.txt';
    file_put_contents($testFile, 'S3 connection test - ' . date('Y-m-d H:i:s'));
    
    $testKey = 'test/' . basename($testFile);
    
    echo "   Uploading test file to: s3://" . AWS_BUCKET . "/" . $testKey . "\n";
    
    $result = $s3->putObject($testFile, AWS_BUCKET, $testKey);
    
    // Clean up test file
    unlink($testFile);
    
    if ($result === true) {
        echo "   ✓ SUCCESS! S3 upload works correctly.\n\n";
        
        // Test presigned URL generation
        echo "3. Testing presigned URL generation...\n";
        $url = $s3->getPresignedUrl(AWS_BUCKET, $testKey, 300);
        echo "   ✓ Presigned URL: " . substr($url, 0, 80) . "...\n\n";
        
        echo "=== All tests PASSED ===\n";
    } else {
        echo "   ✗ FAILED: " . $result . "\n\n";
        echo "=== Test FAILED ===\n";
        
        // Additional debugging
        echo "\nDEBUGGING INFO:\n";
        echo "- Check if AWS credentials are correct\n";
        echo "- Check if bucket name is correct: " . AWS_BUCKET . "\n";
        echo "- Check if region is correct: " . AWS_REGION . "\n";
        echo "- Check network/firewall settings\n";
        echo "- Try running: curl https://s3." . AWS_REGION . ".amazonaws.com\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ EXCEPTION: " . $e->getMessage() . "\n\n";
    echo "=== Test FAILED ===\n";
}
?>
