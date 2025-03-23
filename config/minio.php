<?php
$isDocker = file_exists('/.dockerenv');
$minioHost = $isDocker ? 'matsfx-minio' : 'minio';

$minioConfig = [
    'endpoint' => 'http://' . $minioHost . ':9000',
    'credentials' => [
        'key' => getenv('MINIO_ROOT_USER'),
        'secret' => getenv('MINIO_ROOT_PASSWORD'),
    ],
    'buckets' => [
        'songs' => 'music-songs',
        'covers' => 'music-covers',
        'profiles' => 'music-profiles'
    ],
    
    'max_sizes' => [
        'song'    => 20 * 1024 * 1024,
        'cover'   => 5 * 1024 * 1024,
        'profile' => 5 * 1024 * 1024,
    ],
    
    'allowed_types' => [
        'songs'    => ['audio/mpeg', 'audio/wav', 'audio/x-wav'],
        'images'   => ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'],
    ],
];