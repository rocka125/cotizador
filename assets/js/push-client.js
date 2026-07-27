/**
 * push-client.js — FORTRESS8 Cotizador
 *
 * Gestiona el permiso y la suscripción a Web Push Notifications.
 * Se incluye en el dashboard y en cualquier página que quiera
 * solicitar permiso al usuario.
 *
 * Flujo:
 *  1. Al cargar la página, verificar si ya hay suscripción activa.
 *  2. Si no la hay, mostrar un botón discreto para activar notificaciones.
 *  3. Al hacer clic, pedir permiso al navegador.
 *  4. Con permiso concedido, suscribir con la clave VAPID del servidor.
 *  5. Enviar la suscripción al backend (api_push.php).
 */

(function () {
  'use strict';

  // ── Constantes ─────────────────────────────────────────────────────────
  const API_BASE = '/cotizador/API/api_push.php';

  // ── Utilidades ──────────────────────────────────────────────────────────

  /** Convierte base64url → Uint8Array (para applicationServerKey) */
  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw     = atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
  }

  /** Convierte ArrayBuffer → base64 estándar */
  function arrayBufferToBase64(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
  }

  // ── Estado ─────────────────────────────────────────────────────────────
  let vapidPublicKey    = null;
  let swRegistration    = null;
  let pushSubscription  = null;

  // ── Obtener clave VAPID del servidor ────────────────────────────────────
  async function fetchVapidKey() {
    try {
      const res  = await fetch(`${API_BASE}?action=vapid_key`);
      const data = await res.json();
      if (data.ok) vapidPublicKey = data.publicKey;
    } catch (e) {
      console.warn('[Push] No se pudo obtener la clave VAPID:', e);
    }
  }

  // ── Suscribir al usuario ────────────────────────────────────────────────
  async function subscribeToPush() {
    if (!swRegistration || !vapidPublicKey) return false;

    try {
      const sub = await swRegistration.pushManager.subscribe({
        userVisibleOnly:      true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
      });

      pushSubscription = sub;

      // Enviar al servidor
      const res = await fetch(API_BASE, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action:   'subscribe',
          endpoint: sub.endpoint,
          keys: {
            p256dh: arrayBufferToBase64(sub.getKey('p256dh')),
            auth:   arrayBufferToBase64(sub.getKey('auth')),
          },
        }),
      });

      const data = await res.json();
      return data.ok === true;

    } catch (e) {
      console.error('[Push] Error al suscribir:', e);
      return false;
    }
  }

  // ── Desuscribir ────────────────────────────────────────────────────────
  async function unsubscribeFromPush() {
    if (!pushSubscription) return;

    try {
      await fetch(API_BASE, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'unsubscribe', endpoint: pushSubscription.endpoint }),
      });
      await pushSubscription.unsubscribe();
      pushSubscription = null;
    } catch (e) {
      console.warn('[Push] Error al desuscribir:', e);
    }
  }

  // ── Solicitar permiso y suscribir ───────────────────────────────────────
  async function requestPermissionAndSubscribe() {
    if (!('Notification' in window)) {
      showToast('Tu navegador no soporta notificaciones push.', '#e63946');
      return;
    }

    const permission = await Notification.requestPermission();

    if (permission === 'granted') {
      updateBellButton('loading');
      const ok = await subscribeToPush();
      if (ok) {
        updateBellButton('active');
        showToast('🔔 Notificaciones activadas en este dispositivo', 'var(--orange, #e63946)');
        updatePushToggle(true);
        // Guardar preferencia local
        localStorage.setItem('push_enabled', '1');
      } else {
        updateBellButton('inactive');
        showToast('Error al activar notificaciones. Inténtalo de nuevo.', '#e63946');
      }
    } else if (permission === 'denied') {
      showToast('Permiso denegado. Actívalas en la configuración del navegador.', '#e63946');
      updatePushToggle(false);
    }
  }

  // ── Actualizar UI del botón campana de push ─────────────────────────────
  function updateBellButton(state) {
    const btn = document.getElementById('push-toggle-btn');
    if (!btn) return;

    const states = {
      inactive: { text: '🔕 Activar notificaciones', title: 'Activar notificaciones push' },
      active:   { text: '🔔 Notificaciones activas', title: 'Desactivar notificaciones push' },
      loading:  { text: '⏳ Activando…',              title: 'Activando…' },
    };

    const s = states[state] || states.inactive;
    btn.textContent  = s.text;
    btn.title        = s.title;
    btn.dataset.state = state;
  }

  function updatePushToggle(isActive) {
    const toggle = document.getElementById('push-toggle-btn');
    if (!toggle) return;
    toggle.classList.toggle('push-active', isActive);
  }

  // ── showToast global (compatible con el dashboard existente) ────────────
  function showToast(msg, color) {
    if (typeof window.showToast === 'function') {
      window.showToast(msg, color);
      return;
    }
    // Fallback si showToast no existe aún
    const el = document.getElementById('toast');
    if (el) {
      el.textContent = msg;
      el.style.background = color || '#e63946';
      el.classList.add('show');
      setTimeout(() => el.classList.remove('show'), 3500);
    }
  }

  // ── Inicialización principal ────────────────────────────────────────────
  async function init() {
    // Comprobar soporte
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      console.info('[Push] Web Push no soportado en este navegador.');
      hidePushUI();
      return;
    }

    // Obtener clave pública VAPID
    await fetchVapidKey();
    if (!vapidPublicKey) return;

    // Esperar a que el SW esté listo
    try {
      swRegistration = await navigator.serviceWorker.ready;
    } catch (e) {
      console.warn('[Push] SW no disponible:', e);
      return;
    }

    // Verificar suscripción existente
    pushSubscription = await swRegistration.pushManager.getSubscription();

    if (pushSubscription) {
      // Ya suscrito — asegurarse de que el servidor lo tiene registrado
      const res = await fetch(API_BASE, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action:   'subscribe',
          endpoint: pushSubscription.endpoint,
          keys: {
            p256dh: arrayBufferToBase64(pushSubscription.getKey('p256dh')),
            auth:   arrayBufferToBase64(pushSubscription.getKey('auth')),
          },
        }),
      }).then(r => r.json()).catch(() => ({ ok: false }));

      updateBellButton('active');
      updatePushToggle(true);
    } else {
      updateBellButton('inactive');
      updatePushToggle(false);

      // Auto-solicitar permiso si el usuario ya lo había aceptado antes
      // (útil cuando el browser renovó la suscripción)
      if (Notification.permission === 'granted' && localStorage.getItem('push_enabled') === '1') {
        await subscribeToPush();
        updateBellButton('active');
        updatePushToggle(true);
      }
    }
  }

  function hidePushUI() {
    const el = document.getElementById('push-toggle-btn');
    if (el) el.style.display = 'none';
  }

  // ── Exponer funciones globales ──────────────────────────────────────────
  window.pushClient = {
    requestPermissionAndSubscribe,
    unsubscribeFromPush,
    sendTest: async () => {
      const res  = await fetch(API_BASE, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'send_test' }),
      });
      const data = await res.json();
      if (data.ok) {
        showToast(`✅ Notificación enviada a ${data.sent} dispositivo(s)`, 'var(--orange, #e63946)');
      } else {
        showToast('❌ ' + (data.error || 'Error al enviar'), '#e63946');
      }
    },
    toggleSubscription: async () => {
      const btn = document.getElementById('push-toggle-btn');
      if (btn?.dataset.state === 'active') {
        await unsubscribeFromPush();
        updateBellButton('inactive');
        updatePushToggle(false);
        localStorage.removeItem('push_enabled');
        showToast('🔕 Notificaciones desactivadas', '#888');
      } else {
        await requestPermissionAndSubscribe();
      }
    },
  };

  // ── Arrancar cuando el DOM esté listo ──────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
