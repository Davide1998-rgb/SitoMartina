const CACHE_NAME = 'martina-admin-v2';
const APP_SHELL = [
  './admin-manifest.json',
  './img/logo.png',
  './img/favicon.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(APP_SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('message', event => {
  if (!event.data || event.data.type !== 'NUOVA_RICHIESTA') return;

  event.waitUntil(
    self.registration.showNotification('Nuova richiesta di prenotazione', {
      body: event.data.name ? `Richiesta di ${event.data.name}` : 'Hai una nuova richiesta da confermare.',
      icon: './img/logo.png',
      badge: './img/logo.png',
      tag: `prenotazione-${event.data.id}`,
      data: { url: './admin_richieste.php' }
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
    const existingClient = clientList.find(client => 'focus' in client);
    if (existingClient) {
      existingClient.navigate(event.notification.data.url);
      return existingClient.focus();
    }
    return clients.openWindow(event.notification.data.url);
  }));
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  const requestUrl = new URL(event.request.url);
  if (requestUrl.origin !== self.location.origin) return;

  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});
