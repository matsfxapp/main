<?php
require_once __DIR__ . '/../vendor/autoload.php';

$isDocker = file_exists('/.dockerenv');
$minioHost = getenv('MINIO_HOST') ?: 'minio:9000';

if (strpos($minioHost, ':') === false) {
    $minioPort = getenv('MINIO_PORT') ?: '9000';
    $minioEndpoint = 'http://' . $minioHost . ':' . $minioPort;
} else {
    $minioEndpoint = 'http://' . $minioHost;
}

$minioConfig = [
    'endpoint' => $minioEndpoint,
    'credentials' => [
        'key' => getenv('MINIO_ROOT_USER'),
        'secret' => getenv('MINIO_ROOT_PASSWORD'),
    ],
    'buckets' => [
        'songs' => 'music-songs',
        'covers' => 'music-covers',
        'profiles' => 'user-profiles',
        'banners' => 'user-banners',
    ],
    
    // CDN Configuration
    'cdn' => [
        'enabled' => getenv('CDN_ENABLED') === 'true',
        'domain' => getenv('CDN_DOMAIN') ?: 'cdn.matsfx.com',
        'use_https' => getenv('CDN_USE_HTTPS') !== 'false',
    ],
    
    'max_sizes' => [
        'song'    => 20 * 1024 * 1024,
        'cover'   => 5 * 1024 * 1024,
        'profile' => 5 * 1024 * 1024,
        'banner'  => 10 * 1024 * 1024
    ],
    
    'allowed_types' => [
        'songs'    => ['audio/mpeg', 'audio/wav', 'audio/x-wav'],
        'images'   => ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'],
    ],
];


function getMinIOObjectUrl($bucket, $objectKey) {
    if (empty($objectKey) || $objectKey === 'default-cover.jpg' || $objectKey === 'default-profile.jpg' || $objectKey === 'default-banner.jpg') {
        return '/defaults/' . $objectKey;
    }
    
    if (strpos($objectKey, 'http://') === 0 || strpos($objectKey, 'https://') === 0) {
        return $objectKey;
    }
    
    $objectKey = str_replace($bucket . '/', '', $objectKey);

    return "http://localhost:9000/{$bucket}/{$objectKey}";
}

/**
 * Get MinIO client for direct operations
 */
function getMinioClient() {
    global $minioConfig;
    
    try {
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'endpoint' => $minioConfig['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $minioConfig['credentials']['key'],
                'secret' => $minioConfig['credentials']['secret'],
            ],
        ]);
        
        return $s3;
    } catch (Exception $e) {
        error_log("Failed to create MinIO client: " . $e->getMessage());
        return null;
    }
}

/**
 * Ensure all required MinIO buckets exist
 */
function ensureMinIOBuckets() {
    global $minioConfig;
    $s3 = getMinioClient();
    
    if (!$s3) {
        throw new Exception("MinIO client not available");
    }
    
    foreach ($minioConfig['buckets'] as $bucket) {
        try {
            $s3->headBucket(['Bucket' => $bucket]);
        } catch (Exception $e) {
            // Bucket doesn't exist, create it
            try {
                $s3->createBucket(['Bucket' => $bucket]);
                
                // Set public read policy
                $policy = json_encode([
                    'Version' => '2012-10-17',
                    'Statement' => [
                        [
                            'Effect' => 'Allow',
                            'Principal' => '*',
                            'Action' => 's3:GetObject',
                            'Resource' => "arn:aws:s3:::{$bucket}/*"
                        ]
                    ]
                ]);
                
                $s3->putBucketPolicy([
                    'Bucket' => $bucket,
                    'Policy' => $policy
                ]);
                
                error_log("Created bucket: $bucket");
            } catch (Exception $e2) {
                error_log("Failed to create bucket $bucket: " . $e2->getMessage());
            }
        }
    }
}