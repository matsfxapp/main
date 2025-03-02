<?php
require_once 'config/config.php';
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

function getMinioClient() {
    global $minioConfig;
    
    try {
        // Log connection attempt
        error_log("Attempting to connect to MinIO at: " . $minioConfig['endpoint']);
        
        $client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $minioConfig['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $minioConfig['credentials']['key'],
                'secret' => $minioConfig['credentials']['secret'],
            ],
            // Increased timeouts for reliability
            'http' => [
                'connect_timeout' => 10,
                'timeout' => 15
            ]
        ]);
        
        // Test the connection
        $client->listBuckets();
        error_log("MinIO connection successful");
        return $client;
    } catch (Exception $e) {
        error_log("MinIO connection error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        throw new Exception("Failed to connect to storage service: " . $e->getMessage());
    }
}

function getMinIOObjectUrl($bucket, $key) {
    if (empty($key)) {
        return '/defaults/default-cover.jpg';
    }
    
    // Map bucket names to their specific URL prefixes
    $bucketUrls = [
        'music-songs' => 'http://minio:9000/music-songs/',
        'songs' => 'http://minio:9000/music-songs/',
        'music-covers' => 'http://minio:9000/music-covers/',
        'covers' => 'http://minio:9000/music-covers/',
        'user-profiles' => 'http://minio:9000/user-profiles/',
        'profiles' => 'http://minio:9000/user-profiles/'
    ];
    
    // Check if we have a specific URL for this bucket
    if (isset($bucketUrls[$bucket])) {
        return $bucketUrls[$bucket] . $key;
    }
    
    // Fallback to the original implementation if bucket not in our map
    global $minioConfig;
    $endpoint = rtrim($minioConfig['endpoint'], '/');
    return "$endpoint/$bucket/$key";
}

