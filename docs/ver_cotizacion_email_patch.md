<?php
/**
 * INSTRUCCIONES DE INSTALACIÓN EN ver_cotizacion.view.php
 * =========================================================
 *
 * Este archivo documenta los 3 cambios a hacer en ver_cotizacion.view.php.
 * Son cambios quirúrgicos: no modifica ningún HTML existente, solo agrega.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * CAMBIO 1: Agrega el botón "Enviar por correo" en el toolbar
 * ─────────────────────────────────────────────────────────────────────────
 * Busca esta línea en ver_cotizacion.view.php:
 *
 *   <a href="exportar_pdf.php?id=<?= $ctrl->id ?>" class="btn-tb export" target="_blank">
 *
 * Y ANTES de ella agrega:
 *
 *   <button type="button" class="btn-tb" id="btn-email"
 *           onclick="abrirModalEmail()"
 *           style="background:#1d4ed8;color:#fff;box-shadow:0 0 12px rgba(29,78,216,.3);">
 *       <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
 *            stroke-linecap="round" stroke-linejoin="round">
 *           <rect x="2" y="4" width="20" height="16" rx="2"/>
 *           <path d="m2 7 10 7 10-7"/>
 *       </svg>
 *       <span class="btn-tb-label">Enviar correo</span>
 *   </button>
 *
 * ─────────────────────────────────────────────────────────────────────────
 * CAMBIO 2: Agrega el modal JUSTO ANTES de </body>
 * ─────────────────────────────────────────────────────────────────────────
 * (El HTML y JS del modal están abajo en este mismo archivo)
 *
 * ─────────────────────────────────────────────────────────────────────────
 * CAMBIO 3: Agrega el CSS del modal dentro del <style> existente
 * ─────────────────────────────────────────────────────────────────────────
 * (El CSS está marcado abajo)
 */
?>

<!-- ═══════════════════════════════════════════════════════════════════════
     CSS — pegar dentro del bloque <style> existente de ver_cotizacion.view.php
