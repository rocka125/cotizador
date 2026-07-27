// service-worker.js — FORTRESS8 Cotizador
// Vive en la raíz para que su "scope" cubra toda la app (/cotizador/).
// Se encarga de: 1) permitir que el navegador ofrezca "Instalar app" (PWA)
// y 2) mostrar las notificaciones push que llegan del servidor.

const CACHE_NAME = 'fortress8-shell-v1';
const OFFLINE_URL = '/cotizador/login.php';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

// Handler de fetch mínimo (requerido por Chrome/Android para considerar
// la app "instalable"). No cachea agresivamente para no servir vistas
// desactualizadas de un cotizador que cambia constantemente de datos.
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});

// ── Notificaciones push ─────────────────────────────────────────────────
self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) {
    data = { title: 'Fortress8', body: event.data ? event.data.text() : '' };
  }

  const title = data.title || 'Fortress8 Cotizador';
  const options = {
    body: data.body || '',
    icon: data.icon || '/cotizador/assets/icons/icon-192.png',
    badge: data.badge || '/cotizador/assets/icons/icon-192.png',
    data: { url: data.url || '/cotizador/dashboard.php' },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/cotizador/dashboard.php';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes('/cotizador/') && 'focus' in client) {
          client.navigate(url);
          return client.focus();
        }
      }
      if (self.clients.openWindow) return self.clients.openWindow(url);
    })
  );
});
