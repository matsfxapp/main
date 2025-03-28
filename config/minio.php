<?php
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