═══════════════════════════════════════════════════════════════════════════ -->
<!--
/* ── MODAL CORREO ── */
.email-backdrop {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.55); backdrop-filter: blur(3px);
    z-index: 500; display: none;
    align-items: center; justify-content: center;
}
.email-backdrop.open { display: flex; }
.email-modal {
    background: #13101C; border: 0.5px solid rgba(255,255,255,.10);
    border-radius: 14px; padding: 24px 28px;
    width: 100%; max-width: 460px;
    box-shadow: 0 24px 60px rgba(0,0,0,.6);
}
.email-modal h2 {
    font-size: 15px; font-weight: 600; color: #F5F0FF;
    margin: 0 0 18px; display: flex; align-items: center; gap: 8px;
}
.email-modal label {
    display: block; font-size: 11px; color: #9B94B8;
    text-transform: uppercase; letter-spacing: .04em; margin-bottom: 5px;
}
.email-modal input, .email-modal textarea {
    width: 100%; background: #1A1626; border: 0.5px solid rgba(255,255,255,.12);
    border-radius: 8px; color: #F5F0FF; font-family: 'DM Sans', sans-serif;
    font-size: 13px; padding: 9px 12px; outline: none; box-sizing: border-box;
    transition: border-color .15s;
}
.email-modal input:focus, .email-modal textarea:focus {
    border-color: #1d4ed8;
}
.email-modal textarea { resize: vertical; min-height: 80px; line-height: 1.5; }
.email-field { margin-bottom: 14px; }
.email-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
.btn-cancel-email {
    padding: 8px 18px; border-radius: 8px; font-size: 13px; cursor: pointer;
    background: rgba(255,255,255,.06); border: 0.5px solid rgba(255,255,255,.12);
    color: #9B94B8; font-family: inherit; transition: background .15s;
}
.btn-cancel-email:hover { background: rgba(255,255,255,.10); }
.btn-send-email {
    padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 600;
    cursor: pointer; background: #1d4ed8; border: none; color: #fff;
    font-family: inherit; display: flex; align-items: center; gap: 7px;
    transition: background .15s, transform .1s;
}
.btn-send-email:hover { background: #1e40af; }
.btn-send-email:active { transform: scale(.97); }
.btn-send-email:disabled { opacity: .5; cursor: not-allowed; }
.email-status { margin-top: 10px; font-size: 12px; text-align: center; min-height: 16px; }
.email-status.ok  { color: #4ade80; }
.email-status.err { color: #f87171; }
-->

<!-- ═══════════════════════════════════════════════════════════════════════
     HTML + JS — pegar JUSTO ANTES de </body> en ver_cotizacion.view.php
═══════════════════════════════════════════════════════════════════════════ -->

<!-- Modal: enviar cotización por correo -->
<div class="email-backdrop" id="email-backdrop" onclick="cerrarModalEmail(event)">
  <div class="email-modal" role="dialog" aria-modal="true" aria-labelledby="email-modal-title">

    <h2 id="email-modal-title">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="2" y="4" width="20" height="16" rx="2"/>
        <path d="m2 7 10 7 10-7"/>
      </svg>
      Enviar cotización por correo
    </h2>

    <div class="email-field">
      <label for="em-destino">Correo del cliente *</label>
      <input type="email" id="em-destino"
             placeholder="cliente@empresa.com"
             value="<?= htmlspecialchars($ctrl->cotizacion['cliente_email'] ?? '') ?>">
    </div>

    <div class="email-field">
      <label for="em-cc">Con copia (CC)</label>
      <input type="email" id="em-cc" placeholder="copia@empresa.com (opcional)">
    </div>

    <div class="email-field">
      <label for="em-mensaje">Mensaje personalizado</label>
      <textarea id="em-mensaje"
        placeholder="Estimado cliente, adjunto encontrará nuestra propuesta..."></textarea>
    </div>

    <div class="email-actions">
      <button type="button" class="btn-cancel-email" onclick="cerrarModalEmail()">Cancelar</button>
      <button type="button" class="btn-send-email" id="btn-send-email" onclick="enviarCorreo()">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="22" y1="2" x2="11" y2="13"/>
          <polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
        Enviar correo
      </button>
    </div>

    <div class="email-status" id="email-status"></div>
  </div>
</div>

<script>
// ── Modal correo ──────────────────────────────────────────────────────────
function abrirModalEmail() {
    document.getElementById('email-backdrop').classList.add('open');
    document.getElementById('em-destino').focus();
    document.getElementById('email-status').textContent = '';
    document.getElementById('email-status').className = 'email-status';
}

function cerrarModalEmail(e) {
    if (e && e.target !== document.getElementById('email-backdrop')) return;
    document.getElementById('email-backdrop').classList.remove('open');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.getElementById('email-backdrop').classList.remove('open');
});

async function enviarCorreo() {
    const destino = document.getElementById('em-destino').value.trim();
    const cc      = document.getElementById('em-cc').value.trim();
    const mensaje = document.getElementById('em-mensaje').value.trim();
    const status  = document.getElementById('email-status');
    const btn     = document.getElementById('btn-send-email');

    if (!destino) {
        status.textContent = 'Ingresa el correo del cliente.';
        status.className = 'email-status err';
        document.getElementById('em-destino').focus();
        return;
    }

    const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRx.test(destino)) {
        status.textContent = 'El correo no tiene un formato válido.';
        status.className = 'email-status err';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Enviando...`;
    status.textContent = '';
    status.className = 'email-status';

    try {
        const res = await fetch('../API/api_enviar_correo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cotizacion_id: <?= (int)$ctrl->id ?>,
                email_destino: destino,
                email_cc:      cc,
                mensaje:       mensaje,
            }),
        });

        const data = await res.json();

        if (data.ok) {
            status.textContent = `✓ Correo enviado a ${data.email_destino}`;
            status.className = 'email-status ok';
            btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Enviado`;
            setTimeout(() => {
                document.getElementById('email-backdrop').classList.remove('open');
                btn.disabled = false;
                btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Enviar correo`;
            }, 2200);
        } else {
            status.textContent = data.error || 'Error desconocido al enviar.';
            status.className = 'email-status err';
            btn.disabled = false;
            btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Enviar correo`;
        }
    } catch (err) {
        status.textContent = 'Error de conexión. Intenta nuevamente.';
        status.className = 'email-status err';
        btn.disabled = false;
        btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Enviar correo`;
    }
}
</script>