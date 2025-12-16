<?php
require_once __DIR__ . '/config/s3_config.php';
require_once __DIR__ . '/lib/SimpleS3.php';

echo "==============================================\n";
echo "    EDS App - S3 Connection Test\n";
echo "==============================================\n\n";

echo "Configuration:\n";
echo "  Bucket: " . AWS_BUCKET . "\n";
echo "  Region: " . AWS_REGION . "\n";
echo "  Access Key: " . substr(AWS_ACCESS_KEY, 0, 10) . "...\n\n";

echo "Creating test file...\n";

// Create a test file
$test_file = tempnam(sys_get_temp_dir(), 'test');
$test_content = "Hello from EDS App! 🚀\n";
$test_content .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
$test_content .= "This is a test upload to verify S3 integration.\n";
file_put_contents($test_file, $test_content);

echo "Test file created: " . filesize($test_file) . " bytes\n\n";

echo "Uploading to S3...\n";

// Upload to S3
$s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
$s3_key = 'test/hello.txt';
$result = $s3->putObject($test_file, AWS_BUCKET, $s3_key);

echo "\n";

if ($result === true) {
    echo "✅ SUCCESS! Upload completed successfully!\n\n";
    
    $url = AWS_S3_BASE_URL . '/' . $s3_key;
    echo "Public URL:\n";
    echo "  " . $url . "\n\n";
    
    echo "Next steps:\n";
    echo "  1. Copy the URL above and paste it in your browser\n";
    echo "  2. You should see the test message\n";
    echo "  3. Check your S3 bucket console to see the 'test' folder\n\n";
    
    echo "If you can access the file, S3 is configured correctly! 🎉\n";
} else {
    echo "❌ UPLOAD FAILED!\n\n";
    echo "Error details:\n";
    echo "  " . $result . "\n\n";
    
    echo "Common issues:\n";
    echo "  1. Check if AWS credentials are correct in config/s3_config.php\n";
    echo "  2. Verify IAM user has S3 permissions\n";
    echo "  3. Ensure bucket name and region match\n";
    echo "  4. Check if bucket exists in AWS console\n\n";
}

// Cleanup
unlink($test_file);

echo "\n==============================================\n";
?>
