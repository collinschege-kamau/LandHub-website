const cacheName = 'landhub-v1';
// Keep this list minimal for now to ensure installation succeeds
const assets = [
  '/',
  'index.php',
  'manifest.json',
  'View Listings.php' // Add your main functional pages here
];

// Installation: Caching files one by one to prevent 'addAll' failure
self.addEventListener('install', e => {
  self.skipWaiting(); // Force the new service worker to become active immediately
  e.waitUntil(
    caches.open(cacheName).then(cache => {
      console.log('LandHub: Caching system files');
      // Using map allows us to catch errors if a single file is missing
      return Promise.all(
        assets.map(url => {
          return cache.add(url).catch(err => console.warn(`Failed to cache: ${url}`, err));
        })
      );
    })
  );
});

// Activation: Clean up old caches if you update the version (e.g., v2)
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== cacheName).map(key => caches.delete(key))
      );
    })
  );
});

// Fetching: Network-first approach for better dynamic data (listings)
self.addEventListener('fetch', e => {
  e.respondWith(
    fetch(e.request).catch(() => {
      return caches.match(e.request);
    })
  );
});
