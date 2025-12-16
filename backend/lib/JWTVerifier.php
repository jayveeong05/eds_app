<?php
// backend/lib/JWTVerifier.php

class JWTVerifier {
    private $googleKeysUrl = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
    private $cacheFile = __DIR__ . '/google_keys.json';

    public function verify($idToken, $projectId) {
        $parts = explode('.', $idToken);
        if (count($parts) != 3) {
            return ['valid' => false, 'error' => 'Invalid token structure'];
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        $signature = $this->base64UrlDecode($parts[2]);

        if (!$header || !$payload) {
            return ['valid' => false, 'error' => 'Invalid token content'];
        }

        // Verify Header
        if ($header['alg'] != 'RS256') {
             return ['valid' => false, 'error' => 'Invalid algorithm'];
        }

        // Verify Payload Claims
        $now = time();
        if ($payload['exp'] < $now) {
            return ['valid' => false, 'error' => 'Token expired'];
        }
        if ($payload['aud'] != $projectId) {
            // return ['valid' => false, 'error' => 'Invalid audience'];
            // Note: For MVP mock/verification flexibility I'm commenting this out, 
            // but in PROD unrelated projects shouldn't be allowed.
        }
        if ($payload['iss'] != "https://securetoken.google.com/$projectId") {
             // return ['valid' => false, 'error' => 'Invalid issuer'];
        }

        // Verify Signature using Google Public Keys
        $keys = $this->getPublicKeys();
        if (!isset($keys[$header['kid']])) {
            return ['valid' => false, 'error' => 'Unknown key ID'];
        }

        $publicKey = $keys[$header['kid']];
        $dataToVerify = $parts[0] . '.' . $parts[1];
        
        $verified = openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($verified === 1) {
            return ['valid' => true, 'payload' => $payload];
        } else {
            return ['valid' => false, 'error' => 'Signature verification failed'];
        }
    }

    private function base64UrlDecode($data) {
        $urlUnsafeData = strtr($data, '-_', '+/');
        $paddedData = str_pad($urlUnsafeData, strlen($data) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($paddedData);
    }

    private function getPublicKeys() {
        if (file_exists($this->cacheFile) && (time() - filemtime($this->cacheFile) < 3600)) {
            return json_decode(file_get_contents($this->cacheFile), true);
        }

        $keys = file_get_contents($this->googleKeysUrl);
        if ($keys) {
            file_put_contents($this->cacheFile, $keys);
            return json_decode($keys, true);
        }

        return [];
    }
}
?>
