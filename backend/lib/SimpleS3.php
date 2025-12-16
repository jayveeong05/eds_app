<?php
// backend/lib/SimpleS3.php

class SimpleS3 {
    private $accessKey;
    private $secretKey;
    private $region;

    public function __construct($accessKey, $secretKey, $region) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->region = $region;
    }

    public function putObject($file_path, $bucket, $uri) {
        $host = "$bucket.s3.$this->region.amazonaws.com";
        $date = gmdate('D, d M Y H:i:s T');
        
        // Content type detection
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $content_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $content = file_get_contents($file_path);
        $content_hash = md5($content); // Content-MD5 is optional but good practice
        
        // AWS Signature V4 is widely used but complex to implement in a single file without libs.
        // For simplicity in this "no-composer" environment, we might try Signature V2 or just use a very simple V4 implementation.
        // However, V2 is deprecated in newer regions.
        // A better approach for "no-composer" might be to use a widely compatible simplistic curve, or just use `curl` with params if possibly pre-signed.
        // BUT, properly implementing AWS SigV4 from scratch is error-prone.
        // ALTERNATIVE: Use a pre-built single-file library location if available, or write a minimal V4 signer.
        
        // Let's implement a minimal V4 Signer.
        
        $service = 's3';
        $algorithm = 'AWS4-HMAC-SHA256';
        $amz_date = gmdate('Ymd\THis\Z');
        $date_stamp = gmdate('Ymd');
        
        // Canonical Request
        $canonical_uri = '/' . $uri;
        $canonical_querystring = '';
        $canonical_headers = "host:$host\nx-amz-content-sha256:" . hash('sha256', $content) . "\nx-amz-date:$amz_date\n";
        $signed_headers = 'host;x-amz-content-sha256;x-amz-date';
        $payload_hash = hash('sha256', $content);
        
        $canonical_request = "PUT\n$canonical_uri\n$canonical_querystring\n$canonical_headers\n$signed_headers\n$payload_hash";
        
        // String to Sign
        $credential_scope = "$date_stamp/$this->region/$service/aws4_request";
        $string_to_sign = "$algorithm\n$amz_date\n$credential_scope\n" . hash('sha256', $canonical_request);
        
        // Signature Calculation
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
        
        // Authorization Header
        $authorization_header = "$algorithm Credential=$this->accessKey/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";
        
        $headers = array(
            "Host: $host",
            "Date: $date",
            "Content-Type: $content_type",
            "Authorization: $authorization_header",
            "x-amz-date: $amz_date",
            "x-amz-content-sha256: $payload_hash"
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://$host/$uri");
        curl_setopt($ch, CURLOPT_PUT, 1);
        curl_setopt($ch, CURLOPT_INFILE, fopen($file_path, 'r'));
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file_path));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            return true;
        } else {
            return "Error ($http_code): $result";
        }
    }

    /**
     * Download object from S3 with signed GET request
     */
    public function getObject($bucket, $uri) {
        $host = "$bucket.s3.$this->region.amazonaws.com";
        $service = 's3';
        $algorithm = 'AWS4-HMAC-SHA256';
        $amz_date = gmdate('Ymd\\THis\\Z');
        $date_stamp = gmdate('Ymd');
        
        // Canonical Request for GET
        $canonical_uri = '/' . $uri;
        $canonical_querystring = '';
        $canonical_headers = "host:$host\nx-amz-date:$amz_date\n";
        $signed_headers = 'host;x-amz-date';
        $payload_hash = hash('sha256', ''); // Empty payload for GET
        
        $canonical_request = "GET\n$canonical_uri\n$canonical_querystring\n$canonical_headers\n$signed_headers\n$payload_hash";
        
        // String to Sign
        $credential_scope = "$date_stamp/$this->region/$service/aws4_request";
        $string_to_sign = "$algorithm\n$amz_date\n$credential_scope\n" . hash('sha256', $canonical_request);
        
        // Signature Calculation
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
        
        // Authorization Header
        $authorization_header = "$algorithm Credential=$this->accessKey/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";
        
        $headers = array(
            "Host: $host",
            "Authorization: $authorization_header",
            "x-amz-date: $amz_date",
            "x-amz-content-sha256: $payload_hash"
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://$host/$uri");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'success' => true,
                'data' => $result,
                'content_type' => $content_type
            ];
        } else {
            return [
                'success' => false,
                'error' => $result,
                'http_code' => $http_code
            ];
        }
    }
    /**
     * Download object from S3 and stream directly to output
     * Handles large files efficiently without loading into memory
     */
    public function getObjectStream($bucket, $uri) {
        $host = "$bucket.s3.$this->region.amazonaws.com";
        $service = 's3';
        $algorithm = 'AWS4-HMAC-SHA256';
        $amz_date = gmdate('Ymd\\THis\\Z');
        $date_stamp = gmdate('Ymd');
        
        // Canonical Request for GET
        $canonical_uri = '/' . $uri;
        $canonical_querystring = '';
        $canonical_headers = "host:$host\nx-amz-date:$amz_date\n";
        $signed_headers = 'host;x-amz-date';
        $payload_hash = hash('sha256', ''); // Empty payload for GET
        
        $canonical_request = "GET\n$canonical_uri\n$canonical_querystring\n$canonical_headers\n$signed_headers\n$payload_hash";
        
        // String to Sign
        $credential_scope = "$date_stamp/$this->region/$service/aws4_request";
        $string_to_sign = "$algorithm\n$amz_date\n$credential_scope\n" . hash('sha256', $canonical_request);
        
        // Signature Calculation
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $date_stamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
        
        // Authorization Header
        $authorization_header = "$algorithm Credential=$this->accessKey/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";
        
        $headers = array(
            "Host: $host",
            "Authorization: $authorization_header",
            "x-amz-date: $amz_date",
            "x-amz-content-sha256: $payload_hash"
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://$host/$uri");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Important: Write response directly to output buffer (stream)
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
            echo $data;
            flush(); // Force send to client
            return strlen($data);
        });
        
        // Forward headers (Content-Type)
        // SKIP Content-Length to avoid "Connection closed" errors if size mismatches due to compression/buffering
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) return $len; // Invalid header
            
            $name = trim($header[0]);
            $value = trim($header[1]);
            
            // Only forward Content-Type and Cache headers. 
            // Explicitly excluding Content-Length to let client read until connection close.
            if (in_array(strtolower($name), ['content-type', 'last-modified', 'etag', 'cache-control'])) {
                header("$name: $value");
            }
            return $len;
        });
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code;
    }
}
?>
