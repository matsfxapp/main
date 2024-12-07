const cacheName = 'matsfx-alpha-0.13.2';
const assets = [
    '/', // The root of your application
    '/user_handlers.php',
    '/upload.php',
    '/style.css',
    '/settings.php',
    '/service-worker.js',
    '/search_artist.php',
    '/register.php',
    '/playlists.php',
    '/playlists.css',
    '/music_handlers.php',
    '/manifest.json',
    '/logout.php',
    '/login.php',
    '/like_handlers.php',
    '/index.php',
    '/get_artist_song.php',
    '/config.php',
    '/auth.php',
    '/artist.php',
    '/admin_dashboard.php',
    '/admin.js',
    '/admin-styles.css',
    '/.htaccess',
    '/uploads/',
    '/defaults/',
    '/app_logos/',
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(cacheName).then(cache => {
            return cache.addAll(assets);
        })
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== cacheName).map(key => caches.delete(key))
            );
        })
    );
});
