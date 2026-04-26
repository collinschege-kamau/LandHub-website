const cacheName = 'landhub-v1';
const assets = [
  '/',
  '/index.php',
  '/listings.php', // Add your other important pages here
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
];

// This runs when the app is first installed
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(cacheName).then(cache => {
      console.log('LandHub: Caching system files');
      return cache.addAll(assets);
    })
  );
});
self.addEventListener('fetch', e => {
  e.respondWith(
    caches.match(e.request).then(res => {
      // Return the cached file OR fetch from network
      return res || fetch(e.request);
    })
  );
});