function ensureMinIOBuckets() {
    global $minioConfig;
    
    try {
        $s3 = getMinioClient();
        
        // Get bucket names from config
        $buckets = [
            $minioConfig['buckets']['songs'] ?? 'music-songs',
            $minioConfig['buckets']['covers'] ?? 'music-covers',
            $minioConfig['buckets']['profiles'] ?? 'music-profiles'
        ];
        
        foreach ($buckets as $bucket) {
            // Check if bucket exists
            try {
                $bucketExists = $s3->doesBucketExist($bucket);
                error_log("Bucket $bucket exists: " . ($bucketExists ? "yes" : "no"));
            } catch (Exception $e) {
                $bucketExists = false;
                error_log("Error checking if bucket exists: " . $e->getMessage());
                throw new Exception("Failed to check if bucket '$bucket' exists: " . $e->getMessage());
            }
            
            // Create bucket if it doesn't exist
            if (!$bucketExists) {
                error_log("Creating bucket: $bucket");
                
                try {
                    $result = $s3->createBucket([
                        'Bucket' => $bucket
                    ]);
                    
                    // Set more permissive access policy on the bucket
                    $s3->putBucketPolicy([
                        'Bucket' => $bucket,
                        'Policy' => json_encode([
                            'Version' => '2012-10-17',
                            'Statement' => [
                                [
                                    'Effect' => 'Allow',
                                    'Principal' => '*',
                                    'Action' => [
                                        's3:GetObject',
                                        's3:PutObject',
                                        's3:DeleteObject',
                                        's3:ListBucket'
                                    ],
                                    'Resource' => [
                                        "arn:aws:s3:::$bucket",
                                        "arn:aws:s3:::$bucket/*"
                                    ]
                                ]
                            ]
                        ])
                    ]);
                    
                    error_log("Successfully created bucket: $bucket with full access policy");
                } catch (Exception $e) {
                    error_log("Failed to create bucket $bucket: " . $e->getMessage());
                    throw new Exception("Failed to create bucket '$bucket': " . $e->getMessage());
                }
            } else {
                try {
                    $s3->putBucketPolicy([
                        'Bucket' => $bucket,
                        'Policy' => json_encode([
                            'Version' => '2012-10-17',
                            'Statement' => [
                                [
                                    'Effect' => 'Allow',
                                    'Principal' => '*',
                                    'Action' => [
                                        's3:GetObject',
                                        's3:PutObject',
                                        's3:DeleteObject',
                                        's3:ListBucket'
                                    ],
                                    'Resource' => [
                                        "arn:aws:s3:::$bucket",
                                        "arn:aws:s3:::$bucket/*"
                                    ]
                                ]
                            ]
                        ])
                    ]);
                    error_log("Updated policy for existing bucket: $bucket");
                } catch (Exception $e) {
                    error_log("Failed to update policy for bucket $bucket: " . $e->getMessage());
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error ensuring MinIO buckets: " . $e->getMessage());
        throw new Exception("Storage setup error: " . $e->getMessage());
    }
}

function uploadSong($title, $artist, $album, $genre, $file, $cover_art = null) {
    global $pdo, $minioConfig;
    
    // Debug log
    error_log("Starting upload process for: $title by $artist");
    
    // Initialize result array
    $result = [
        'success' => false,
        'message' => 'Unknown error occurred'
    ];
    
    // Check file error
    if (!isset($file) || !is_array($file) || $file["error"] !== 0) {
        $errorMsg = "File upload error code: " . ($file["error"] ?? 'No file');
        error_log($errorMsg);
        $result['message'] = $errorMsg;
        return $result;
    }
    
    // Validate database connection
    if (!$pdo) {
        $errorMsg = "Database connection not established";
        error_log($errorMsg);
        $result['message'] = $errorMsg;
        return $result;
    }
    
    // Get bucket names from config
    $songs_bucket = $minioConfig['buckets']['songs'] ?? 'music-songs';
    $covers_bucket = $minioConfig['buckets']['covers'] ?? 'music-covers';
    
    // Generate unique filenames for storage
    $song_key = uniqid() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file["name"]));
    $cover_filename = null;
    
    try {
        // Create a MinIO client
        $s3 = getMinioClient();
        
        // Ensure buckets exist
        try {
            ensureMinIOBuckets();
        } catch (Exception $e) {
            $result['message'] = "Storage setup failed: " . $e->getMessage();
            return $result;
        }
        
        // Upload song file
        error_log("Uploading song file: {$file['name']} to bucket $songs_bucket");
        
        try {
            $result_song = $s3->putObject([
                'Bucket' => $songs_bucket,
                'Key' => $song_key,
                'SourceFile' => $file["tmp_name"],
                'ContentType' => $file["type"],
                'ACL' => 'public-read'
            ]);
            
            // Store the full URL instead of just the filename
            $song_filename = getMinIOObjectUrl($songs_bucket, $song_key);
            error_log("Song uploaded successfully to MinIO: $song_filename");
        } catch (Exception $e) {
            $errorMsg = "Failed to upload song file to storage: " . $e->getMessage();
            error_log($errorMsg);
            $result['message'] = $errorMsg;
            return $result;
        }
        
        // Upload cover art if provided
        if (is_array($cover_art) && $cover_art["error"] === 0) {
            $cover_key = uniqid() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($cover_art["name"]));
            
            error_log("Uploading cover art: {$cover_art['name']} to bucket $covers_bucket");
            
            try {
                $result_cover = $s3->putObject([
                    'Bucket' => $covers_bucket,
                    'Key' => $cover_key,
                    'SourceFile' => $cover_art["tmp_name"],
                    'ContentType' => $cover_art["type"],
                    'ACL' => 'public-read'
                ]);
                
                // Store the full URL instead of just the filename
                $cover_filename = getMinIOObjectUrl($covers_bucket, $cover_key);
                error_log("Cover art uploaded successfully to MinIO: $cover_filename");
            } catch (Exception $e) {
                error_log("Warning: Failed to upload cover art to MinIO: " . $e->getMessage());
                $cover_filename = null;
            }
        }
        
        // Store in database
        error_log("Storing song information in database");
        
        try {
            $stmt = $pdo->prepare("INSERT INTO songs (title, artist, album, genre, file_path, cover_art, uploaded_by) 
                     VALUES (:title, :artist, :album, :genre, :file_path, :cover_path, :uploaded_by)");
                     
            $stmt->execute([
                ':title' => $title,
                ':artist' => $artist,
                ':album' => $album,
                ':genre' => $genre,
                ':file_path' => $song_filename,
                ':cover_path' => $cover_filename ? $cover_filename : '/defaults/default-cover.jpg',
                ':uploaded_by' => $_SESSION['user_id'] ?? 0
            ]);
            
            error_log("Song database entry created successfully: $title by $artist");
            $result['success'] = true;
            $result['message'] = "Song uploaded successfully!";
            return $result;
        } catch (PDOException $e) {
            $errorMsg = "Database error while storing song: " . $e->getMessage();
            error_log($errorMsg);
            $result['message'] = $errorMsg;
            return $result;
        }
    } catch (Exception $e) {
        $errorMsg = "Upload error: " . $e->getMessage();
        error_log($errorMsg . "\n" . $e->getTraceAsString());
        $result['message'] = $errorMsg;
        return $result;
    }
}


function uploadToMinIO($bucket, $file) {
    global $minioConfig;
    
    // Initialize result
    $result = [
        'success' => false,
        'message' => 'Unknown error',
        'path' => null,
        'url' => null
    ];
    
    if (!is_array($file) || $file['error'] !== 0) {
        $result['message'] = "Invalid file data or file has error: " . ($file['error'] ?? 'unknown');
        error_log($result['message']);
        return $result;
    }
    
    try {
        // Create a MinIO client
        $s3 = getMinioClient();
        
        // Get the proper bucket name from config
        $bucketName = isset($minioConfig['buckets'][$bucket]) ? 
                      $minioConfig['buckets'][$bucket] : 
                      $bucket;
        
        // Ensure the bucket exists
        ensureMinIOBuckets();
        
        // Generate a safe filename
        $file_key = uniqid() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file["name"]));
        
        // Upload the file
        $s3Result = $s3->putObject([
            'Bucket' => $bucketName,
            'Key' => $file_key,
            'SourceFile' => $file["tmp_name"],
            'ContentType' => $file["type"],
            'ACL' => 'public-read'
        ]);
        
        // Generate the full URL
        $full_url = getMinIOObjectUrl($bucketName, $file_key);
        
        // Return success with file data - store full URL in path
        $result['success'] = true;
        $result['message'] = 'File uploaded successfully';
        $result['path'] = $full_url;
        $result['url'] = $full_url;  // Keep for backward compatibility
        return $result;
    } catch (Exception $e) {
        $result['message'] = "Error uploading to MinIO: " . $e->getMessage();
        error_log($result['message']);
        return $result;
    }
}

function getAllSongs() {
    global $pdo, $minioConfig;
    
    if (!$pdo) {
        error_log("Database connection not established in getAllSongs()");
        return [];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM songs ORDER BY upload_date DESC");
        $stmt->execute();
        $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add URLs for song files and cover art
        foreach ($songs as &$song) {
            $song['song_url'] = getMinIOObjectUrl(
                $minioConfig['buckets']['songs'] ?? 'music-songs', 
                $song['file_path']
            );
            
            $song['cover_url'] = getMinIOObjectUrl(
                $minioConfig['buckets']['covers'] ?? 'music-covers', 
                $song['cover_art']
            );
        }
        
        return $songs;
    } catch (PDOException $e) {
        error_log("Database error in getAllSongs(): " . $e->getMessage());
        return [];
    }
}
