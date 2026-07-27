/**
 * notif-sound.js — FORTRESS8 Cotizador
 *
 * Reproduce pequeños sonidos de notificación (sin archivos de audio,
 * generados con Web Audio API) para eventos clave de la app:
 *   - NotifSound.playCreate()  → al crear/guardar una cotización
 *   - NotifSound.playSend()    → al enviar una cotización (correo/whatsapp)
 *   - NotifSound.playAlert()   → notificaciones/avisos generales (opcional)
 *
 * Respeta la preferencia del usuario guardada en localStorage
 * bajo la clave "notif_sound_enabled" (por defecto: activado).
 * Usa un único AudioContext compartido y lo reanuda si el navegador
 * lo suspende por políticas de autoplay.
 */
(function (global) {
  'use strict';

  const STORAGE_KEY = 'notif_sound_enabled';
  let ctx = null;

  function isEnabled() {
    const v = localStorage.getItem(STORAGE_KEY);
    return v === null ? true : v === '1';
  }

  function setEnabled(enabled) {
    localStorage.setItem(STORAGE_KEY, enabled ? '1' : '0');
  }

  function getCtx() {
    if (!ctx) {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return null;
      ctx = new AudioCtx();
    }
    if (ctx.state === 'suspended') {
      ctx.resume().catch(() => {});
    }
    return ctx;
  }

  /**
   * Reproduce una secuencia de tonos.
   * @param {Array<{freq:number, start:number, dur:number, gain?:number, type?:OscillatorType}>} notes
   */
  function playNotes(notes) {
    if (!isEnabled()) return;
    const c = getCtx();
    if (!c) return;

    const now = c.currentTime;
    notes.forEach(note => {
      const osc  = c.createOscillator();
      const gain = c.createGain();
      osc.type = note.type || 'sine';
      osc.frequency.setValueAtTime(note.freq, now + note.start);

      const peak = note.gain ?? 0.18;
      const t0 = now + note.start;
      const t1 = t0 + note.dur;

      gain.gain.setValueAtTime(0.0001, t0);
      gain.gain.exponentialRampToValueAtTime(peak, t0 + Math.min(0.02, note.dur / 3));
      gain.gain.exponentialRampToValueAtTime(0.0001, t1);

      osc.connect(gain);
      gain.connect(c.destination);
      osc.start(t0);
      osc.stop(t1 + 0.02);
    });
  }

  // Sonido de "creado/guardado" — dos notas ascendentes, cálido y breve.
  function playCreate() {
    playNotes([
      { freq: 587.33, start: 0,    dur: 0.11, type: 'sine', gain: 0.16 }, // D5
      { freq: 880.00, start: 0.10, dur: 0.16, type: 'sine', gain: 0.18 }, // A5
    ]);
  }

  // Sonido de "enviado" — un "whoosh" de dos notas rápidas más agudas.
  function playSend() {
    playNotes([
      { freq: 740.00, start: 0,    dur: 0.09, type: 'triangle', gain: 0.15 }, // F#5
      { freq: 987.77, start: 0.07, dur: 0.10, type: 'triangle', gain: 0.17 }, // B5
      { freq: 1318.5, start: 0.15, dur: 0.14, type: 'sine',     gain: 0.14 }, // E6
    ]);
  }

  // Sonido de alerta/notificación genérica — un solo "ping".
  function playAlert() {
    playNotes([
      { freq: 660.00, start: 0, dur: 0.18, type: 'sine', gain: 0.15 },
    ]);
  }

  // Sonido de "aprobada" — arpegio mayor ascendente, brillante y positivo.
  function playApprove() {
    playNotes([
      { freq: 523.25, start: 0,    dur: 0.10, type: 'sine', gain: 0.15 }, // C5
      { freq: 659.25, start: 0.08, dur: 0.10, type: 'sine', gain: 0.16 }, // E5
      { freq: 783.99, start: 0.16, dur: 0.20, type: 'sine', gain: 0.19 }, // G5
    ]);
  }

  // Sonido de "rechazada" — dos notas descendentes en menor, grave y seco.
  function playReject() {
    playNotes([
      { freq: 392.00, start: 0,    dur: 0.13, type: 'sawtooth', gain: 0.12 }, // G4
      { freq: 293.66, start: 0.11, dur: 0.22, type: 'sawtooth', gain: 0.14 }, // D4
    ]);
  }

  global.NotifSound = { playCreate, playSend, playAlert, playApprove, playReject, isEnabled, setEnabled };
})(window);
