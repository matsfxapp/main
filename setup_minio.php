<?php
require_once 'config/minio.php';

echo "Setting up MinIO buckets...\n\n";

try {
    ensureMinIOBuckets();
    echo "✅ All buckets created successfully!\n\n";
    
    // List buckets to verify
    $s3 = getMinioClient();
    $result = $s3->listBuckets();
    
    echo "Available buckets:\n";
    foreach ($result['Buckets'] as $bucket) {
        echo "  - " . $bucket['Name'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}