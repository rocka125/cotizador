<?php
/**
 * views/lista_precios.view.php — Diseño "Minimalista Industrial"
 * Misma funcionalidad, estética completamente diferente.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Fortress8 · Precios · Industrial</title>
<link rel="icon" href="assets/img/favicon.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<link rel="manifest" href="/cotizador/manifest.json">
<meta name="theme-color" content="#e85d04">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Cotizador">
<link rel="apple-touch-icon" href="/cotizador/assets/icons/icon-192.png">
<script>
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
      navigator.serviceWorker.register("/cotizador/service-worker.js")
        .then(r => console.log("SW registrado:", r.scope))
        .catch(e => console.error("SW error:", e));
    });
  }
</script>

<style>
/* ==============================================================
   DISEÑO "MINIMALISTA INDUSTRIAL" — Negro mate + acentos naranja
   ============================================================== */
*,
*::before,
*::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --bg-deep: #0a0a0c;
  --bg-panel: #111114;
  --bg-card: #16161a;
  --border-soft: #2a2a30;
  --border-glow: rgba(255, 140, 40, 0.3);
  --orange-glow: #ff8c1a;
  --orange-soft: #ffaa55;
  --orange-dim: rgba(255, 140, 40, 0.12);
  --text-light: #f0ece8;
  --text-muted: #9a9490;
  --text-dim: #5a5450;
  --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.6);
  --radius-card: 12px;
  --transition: all 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg-deep);
  color: var(--text-light);
  height: 100vh;
  height: 100dvh;
  overflow: hidden;
  position: relative;
}

/* Fondo sutil */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  z-index: 0;
  background: radial-gradient(ellipse 50% 40% at 20% 10%, rgba(255, 140, 40, 0.06) 0%, transparent 60%),
              radial-gradient(ellipse 40% 40% at 80% 90%, rgba(255, 80, 0, 0.04) 0%, transparent 55%);
  pointer-events: none;
}

.app-shell {
  display: grid;
  grid-template-columns: 56px 1fr;
  grid-template-rows: 52px 1fr;
  height: 100vh;
  height: 100dvh;
  position: relative;
  z-index: 1;
}

/* ===== TOPBAR ===== */
.topbar {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 16px;
  background: var(--bg-panel);
  border-bottom: 1px solid var(--border-soft);
  z-index: 20;
}

.topbar .brand {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: 'Fraunces', serif;
  font-weight: 700;
  font-size: 16px;
  letter-spacing: -0.2px;
  flex-shrink: 0;
}
.topbar .brand img {
  height: 24px;
  width: auto;
}
.topbar .brand .accent {
  color: var(--orange-glow);
}

.topbar .search-wrap {
  flex: 1;
  max-width: 280px;
  position: relative;
  margin: 0 auto;
}
.topbar .search-wrap i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-dim);
  font-size: 14px;
}
.topbar .search-wrap input {
  width: 100%;
  padding: 5px 12px 5px 32px;
  background: var(--bg-card);
  border: 1px solid var(--border-soft);
  border-radius: 8px;
  color: var(--text-light);
  font-size: 12px;
  outline: none;
  transition: var(--transition);
  font-family: inherit;
  height: 28px;
}
.topbar .search-wrap input:focus {
  border-color: var(--orange-glow);
}

.topbar .tc-pill {
  font-size: 11px;
  color: var(--text-muted);
  background: var(--bg-card);
  border: 1px solid var(--border-soft);
  border-radius: 6px;
  padding: 4px 12px;
  display: flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}
.topbar .tc-pill strong {
  color: #8fe3a6;
  font-weight: 500;
}

.topbar .user-area {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
}
.topbar .user-area .avatar {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  background: linear-gradient(135deg, #ffb46b, var(--orange-glow));
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 10px;
  color: #1a0d04;
  cursor: pointer;
  position: relative;
  flex-shrink: 0;
  transition: var(--transition);
}
.topbar .user-area .avatar:hover {
  filter: brightness(1.1);
}
.topbar .user-area .avatar .user-menu {
  display: none;
  position: absolute;
  top: 36px;
  right: 0;
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 8px;
  min-width: 170px;
  padding: 0;
  z-index: 200;
  overflow: hidden;
  box-shadow: var(--shadow-card);
}
.topbar .user-area .avatar .user-menu.open {
  display: block;
}
.topbar .user-area .avatar .user-menu .user-name {
  padding: 8px 14px 4px;
  font-weight: 500;
  font-size: 11px;
  color: var(--text-light);
}
.topbar .user-area .avatar .user-menu a {
  display: block;
  padding: 7px 14px;
  font-size: 11px;
  color: var(--text-muted);
  text-decoration: none;
  transition: var(--transition);
}
.topbar .user-area .avatar .user-menu a:hover {
  background: rgba(255, 255, 255, 0.04);
  color: var(--text-light);
}
.topbar .user-area .avatar .user-menu .sep {
  height: 1px;
  background: var(--border-soft);
}

.topbar .btn-new {
  background: var(--orange-glow);
  border: none;
  padding: 6px 16px;
  border-radius: 6px;
  color: #0a0a0c;
  font-weight: 600;
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition: var(--transition);
  text-decoration: none;
}
.topbar .btn-new:hover {
  background: #ff9a33;
  transform: scale(1.02);
}

.hamburger {
  display: none;
  background: none;
  border: none;
  color: var(--text-light);
  font-size: 18px;
  cursor: pointer;
}

/* ============================================================
   SIDEBAR — modificado con fondo negro e íconos más grandes
   ============================================================ */
.sidebar {
  background: #0B0708;               /* fondo negro sólido */
  border-right: 1px solid var(--border-soft);
  padding: 16px 0;                   /* más aire arriba/abajo */
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;                          /* separación entre íconos */
  overflow: hidden;
  box-shadow: 4px 0 20px rgba(0,0,0,.4); /* profundidad */
}
.sidebar .si {
  width: 44px;                       /* más grande */
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;           /* centrado perfecto */
  cursor: pointer;
  text-decoration: none;
  transition: var(--transition);
  color: var(--text-dim);
}
.sidebar .si i {
  font-size: 22px;                   /* más grande */
  transition: color 0.18s;
  line-height: 1;                    /* evita desbordes */
}
.sidebar .si:hover:not(.on) {
  background: var(--bg-card);
  color: var(--text-muted);
}
.sidebar .si.on {
  background: var(--orange-dim);
  color: var(--orange-glow);
  border: 1px solid var(--border-glow);
}
.sidebar .si-sep {
  height: 1px;
  width: 32px;                       /* más ancho */
  background: var(--border-soft);
  margin: 4px 0;
}

/* ===== MAIN CONTENT ===== */
.main-content {
  overflow-y: auto;
  padding: 16px 20px 20px;
  position: relative;
  z-index: 1;
}
.main-content::-webkit-scrollbar {
  width: 4px;
}
.main-content::-webkit-scrollbar-thumb {
  background: var(--border-soft);
  border-radius: 2px;
}

/* ===== KPI ===== */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 24px;
}

.kpi-widget {
  background: var(--bg-card);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-card);
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: var(--transition);
}
.kpi-widget:hover {
  border-color: var(--border-glow);
}

.kpi-icon {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
  background: var(--orange-dim);
  color: var(--orange-glow);
}

.kpi-content {
  flex: 1;
}
.kpi-content .kpi-label {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-dim);
  font-weight: 500;
}
.kpi-content .kpi-value {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-light);
  line-height: 1.2;
}
.kpi-content .kpi-sub {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 1px;
}

/* ===== GRID ===== */
.main-grid {
  display: grid;
  grid-template-columns: 1.2fr 1.8fr;
  gap: 16px;
  margin-bottom: 16px;
}

/* ===== TARJETAS ===== */
.glass-card {
  background: var(--bg-card);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-card);
  padding: 16px 20px 20px;
  transition: var(--transition);
}
.glass-card:hover {
  border-color: var(--border-glow);
}

.glass-card .card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.glass-card .card-header h3 {
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}
.glass-card .card-header h3 i {
  color: var(--orange-glow);
}
.glass-card .card-header .badge {
  font-size: 10px;
  background: var(--orange-dim);
  padding: 2px 12px;
  border-radius: 4px;
  border: 1px solid var(--border-soft);
  color: var(--text-muted);
  font-weight: 500;
}

/* ===== STEPPER HORIZONTAL ===== */
.stepper {
  display: flex;
  gap: 4px;
  margin-bottom: 16px;
}
.step {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--border-soft);
  border-radius: 8px;
  transition: var(--transition);
}
.step .num {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--orange-dim);
  color: var(--orange-glow);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  font-family: monospace;
  flex-shrink: 0;
}
.step.done .num {
  background: var(--orange-glow);
  color: #0a0a0c;
}
.step .label {
  font-size: 11px;
  font-weight: 500;
  color: var(--text-muted);
}
.step.done .label {
  color: var(--text-light);
}
.step .line {
  flex: 1;
  height: 1px;
  background: var(--border-soft);
  margin: 0 4px;
}
.step:last-child .line { display: none; }

.upload-zone {
  border: 2px dashed var(--border-soft);
  border-radius: 8px;
  padding: 18px;
  text-align: center;
  cursor: pointer;
  transition: var(--transition);
  background: rgba(255, 255, 255, 0.01);
}
.upload-zone:hover,
.upload-zone.drag {
  border-color: var(--orange-glow);
  background: var(--orange-dim);
}
.upload-zone input { display: none; }
.upload-zone .icon-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  margin: 0 auto 6px;
  background: var(--orange-dim);
  color: var(--orange-glow);
}
.upload-zone .label { font-weight: 600; font-size: 13px; }
.upload-zone .sub { font-size: 11px; color: var(--text-dim); }

.file-chip {
  display: none;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
  padding: 6px 14px;
  background: var(--orange-dim);
  border: 1px solid var(--border-soft);
  border-radius: 6px;
  font-size: 12px;
  color: var(--text-light);
}
.file-chip.show { display: flex; }
.file-chip i { color: var(--orange-glow); }
.file-chip .fc-name { font-family: monospace; color: var(--orange-glow); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.progress-wrap {
  display: none;
  margin-top: 12px;
  padding: 10px 14px;
  background: rgba(0,0,0,0.3);
  border: 1px solid var(--border-soft);
  border-radius: 8px;
}
.progress-wrap.show { display: block; }
.progress-label {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  color: var(--text-dim);
  margin-bottom: 6px;
}
.progress-label #progress-pct { color: var(--orange-glow); font-weight: 700; }
.progress-bar-bg {
  height: 4px;
  background: var(--border-soft);
  border-radius: 2px;
  overflow: hidden;
}
.progress-bar {
  height: 100%;
  background: var(--orange-glow);
  width: 0%;
  border-radius: 2px;
  transition: width 0.3s;
}

.preview-wrap {
  display: none;
  margin-top: 12px;
}
.preview-wrap.show { display: block; }
.preview-wrap .preview-title {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-dim);
  margin-bottom: 6px;
}
.preview-chips {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 6px;
  margin-bottom: 8px;
}
.preview-chip {
  font-size: 11px;
  background: var(--orange-dim);
  padding: 4px 12px;
  border-radius: 4px;
  border: 1px solid var(--border-soft);
  color: var(--text-muted);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.preview-chip strong { color: var(--orange-glow); font-family: monospace; }
.preview-total { font-size: 12px; color: #4ade80; font-weight: 600; display: flex; align-items: center; gap: 4px; }

.version-name-wrap {
  display: none;
  margin-top: 12px;
}
.version-name-wrap label {
  font-size: 10px;
  color: var(--text-dim);
  display: block;
  margin-bottom: 4px;
}
.version-name-wrap .vname-input-wrap {
  position: relative;
}
.version-name-wrap .vname-input-wrap i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-dim);
  font-size: 14px;
}
.version-name-wrap input {
  width: 100%;
  padding: 7px 12px 7px 34px;
  background: rgba(0,0,0,0.4);
  border: 1px solid var(--border-soft);
  border-radius: 6px;
  color: var(--text-light);
  font-size: 12px;
  outline: none;
  font-family: inherit;
  transition: var(--transition);
}
.version-name-wrap input:focus { border-color: var(--orange-glow); }

.btn-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
  flex-wrap: wrap;
}
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 18px;
  border-radius: 6px;
  font-weight: 500;
  font-size: 12px;
  cursor: pointer;
  border: none;
  font-family: inherit;
  transition: var(--transition);
  text-decoration: none;
  background: var(--bg-card);
  color: var(--text-muted);
  border: 1px solid var(--border-soft);
}
.btn-primary {
  background: var(--orange-glow);
  color: #0a0a0c;
  border: none;
}
.btn-primary:hover { background: #ff9a33; transform: scale(1.02); }
.btn-primary:disabled { opacity: 0.4; cursor: not-allowed; transform: none !important; }
.btn-ghost { background: transparent; }
.btn-ghost:hover { background: var(--bg-card); color: var(--text-light); }

.help-box {
  margin-top: 12px;
  padding: 10px 14px;
  background: rgba(0,0,0,0.3);
  border-radius: 8px;
  border: 1px solid var(--border-soft);
  font-size: 11px;
  color: var(--text-dim);
  line-height: 1.6;
}
.help-box strong { color: var(--text-muted); }
.help-box .highlight { color: var(--orange-glow); }

/* ===== VERSIONES ===== */
.ver-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.ver-item {
  display: grid;
  grid-template-columns: 12px 1fr auto auto auto;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  background: rgba(255,255,255,0.02);
  border-radius: 6px;
  border: 1px solid transparent;
  transition: var(--transition);
}
.ver-item:hover {
  border-color: var(--border-soft);
}
.ver-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.ver-dot.active {
  background: #4ade80;
  box-shadow: 0 0 10px rgba(74,222,128,0.3);
}
.ver-dot.old { background: var(--text-dim); }
.ver-name { font-weight: 500; font-size: 13px; }
.ver-meta { font-size: 10px; color: var(--text-dim); font-family: monospace; }
.ver-count { font-family: monospace; font-size: 13px; color: var(--orange-glow); }
.ver-badge {
  font-size: 9px;
  font-weight: 600;
  text-transform: uppercase;
  padding: 1px 10px;
  border-radius: 4px;
}
.ver-badge.active {
  background: rgba(74,222,128,0.1);
  color: #4ade80;
  border: 1px solid rgba(74,222,128,0.15);
}
.ver-badge.old {
  background: var(--bg-card);
  color: var(--text-dim);
  border: 1px solid var(--border-soft);
}
.ver-actions {
  display: flex;
  gap: 4px;
}
.btn-ver-activar {
  background: rgba(74,222,128,0.06);
  border: 1px solid rgba(74,222,128,0.12);
  color: #4ade80;
  padding: 1px 12px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
}
.btn-ver-activar:hover { background: rgba(74,222,128,0.12); }
.btn-ver-eliminar {
  background: transparent;
  border: 1px solid var(--border-soft);
  color: var(--text-dim);
  width: 24px;
  height: 24px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 12px;
  transition: var(--transition);
}
.btn-ver-eliminar:hover {
  border-color: #e54818;
  color: #e54818;
  background: rgba(229,72,24,0.06);
}

/* ===== CATEGORÍAS (GRID) ===== */
.cat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 8px;
}
.cat-item {
  background: rgba(255,255,255,0.02);
  border: 1px solid var(--border-soft);
  border-radius: 8px;
  padding: 10px 12px;
  cursor: pointer;
  transition: var(--transition);
  text-align: center;
}
.cat-item:hover {
  border-color: var(--border-glow);
  background: var(--orange-dim);
}
.cat-item.active-cat {
  border-color: var(--orange-glow);
  background: var(--orange-dim);
}
.cat-item .cat-name {
  font-size: 12px;
  font-weight: 500;
  color: var(--text-muted);
}
.cat-item.active-cat .cat-name {
  color: var(--orange-glow);
}
.cat-item .cat-count {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-light);
  display: block;
  margin-top: 2px;
}
.cat-item .cat-bar {
  height: 3px;
  background: var(--orange-glow);
  border-radius: 2px;
  margin-top: 6px;
  transition: width 0.3s;
}

/* ===== BUSCADOR GLOBAL ===== */
.global-search-box {
  margin-bottom: 12px;
  position: relative;
}
.global-search-box input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  background: rgba(0,0,0,0.4);
  border: 1px solid var(--border-soft);
  border-radius: 6px;
  color: var(--text-light);
  font-size: 12px;
  outline: none;
  font-family: inherit;
  transition: var(--transition);
}
.global-search-box input:focus { border-color: var(--orange-glow); }
.global-search-box i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-dim);
  font-size: 16px;
}
.global-search-box .hint {
  font-size: 10px;
  color: var(--text-dim);
  margin-top: 4px;
  padding-left: 12px;
}

/* ===== DRAWER ===== */
.drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  z-index: 100;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s;
}
.drawer-backdrop.open { opacity: 1; pointer-events: auto; }

.drawer {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 600px;
  max-width: 94vw;
  background: var(--bg-panel);
  border-left: 1px solid var(--border-soft);
  z-index: 101;
  display: flex;
  flex-direction: column;
  transform: translateX(100%);
  transition: transform 0.3s cubic-bezier(0.22,1,0.36,1);
  box-shadow: -4px 0 32px rgba(0,0,0,0.5);
}
.drawer.open { transform: translateX(0); }

.drawer-header {
  padding: 14px 20px;
  border-bottom: 1px solid var(--border-soft);
  flex-shrink: 0;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.drawer-header .title { flex: 1; }
.drawer-header .title .cat-name { font-size: 18px; font-weight: 700; }
.drawer-header .title .cat-meta { font-size: 11px; color: var(--text-dim); font-family: monospace; margin-top: 2px; }
.drawer-header .close-btn {
  background: transparent;
  border: 1px solid var(--border-soft);
  border-radius: 4px;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
  cursor: pointer;
  font-size: 16px;
  transition: var(--transition);
  flex-shrink: 0;
}
.drawer-header .close-btn:hover { background: var(--bg-card); color: var(--text-light); }

.drawer-search {
  padding: 10px 20px;
  border-bottom: 1px solid var(--border-soft);
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}
.drawer-search .dsw {
  flex: 1;
  position: relative;
}
.drawer-search .dsw input {
  width: 100%;
  padding: 6px 12px 6px 32px;
  background: rgba(0,0,0,0.4);
  border: 1px solid var(--border-soft);
  border-radius: 6px;
  color: var(--text-light);
  font-size: 12px;
  outline: none;
  font-family: inherit;
}
.drawer-search .dsw input:focus { border-color: var(--orange-glow); }
.drawer-search .dsw i {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-dim);
}
.drawer-search .view-toggle {
  display: flex;
  gap: 2px;
  background: var(--bg-card);
  border: 1px solid var(--border-soft);
  border-radius: 6px;
  padding: 2px;
}
.drawer-search .view-toggle button {
  background: transparent;
  border: none;
  padding: 3px 10px;
  border-radius: 4px;
  color: var(--text-dim);
  cursor: pointer;
  font-size: 14px;
  transition: var(--transition);
}
.drawer-search .view-toggle button.on {
  background: var(--orange-glow);
  color: #0a0a0c;
}

.drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 8px 12px;
}
.drawer-body::-webkit-scrollbar { width: 4px; }
.drawer-body::-webkit-scrollbar-thumb { background: var(--border-soft); border-radius: 2px; }

.d-table {
  width: 100%;
  border-collapse: collapse;
}
.d-table th {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-dim);
  padding: 6px 10px;
  text-align: left;
  border-bottom: 1px solid var(--border-soft);
  position: sticky;
  top: 0;
  background: var(--bg-panel);
}
.d-table th.r { text-align: right; }
.d-table td {
  padding: 6px 10px;
  border-bottom: 1px solid var(--border-soft);
  font-size: 12px;
  color: var(--text-muted);
  vertical-align: middle;
}
.d-table tr:hover td { background: rgba(255,255,255,0.02); }
.d-sku { font-family: monospace; color: var(--orange-glow); font-weight: 500; }
.d-desc { line-height: 1.4; max-width: 260px; }
.d-price { font-family: monospace; color: var(--text-light); text-align: right; font-weight: 500; }

.d-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 10px;
  padding: 6px 0;
}
.d-card {
  background: var(--bg-card);
  border: 1px solid var(--border-soft);
  border-radius: 8px;
  padding: 10px 14px;
  transition: var(--transition);
}
.d-card:hover { border-color: var(--border-glow); }
.d-card .d-sku { font-size: 11px; }
.d-card .d-desc { font-size: 12px; color: var(--text-light); margin: 4px 0 6px; line-height: 1.3; }
.d-card .d-price { font-size: 16px; color: var(--orange-glow); text-align: left; font-weight: 700; }

.drawer-footer {
  padding: 8px 20px;
  border-top: 1px solid var(--border-soft);
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  color: var(--text-dim);
  font-family: monospace;
  flex-shrink: 0;
}

/* ===== MODAL ===== */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  z-index: 200;
  display: none;
  align-items: center;
  justify-content: center;
}
.modal-backdrop.open { display: flex; }
.modal {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: 12px;
  padding: 24px 28px;
  max-width: 400px;
  width: 92%;
  box-shadow: var(--shadow-card);
}
.modal .icon { font-size: 28px; margin-bottom: 6px; }
.modal .title { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
.modal .body { font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 18px; }
.modal .body strong { color: var(--text-light); }
.modal .actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}
.modal .actions .btn-cancel {
  background: transparent;
  border: 1px solid var(--border-soft);
  padding: 5px 16px;
  border-radius: 6px;
  color: var(--text-muted);
  cursor: pointer;
  font-family: inherit;
  font-size: 12px;
  transition: var(--transition);
}
.modal .actions .btn-cancel:hover { background: var(--bg-card); }
.modal .actions .btn-confirm {
  padding: 5px 18px;
  border-radius: 6px;
  border: none;
  font-weight: 600;
  font-family: inherit;
  font-size: 12px;
  cursor: pointer;
  transition: var(--transition);
}
.modal .actions .btn-confirm.green { background: #4ade80; color: #0a0a0c; }
.modal .actions .btn-confirm.green:hover { filter: brightness(1.1); }
.modal .actions .btn-confirm.red { background: #e54818; color: #fff; }
.modal .actions .btn-confirm.red:hover { filter: brightness(1.1); }
.modal .actions .btn-confirm:disabled { opacity: 0.4; cursor: not-allowed; }

.alert {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  margin-bottom: 14px;
  border: 1px solid var(--border-soft);
  background: rgba(0,0,0,0.3);
}
.alert.success { border-color: rgba(74,222,128,0.15); color: #4ade80; }
.alert.error { border-color: rgba(229,72,24,0.15); color: #e54818; }
.alert a { color: inherit; font-weight: 600; text-decoration: underline; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
  .main-grid { grid-template-columns: 1fr; }
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .app-shell { grid-template-columns: 1fr; }
  .sidebar { display: none; position: fixed; top: 0; left: 0; bottom: 0; width: 56px; z-index: 210; }
  .sidebar.open { display: flex; box-shadow: 4px 0 20px rgba(0,0,0,0.5); }
  .hamburger { display: block; }
  .topbar { flex-wrap: wrap; gap: 6px; padding: 6px 12px; height: auto; }
  .topbar .search-wrap { order: 10; flex: 1 1 100%; max-width: 100%; margin: 4px 0 0; }
  .topbar .brand { font-size: 14px; }
  .topbar .btn-new { font-size: 11px; padding: 4px 12px; }
  .kpi-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
  .kpi-widget { padding: 8px 12px; }
  .kpi-icon { width: 28px; height: 28px; font-size: 14px; }
  .kpi-content .kpi-value { font-size: 18px; }
  .glass-card { padding: 12px 14px; }
  .drawer { width: 100%; max-width: 100%; }
  .ver-item { grid-template-columns: 10px 1fr auto; gap: 6px; }
  .ver-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; }
}
@media (max-width: 480px) {
  .kpi-grid { grid-template-columns: 1fr; }
  .topbar .tc-pill { display: none; }
  .cat-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
}
</style>
</head>
<body>

<!-- ===== MODAL ===== -->
<div class="modal-backdrop" id="modal-backdrop">
  <div class="modal">
    <div class="icon" id="modal-icon">⚡</div>
    <div class="title" id="modal-title">Confirmar acción</div>
    <div class="body" id="modal-body"></div>
    <div class="actions">
      <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
      <button class="btn-confirm green" id="modal-confirm-btn" onclick="modalConfirmAction()">Confirmar</button>
    </div>
  </div>
</div>

<!-- ===== DRAWER ===== -->
<div class="drawer-backdrop" id="drawer-backdrop" onclick="closeDrawer()"></div>
<div class="drawer" id="drawer">
  <div class="drawer-header">
    <div class="title">
      <div class="cat-name" id="d-cat-name">—</div>
      <div class="cat-meta" id="d-cat-meta"></div>
    </div>
    <button class="close-btn" onclick="closeDrawer()">✕</button>
  </div>
  <div class="drawer-search">
    <div class="dsw">
      <i class="ti ti-search"></i>
      <input type="text" id="d-search" placeholder="Buscar SKU o descripción…" oninput="filterDrawer()">
    </div>
    <div class="view-toggle">
      <button type="button" class="on" id="dview-table" onclick="setDrawerView('table')"><i class="ti ti-list"></i></button>
      <button type="button" id="dview-cards" onclick="setDrawerView('cards')"><i class="ti ti-layout-grid"></i></button>
    </div>
  </div>
  <div class="drawer-body" id="d-body">
    <div class="drawer-loading" style="padding:16px;text-align:center;color:var(--text-dim);">
      <div class="spinner" style="display:inline-block;width:16px;height:16px;border:2px solid var(--border-soft);border-top-color:var(--orange-glow);border-radius:50%;animation:spin .7s linear infinite;"></div>
      Cargando…
    </div>
  </div>
  <div class="drawer-footer">
    <span id="d-footer"></span>
    <span>USD · Fortinet AMER</span>
  </div>
</div>

<!-- ===== APP SHELL ===== -->
<div class="app-shell">

  <!-- TOPBAR -->
  <header class="topbar">
    <button class="hamburger" onclick="toggleSidenav()" aria-label="Menú">☰</button>
    <div class="brand">
      <img src="assets/img/logoss.png" alt="Fortress8">
      <span>FORTRESS<span class="accent">8</span></span>
    </div>

    <div class="tc-pill">
      USD/MXN <strong id="tc-val">—</strong>
    </div>

    <div class="user-area">
      <div class="avatar" onclick="toggleUserMenu()" id="user-trigger">
        <?= htmlspecialchars($auth->iniciales()) ?>
        <div class="user-menu" id="user-menu">
          <div class="user-name">
            <?= htmlspecialchars($auth->usuarioNombre()) ?>
            (<?= $auth->esAdmin() ? 'Admin' : 'Vendedor' ?>)
          </div>
          <div class="sep"></div>
          <a href="cotizaciones.php?action=nueva"><i class="ti ti-file-plus"></i> Nueva cotización</a>
          <a href="lista_cotizaciones.php"><i class="ti ti-file-invoice"></i> Mis cotizaciones</a>
          <a href="lista_precios.php"><i class="ti ti-list-details"></i> Lista de precios</a>
          <a href="comparar_listas.php"><i class="ti ti-copy"></i> Comparar listas</a>
          <div class="sep"></div>
          <a href="logout.php"><i class="ti ti-logout"></i> Cerrar sesión</a>
        </div>
      </div>
      <a href="cotizaciones.php?action=nueva" class="btn-new">
        <i class="ti ti-plus"></i> Nueva
      </a>
    </div>
  </header>

  <!-- SIDEBAR (modificado) -->
  <nav class="sidebar" id="sidenav">
    <a class="si" href="dashboard.php" title="Dashboard"><i class="ti ti-layout-dashboard"></i></a>
    <a class="si" href="lista_cotizaciones.php" title="Cotizaciones"><i class="ti ti-file-invoice"></i></a>
    <a class="si" href="seguimiento.php" title="Seguimiento"><i class="ti ti-timeline"></i></a>
    <div class="si-sep"></div>
    <a class="si on" href="lista_precios.php" title="Lista de precios"><i class="ti ti-list-details"></i></a>
    <a class="si" href="comparar_listas.php" title="Comparar listas"><i class="ti ti-copy"></i></a>
    <?php if ($auth->esAdmin()): ?>
    <div class="si-sep"></div>
    <a class="si" href="auditoria.php" title="Auditoría"><i class="ti ti-shield"></i></a>
    <?php endif; ?>
    <div class="si-sep"></div>
    <a class="si" href="logout.php" title="Cerrar sesión"><i class="ti ti-logout"></i></a>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="main-content">

    <div id="js-alert" style="display:none;"></div>

    <!-- KPI -->
    <div class="kpi-grid">
      <div class="kpi-widget">
        <div class="kpi-icon"><i class="ti ti-package"></i></div>
        <div class="kpi-content">
          <div class="kpi-label">Productos activos</div>
          <div class="kpi-value"><?= number_format($ctrl->totalActual) ?></div>
          <div class="kpi-sub">Versión actual</div>
        </div>
      </div>
      <div class="kpi-widget">
        <div class="kpi-icon"><i class="ti ti-category"></i></div>
        <div class="kpi-content">
          <div class="kpi-label">Categorías</div>
          <div class="kpi-value"><?= count($ctrl->categorias) ?></div>
          <div class="kpi-sub">Líneas de producto</div>
        </div>
      </div>
      <div class="kpi-widget">
        <div class="kpi-icon"><i class="ti ti-history"></i></div>
        <div class="kpi-content">
          <div class="kpi-label">Versiones guardadas</div>
          <div class="kpi-value"><?= count($ctrl->versiones) ?></div>
          <div class="kpi-sub">Historial completo</div>
        </div>
      </div>
      <div class="kpi-widget">
        <div class="kpi-icon"><i class="ti ti-file-spreadsheet"></i></div>
        <div class="kpi-content">
          <div class="kpi-label">Importación</div>
          <div class="kpi-value" style="font-size:18px; color:var(--orange-glow); background:none; -webkit-text-fill-color:unset;">SheetJS</div>
          <div class="kpi-sub">Sin Composer</div>
        </div>
      </div>
    </div>

    <!-- GRID -->
    <div class="main-grid">

      <!-- COLUMNA IZQUIERDA -->
      <div class="glass-card">
        <div class="card-header">
          <h3><i class="ti ti-upload"></i> Importar Excel</h3>
          <span class="badge">.xlsx / .xls</span>
        </div>

        <!-- Stepper horizontal -->
        <div class="stepper">
          <div class="step done" id="step-1">
            <span class="num">1</span>
            <span class="label">Archivo</span>
            <span class="line"></span>
          </div>
          <div class="step" id="step-2">
            <span class="num">2</span>
            <span class="label">Revisar</span>
            <span class="line"></span>
          </div>
          <div class="step" id="step-3">
            <span class="num">3</span>
            <span class="label">Guardar</span>
          </div>
        </div>

        <label class="upload-zone" for="xlsx-input" id="drop-zone">
          <div class="icon-circle"><i class="ti ti-file-spreadsheet"></i></div>
          <div class="label">Arrastra o haz clic</div>
          <div class="sub">.xlsx, .xls</div>
          <div class="file-chip" id="file-chip"><i class="ti ti-file-check"></i><span class="fc-name"></span></div>
          <input type="file" id="xlsx-input" accept=".xlsx,.xls">
        </label>

        <div class="progress-wrap" id="progress-wrap">
          <div class="progress-label">
            <span id="progress-text">Leyendo…</span>
            <span id="progress-pct">0%</span>
          </div>
          <div class="progress-bar-bg"><div class="progress-bar" id="progress-bar"></div></div>
        </div>

        <div class="preview-wrap" id="preview-wrap">
          <div class="preview-title">Hojas detectadas</div>
          <div class="preview-chips" id="preview-chips"></div>
          <div class="preview-total" id="preview-total"><i class="ti ti-circle-check"></i> <span></span></div>
          <div id="preview-omitidas" style="display:none;margin-top:8px;padding:8px 12px;border-radius:8px;background:rgba(229,72,24,0.1);border:1px solid rgba(229,72,24,0.3);color:#e54818;font-size:12px;line-height:1.5;"></div>
        </div>

        <div class="version-name-wrap" id="vname-wrap">
          <label for="vname-input">Nombre para esta versión</label>
          <div class="vname-input-wrap">
            <i class="ti ti-tag"></i>
            <input type="text" id="vname-input" placeholder="Ej: AMER Q3 2026">
          </div>
        </div>

        <div class="btn-actions">
          <button class="btn btn-primary" id="btn-guardar" disabled onclick="guardarLista()">
            <i class="ti ti-database"></i> Guardar versión
          </button>
          <a href="dashboard.php" class="btn btn-ghost"><i class="ti ti-arrow-left"></i> Volver</a>
        </div>

        <div class="help-box">
          <strong>¿Cómo funciona?</strong><br>
          1. El Excel se lee <span class="highlight">directo en el navegador</span>.<br>
          2. Solo se importan las hojas de producto Fortinet.<br>
          3. Al guardar se crea una <span class="highlight">nueva versión</span>.<br>
          4. El cotizador usa la versión activa.
        </div>
      </div>

      <!-- COLUMNA DERECHA -->
      <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Versiones -->
        <div class="glass-card">
          <div class="card-header">
            <h3><i class="ti ti-clock-history"></i> Historial de versiones</h3>
            <span class="badge"><?= count($ctrl->versiones) ?></span>
          </div>
          <?php if (empty($ctrl->versiones)): ?>
            <div style="padding:16px 0;text-align:center;color:var(--text-dim);">
              <i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:4px;opacity:0.4;"></i>
              Sin versiones importadas.
            </div>
          <?php else: ?>
            <div class="ver-list">
              <?php foreach ($ctrl->versiones as $v): ?>
              <div class="ver-item" id="ver-row-<?= $v['id'] ?>">
                <div class="ver-dot <?= $v['activa'] ? 'active' : 'old' ?>" id="ver-dot-<?= $v['id'] ?>"></div>
                <div class="ver-name"><?= htmlspecialchars($v['nombre']) ?></div>
                <div class="ver-meta">
                  <?= date('d/m/Y H:i', strtotime($v['created_at'])) ?>
                  <?= $v['descripcion'] ? ' · ' . htmlspecialchars($v['descripcion']) : '' ?>
                </div>
                <div class="ver-count"><?= number_format($v['total_skus']) ?></div>
                <span class="ver-badge <?= $v['activa'] ? 'active' : 'old' ?>" id="ver-badge-<?= $v['id'] ?>">
                  <?= $v['activa'] ? 'Activa' : 'Archivada' ?>
                </span>
                <div class="ver-actions">
                  <?php if (!$v['activa']): ?>
                  <button class="btn-ver-activar" onclick="activarVersion(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['nombre'])) ?>')">
                    <i class="ti ti-play"></i> Activar
                  </button>
                  <button class="btn-ver-eliminar" onclick="eliminarVersion(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($v['nombre'])) ?>')">
                    ✕
                  </button>
                  <?php else: ?>
                  <span style="width:60px;"></span>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Buscador global -->
        <?php if ($ctrl->versionActiva): ?>
        <div class="glass-card" style="padding:10px 16px;">
          <div class="global-search-box">
            <i class="ti ti-search"></i>
            <input type="text" id="g-search2" placeholder="Buscar producto en toda la lista…"
                   oninput="globalSearchInput2()" onkeydown="if(event.key==='Enter'){clearTimeout(_globalSearchTimer);const q=this.value.trim();if(q.length>=2)runGlobalSearch(q);}">
            <div class="hint">Busca en <?= number_format($ctrl->totalActual) ?> referencias activas.</div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Categorías (grid) -->
        <?php if ($ctrl->versionActiva && !empty($ctrl->categorias)): ?>
        <div class="glass-card">
          <div class="card-header">
            <h3><i class="ti ti-category"></i> Categorías</h3>
            <span class="badge"><?= count($ctrl->categorias) ?></span>
          </div>
          <div class="cat-grid">
            <?php $maxN = max(array_column($ctrl->categorias, 'n')); ?>
            <?php foreach ($ctrl->categorias as $cat): ?>
            <div class="cat-item"
                 data-categoria="<?= htmlspecialchars($cat['categoria'], ENT_QUOTES, 'UTF-8') ?>"
                 data-total="<?= (int)$cat['n'] ?>"
                 data-vid="<?= (int)$ctrl->versionActiva['id'] ?>">
              <div class="cat-name"><?= htmlspecialchars($cat['categoria']) ?></div>
              <span class="cat-count"><?= number_format($cat['n']) ?></span>
              <div class="cat-bar" style="width:<?= round($cat['n'] / $maxN * 100) ?>%;"></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

  </main>
</div>

<script>
// ================================================================
//  NAVEGACIÓN Y UTILIDADES (mismo JavaScript que antes, con pequeños ajustes)
// ================================================================
function toggleSidenav() {
  document.getElementById('sidenav').classList.toggle('open');
}
document.addEventListener('click', function(e) {
  const nav = document.getElementById('sidenav');
  const hamb = document.querySelector('.hamburger');
  if (nav && hamb && !nav.contains(e.target) && !hamb.contains(e.target)) {
    nav.classList.remove('open');
  }
});

function toggleUserMenu() {
  document.getElementById('user-menu').classList.toggle('open');
}
document.addEventListener('click', e => {
  const t = document.getElementById('user-trigger');
  if (t && !t.contains(e.target)) document.getElementById('user-menu')?.classList.remove('open');
});

// ── Tipo de cambio ──
fetch('API/api_tipo_cambio.php').then(r => r.json()).then(d => {
  const v = d.MXN || d.mxn || null;
  if (v) document.getElementById('tc-val').textContent = parseFloat(v).toFixed(2);
}).catch(() => { document.getElementById('tc-val').textContent = 'N/D'; });

// ── Búsqueda global ──
let _globalSearchTimer = null;
function globalSearchInput2() {
  const q = document.getElementById('g-search2').value;
  clearTimeout(_globalSearchTimer);
  if (q.trim().length < 2) return;
  _globalSearchTimer = setTimeout(() => runGlobalSearch(q.trim()), 350);
}

async function runGlobalSearch(q) {
  if (activeDrawerItem) { activeDrawerItem.classList.remove('active-cat'); activeDrawerItem = null; }
  drawerMode = 'global';

  document.getElementById('drawer').classList.add('open');
  document.getElementById('drawer-backdrop').classList.add('open');
  document.getElementById('d-cat-name').textContent = `Resultados para "${q}"`;
  document.getElementById('d-cat-meta').textContent = 'Buscando en toda la lista…';
  document.getElementById('d-search').value = q;
  document.getElementById('d-body').innerHTML =
    '<div class="drawer-loading" style="padding:16px;text-align:center;color:var(--text-dim);"><div class="spinner" style="display:inline-block;width:16px;height:16px;border:2px solid var(--border-soft);border-top-color:var(--orange-glow);border-radius:50%;animation:spin .7s linear infinite;"></div> Buscando…</div>';
  document.getElementById('d-footer').textContent = '';
  document.body.style.overflow = 'hidden';

  try {
    const res  = await fetch(`API/api_precios.php?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    const productos = (data.productos || []).map(p => ({
      codigo: p.sku, descripcion: p.desc || p.producto, precio: p.price, categoria: p.sheet,
    }));
    drawerProds = productos;
    document.getElementById('d-cat-meta').textContent = productos.length + ' resultado' + (productos.length !== 1 ? 's' : '');
    renderDrawer(drawerProds);
  } catch(e) {
    document.getElementById('d-body').innerHTML =
      '<div class="drawer-empty" style="padding:16px;text-align:center;color:var(--text-dim);">Error al buscar productos.</div>';
  }
}

// ================================================================
//  LECTURA EXCEL (SheetJS)
// ================================================================
let parsedProducts = [];

document.getElementById('xlsx-input').addEventListener('change', function() {
  if (this.files && this.files[0]) readExcel(this.files[0]);
});

const dz = document.getElementById('drop-zone');
dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('drag'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag'));
dz.addEventListener('drop', e => {
  e.preventDefault(); dz.classList.remove('drag');
  const f = e.dataTransfer.files[0];
  if (f) { document.getElementById('xlsx-input').files = e.dataTransfer.files; readExcel(f); }
});

function setProgress(pct, text) {
  document.getElementById('progress-wrap').classList.add('show');
  document.getElementById('progress-bar').style.width = pct + '%';
  document.getElementById('progress-pct').textContent = pct + '%';
  document.getElementById('progress-text').textContent = text;
}

const SHEET_CATEGORY_MAP = {
  'FortiGate':'FortiGate','FortiWiFi':'FortiWiFi',
  'FortiGate Chassis Platforms':'FortiGate Chassis Platforms',
  'FortiGate VM':'FortiGate VM','Rugged Products':'Rugged Products',
  'VDOM & ADOM':'VDOM & ADOM','FortiSASE':'FortiSASE',
  'FortiAnalyzer':'FortiAnalyzer','FortiManager':'FortiManager',
  'FortiSwitch':'FortiSwitch','Wireless Products':'Wireless Products',
  'FortiSandbox & ATP Products':'FortiSandbox & ATP Products',
  'FortiMail':'FortiMail','FortiWeb':'FortiWeb','FortiClient':'FortiClient',
  'FortiEndpoint':'FortiEndpoint','Data Protection':'Data Protection',
  'FortiEDR':'FortiEDR','FortiMonitor':'FortiMonitor',
  'FortiSIEM, SOAR & UEBA':'FortiSIEM, SOAR & UEBA','FortiCloud':'FortiCloud',
  'IAM, PAM, SRA':'IAM, PAM, SRA','FortiExtender':'FortiExtender',
  'ADN & DDoS Products':'ADN & DDoS Products','Voice & Video':'Voice & Video',
  'FortiNAC':'FortiNAC','Proxy Products':'Proxy Products',
  'Transceivers-DAC':'Transceivers-DAC','Other Products':'Other Products',
  'Accessories':'Accessories','Training':'Training','Adv-Services':'Adv-Services',
  'LENC':'LENC','DataSet':'DataSet','Changes':'Changes',
};

// Hojas que sabemos que NO son categorías de producto — no se reportan como "omitidas".
const SHEET_IGNORE_LIST = ['Cover Sheet','Index','General Info','Ordering Guides','DataSet','Changes'];

// Normaliza un nombre de hoja (espacios extra, NBSP, mayúsculas) para que un
// cambio mínimo en el nombre de la pestaña (ej. doble espacio, mayúscula,
// coma faltante) no tire la categoría entera de forma silenciosa.
function normalizeSheetName(name) {
  return String(name || '')
    .replace(/\u00a0/g, ' ')
    .trim()
    .replace(/\s+/g, ' ')
    .toLowerCase();
}

const NORMALIZED_SHEET_MAP = {};
Object.keys(SHEET_CATEGORY_MAP).forEach(k => {
  NORMALIZED_SHEET_MAP[normalizeSheetName(k)] = SHEET_CATEGORY_MAP[k];
});
const NORMALIZED_IGNORE_SET = new Set(SHEET_IGNORE_LIST.map(normalizeSheetName));

function readExcel(file) {
  const nombreArchivo = (file.name || '').toLowerCase();
  if (!nombreArchivo.endsWith('.xlsx') && !nombreArchivo.endsWith('.xls')) {
    showAlert('error', 'Formato no soportado. Sube un archivo .xlsx o .xls.');
    return;
  }

  const baseName = file.name.replace(/\.[^.]+$/, '').replace(/_/g, ' ');
  document.getElementById('vname-input').value = baseName;
  document.getElementById('file-chip').querySelector('.fc-name').textContent = file.name;
  document.getElementById('file-chip').classList.add('show');
  document.getElementById('preview-wrap').classList.remove('show');
  document.getElementById('btn-guardar').disabled = true;
  document.getElementById('step-2').classList.remove('done');
  document.getElementById('step-3').classList.remove('done');
  parsedProducts = [];

  setProgress(5, 'Leyendo archivo…');

  const reader = new FileReader();

  reader.onerror = function() {
    setProgress(0, '');
    document.getElementById('progress-wrap').classList.remove('show');
    showAlert('error', 'No se pudo leer el archivo. Inténtalo de nuevo.');
  };

  reader.onload = function(e) {
    try {
      setProgress(20, 'Parseando Excel…');
      const wb = XLSX.read(e.target.result, { type: 'array' });
      setProgress(40, 'Buscando columnas SKU / DESCRIPTION / PRICE…');

      const sheetSummary   = [];
      const omittedSheets  = []; // { nombre, razon } — hojas que no se importaron, para avisar al usuario
      let totalProds = 0;

      wb.SheetNames.forEach((sheetName, si) => {
        const norm = normalizeSheetName(sheetName);

        if (NORMALIZED_IGNORE_SET.has(norm)) return; // hoja conocida como no-producto, no se reporta

        const categoriaName = NORMALIZED_SHEET_MAP[norm];
        if (!categoriaName) {
          omittedSheets.push({ nombre: sheetName, razon: 'Nombre de hoja no reconocido (revisa si cambió respecto al mapa de categorías)' });
          return;
        }

        const ws   = wb.Sheets[sheetName];
        const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });

        // Detecta una fila de encabezado y devuelve el mapeo de columnas.
        // Soporta el layout estándar (SKU/DESCRIPTION/PRICE) y el layout de
        // la tabla de "cambios de precio" (SKU / New Description / New Price,
        // con fallback a Old Description / Old Price si no hay "new").
        function detectHeader(row) {
          let s = -1, dNew = -1, dOld = -1, pNew = -1, pOld = -1, pPlain = -1, dPlain = -1;
          row.forEach((cell, ci) => {
            const v = String(cell).trim().toUpperCase();
            if (v === 'SKU')              s = ci;
            if (v === 'DESCRIPTION')      dPlain = ci;
            if (v === 'NEW DESCRIPTION')  dNew = ci;
            if (v === 'OLD DESCRIPTION')  dOld = ci;
            if (v === 'PRICE')            pPlain = ci;
            if (v === 'NEW PRICE')        pNew = ci;
            if (v === 'OLD PRICE')        pOld = ci;
          });
          const d = dPlain >= 0 ? dPlain : (dNew >= 0 ? dNew : dOld);
          const p = pPlain >= 0 ? pPlain : (pNew >= 0 ? pNew : pOld);
          if (s < 0 || d < 0 || p < 0) return null;
          const priceCols = [];
          for (let ci = p; ci < Math.min(p + 6, row.length); ci++) priceCols.push(ci);
          return { colSKU: s, colDesc: d, priceCols };
        }

        let mapping = null;
        let headerSeen = false;
        let count = 0;

        for (let r = 0; r < rows.length; r++) {
          const row = rows[r];
          const maybeHeader = detectHeader(row);
          if (maybeHeader) {
            mapping = maybeHeader;
            headerSeen = true;
            continue; // la fila de encabezado no es un producto
          }
          if (!mapping) continue; // aún no hemos visto ningún encabezado en esta hoja

          const sku  = String(row[mapping.colSKU]  ?? '').trim();
          const desc = String(row[mapping.colDesc] ?? '').trim();
          if (!sku) continue;
          let price = 0;
          for (const ci of mapping.priceCols) {
            const val = parseFloat(row[ci] ?? 0);
            if (!isNaN(val) && val > 0) { price = val; break; }
          }
          if (price <= 0) continue;
          parsedProducts.push({ categoria: categoriaName, sku, producto: sku, descripcion: desc, precio: Math.round(price * 100) / 100 });
          count++; totalProds++;
        }

        if (!headerSeen) {
          omittedSheets.push({ nombre: sheetName, razon: 'No se encontró ninguna fila de encabezado SKU/DESCRIPTION/PRICE' });
          return;
        }

        if (count > 0) {
          sheetSummary.push({ nombre: categoriaName, count });
        } else {
          omittedSheets.push({ nombre: sheetName, razon: 'Header encontrado pero 0 productos con SKU y precio válido' });
        }
        setProgress(40 + Math.round(((si + 1) / wb.SheetNames.length) * 50), `Procesando: ${categoriaName}`);
      });

      setProgress(100, '¡Listo!');

      if (parsedProducts.length === 0) {
        document.getElementById('progress-wrap').classList.remove('show');
        showAlert('error', 'No se encontraron productos. Asegúrate de cargar la lista de precios AMER de Fortinet.');
        return;
      }

      // ── Detección de SKUs duplicados entre categorías ──────────────────
      // La base de datos solo permite un SKU por versión (sin importar
      // categoría). Si el mismo SKU aparece en 2 hojas distintas, la última
      // en procesarse "gana" la categoría al guardar. Lo detectamos aquí
      // para que el preview coincida exactamente con lo que se guardará, y
      // para avisar al usuario en vez de que pase en silencio.
      const skuToCategoria = new Map();
      parsedProducts.forEach(p => skuToCategoria.set(p.sku, p.categoria));

      const dedupedProducts = [];
      const seenSku = new Set();
      for (let i = parsedProducts.length - 1; i >= 0; i--) {
        const p = parsedProducts[i];
        if (seenSku.has(p.sku)) continue;
        seenSku.add(p.sku);
        dedupedProducts.push(p);
      }
      dedupedProducts.reverse();

      const crossCategorySkus = [];
      const skuFirstCategoria = new Map();
      parsedProducts.forEach(p => {
        if (!skuFirstCategoria.has(p.sku)) {
          skuFirstCategoria.set(p.sku, p.categoria);
        } else if (skuFirstCategoria.get(p.sku) !== p.categoria && !crossCategorySkus.includes(p.sku)) {
          crossCategorySkus.push(p.sku);
        }
      });

      if (dedupedProducts.length !== parsedProducts.length) {
        parsedProducts = dedupedProducts;
        // Recalcular resumen por categoría con los datos ya deduplicados
        const recount = {};
        parsedProducts.forEach(p => { recount[p.categoria] = (recount[p.categoria] || 0) + 1; });
        sheetSummary.forEach(s => { s.count = recount[s.nombre] || 0; });
        totalProds = parsedProducts.length;
      }

      document.getElementById('preview-chips').innerHTML = sheetSummary.map(s =>
        `<span class="preview-chip"><span>${escH(s.nombre)}</span><strong>${s.count}</strong></span>`
      ).join('');
      document.getElementById('preview-total').querySelector('span').textContent = `${totalProds.toLocaleString()} productos listos para guardar`;

      // Aviso visible de hojas omitidas — antes esto pasaba en silencio.
      const omitBox = document.getElementById('preview-omitidas');
      if (omitBox) {
        const msgs = [];
        if (omittedSheets.length > 0) {
          msgs.push('⚠ ' + omittedSheets.length + ' hoja(s) NO importada(s): '
            + omittedSheets.map(o => `<strong>${escH(o.nombre)}</strong> (${escH(o.razon)})`).join(', '));
        }
        if (crossCategorySkus.length > 0) {
          msgs.push('⚠ ' + crossCategorySkus.length + ' SKU(s) aparecen en más de una categoría '
            + '(se guardará solo la última categoría vista para cada uno): '
            + crossCategorySkus.slice(0, 15).map(s => `<strong>${escH(s)}</strong>`).join(', ')
            + (crossCategorySkus.length > 15 ? ` y ${crossCategorySkus.length - 15} más…` : ''));
        }
        if (msgs.length > 0) {
          omitBox.style.display = 'block';
          omitBox.innerHTML = msgs.join('<br><br>');
        } else {
          omitBox.style.display = 'none';
          omitBox.innerHTML = '';
        }
      }

      document.getElementById('preview-wrap').classList.add('show');
      document.getElementById('vname-wrap').style.display = 'block';
      document.getElementById('btn-guardar').disabled = false;
      document.getElementById('step-2').classList.add('done');
      document.getElementById('step-3').classList.add('done');

    } catch(err) {
      setProgress(0, '');
      document.getElementById('progress-wrap').classList.remove('show');
      showAlert('error', 'Error al leer el archivo: ' + err.message);
    }
  };
  reader.readAsArrayBuffer(file);
}

// ================================================================
//  GUARDAR LISTA
// ================================================================
const IMPORT_BATCH_SIZE = 300;

async function postImportar(payload) {
  const res = await fetch('API/api_importar.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    let msg = `Error del servidor (${res.status})`;
    try { const errData = await res.json(); if (errData?.error) msg = errData.error; } catch(_) {}
    throw new Error(msg);
  }
  const data = await res.json();
  if (!data.ok) throw new Error(data.error ?? 'Respuesta inesperada del servidor');
  return data;
}

async function guardarLista() {
  if (!parsedProducts.length) return;
  const nombre = document.getElementById('vname-input').value.trim()
               || 'Lista ' + new Date().toLocaleDateString('es-MX');
  const btn = document.getElementById('btn-guardar');
  const originalBtnHtml = btn.innerHTML;
  btn.disabled = true;

  const total   = parsedProducts.length;
  const batches = [];
  for (let i = 0; i < total; i += IMPORT_BATCH_SIZE) {
    batches.push(parsedProducts.slice(i, i + IMPORT_BATCH_SIZE));
  }

  try {
    // 1) Crear la versión (inactiva hasta que termine de importar todo)
    btn.innerHTML = '<i class="ti ti-loader" style="animation:spin .7s linear infinite;"></i> Creando versión…';
    const init = await postImportar({ action: 'iniciar', nombre });
    const versionId = init.version_id;

    // 2) Insertar en lotes, mostrando progreso real
    let insertados = 0;
    for (let b = 0; b < batches.length; b++) {
      btn.innerHTML = `<i class="ti ti-loader" style="animation:spin .7s linear infinite;"></i> `
        + `Guardando ${Math.min((b + 1) * IMPORT_BATCH_SIZE, total).toLocaleString()} / ${total.toLocaleString()}…`;
      const loteRes = await postImportar({ action: 'lote', version_id: versionId, productos: batches[b] });
      insertados += loteRes.insertados;
    }

    // 3) Finalizar: activa la versión y desactiva las demás
    btn.innerHTML = '<i class="ti ti-loader" style="animation:spin .7s linear infinite;"></i> Activando versión…';
    const fin = await postImportar({ action: 'finalizar', version_id: versionId });

    showAlert('success',
      `✓ Versión <strong>"${escH(nombre)}"</strong> guardada — `
      + `${fin.total_skus.toLocaleString()} productos importados. `
      + `<a href="lista_precios.php" style="color:var(--orange-glow);font-weight:600">Recargar →</a>`
    );
    parsedProducts = [];
    ['preview-wrap','progress-wrap'].forEach(id => document.getElementById(id).classList.remove('show'));
    document.getElementById('vname-wrap').style.display = 'none';
    document.getElementById('file-chip').classList.remove('show');
    document.getElementById('step-2').classList.remove('done');
    document.getElementById('step-3').classList.remove('done');
    btn.innerHTML = '<i class="ti ti-circle-check"></i> Guardado';
    setTimeout(() => location.reload(), 1200);

  } catch (err) {
    showAlert('error', 'Error al guardar: ' + err.message + ' — la versión anterior sigue activa, ningún dato se perdió.');
    btn.disabled = false;
    btn.innerHTML = originalBtnHtml;
  }
}

// ================================================================
//  DRAWER DE PRODUCTOS — con event listener delegado para .cat-item
// ================================================================
let drawerProds = [], activeDrawerItem = null, drawerView = 'table', drawerMode = 'categoria';

document.addEventListener('click', function(e) {
  const item = e.target.closest('.cat-item');
  if (item && !e.target.closest('a, button')) {
    const cat = item.dataset.categoria;
    const total = parseInt(item.dataset.total, 10);
    const vid = parseInt(item.dataset.vid, 10);
    if (cat && total && vid) {
      openDrawer(item, cat, total, vid);
    }
  }
});

async function openDrawer(el, cat, total, vid) {
  drawerMode = 'categoria';
  const gs2 = document.getElementById('g-search2');
  if (gs2) gs2.value = '';
  if (activeDrawerItem) activeDrawerItem.classList.remove('active-cat');
  el.classList.add('active-cat');
  activeDrawerItem = el;

  document.getElementById('drawer').classList.add('open');
  document.getElementById('drawer-backdrop').classList.add('open');
  document.getElementById('d-cat-name').textContent = cat;
  document.getElementById('d-cat-meta').textContent = total + ' productos';
  document.getElementById('d-search').value = '';
  document.getElementById('d-body').innerHTML =
    '<div class="drawer-loading" style="padding:16px;text-align:center;color:var(--text-dim);"><div class="spinner" style="display:inline-block;width:16px;height:16px;border:2px solid var(--border-soft);border-top-color:var(--orange-glow);border-radius:50%;animation:spin .7s linear infinite;"></div> Cargando productos…</div>';
  document.getElementById('d-footer').textContent = '';
  document.body.style.overflow = 'hidden';

  try {
    const res  = await fetch(`lista_precios.php?api=productos&cat=${encodeURIComponent(cat)}&vid=${vid}`);
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      console.error('Respuesta no-JSON:', text);
      document.getElementById('d-body').innerHTML =
        `<div class="drawer-empty" style="padding:16px;color:var(--text-dim);">
           <strong style="color:#e54818;">Error del servidor</strong>
           <pre style="white-space:pre-wrap;font-size:11px;margin-top:6px;">${escH(text.slice(0, 600))}</pre>
         </div>`;
      return;
    }
    if (data && data.error) {
      document.getElementById('d-body').innerHTML =
        `<div class="drawer-empty" style="padding:16px;color:var(--text-dim);">
           <strong style="color:#e54818;">${escH(data.error)}</strong>
           <span style="font-size:11px;opacity:.7;">${escH(JSON.stringify(data.debug ?? ''))}</span>
         </div>`;
      return;
    }
    drawerProds = data;
    renderDrawer(drawerProds);
  } catch(e) {
    console.error('Error de red:', e);
    document.getElementById('d-body').innerHTML =
      `<div class="drawer-empty" style="padding:16px;text-align:center;color:var(--text-dim);">Error de conexión: ${escH(e.message)}</div>`;
  }
}

function closeDrawer() {
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('drawer-backdrop').classList.remove('open');
  document.body.style.overflow = '';
  if (activeDrawerItem) { activeDrawerItem.classList.remove('active-cat'); activeDrawerItem = null; }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDrawer(); closeModal(); } });

function setDrawerView(view) {
  drawerView = view;
  document.getElementById('dview-table').classList.toggle('on', view === 'table');
  document.getElementById('dview-cards').classList.toggle('on', view === 'cards');
  filterDrawer();
}

function renderDrawer(prods) {
  const body   = document.getElementById('d-body');
  const footer = document.getElementById('d-footer');
  footer.textContent = prods.length + ' producto' + (prods.length !== 1 ? 's' : '');
  if (!prods.length) { body.innerHTML = '<div class="drawer-empty" style="padding:16px;text-align:center;color:var(--text-dim);">Sin productos.</div>'; return; }

  const showCat = drawerMode === 'global';

  if (drawerView === 'cards') {
    body.innerHTML = `<div class="d-cards">${prods.map(p => `
      <div class="d-card">
        <div class="d-sku">${escH(p.codigo)}${showCat && p.categoria ? ` · <span style="color:var(--text-dim);font-weight:400;">${escH(p.categoria)}</span>` : ''}</div>
        <div class="d-desc">${escH(p.descripcion || p.producto || '—')}</div>
        <div class="d-price">$${parseFloat(p.precio).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
      </div>`).join('')}
    </div>`;
    return;
  }

  body.innerHTML = `<table class="d-table">
    <thead><tr>
      <th style="width:90px">SKU</th>
      <th>Descripción</th>
      ${showCat ? '<th style="width:100px">Categoría</th>' : ''}
      <th class="r" style="width:90px">Precio USD</th>
    </tr></thead>
    <tbody>${prods.map(p => `
      <tr>
        <td><div class="d-sku">${escH(p.codigo)}</div></td>
        <td><div class="d-desc">${escH(p.descripcion || p.producto || '—')}</div></td>
        ${showCat ? `<td><div class="d-desc" style="color:var(--text-dim);font-size:11px;">${escH(p.categoria || '—')}</div></td>` : ''}
        <td><div class="d-price">$${parseFloat(p.precio).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2})}</div></td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

function filterDrawer() {
  const q = document.getElementById('d-search').value.toLowerCase();
  const filtered = q
    ? drawerProds.filter(p =>
        (p.codigo||'').toLowerCase().includes(q) ||
        (p.descripcion||'').toLowerCase().includes(q) ||
        (p.producto||'').toLowerCase().includes(q))
    : drawerProds;
  renderDrawer(filtered);
  if (q) document.getElementById('d-footer').textContent =
    filtered.length + ' de ' + drawerProds.length + ' productos';
}

// ================================================================
//  MODAL
// ================================================================
let _modalAction = null;
function openModal({ icon, title, body, btnLabel, btnColor, onConfirm }) {
  document.getElementById('modal-icon').textContent  = icon;
  document.getElementById('modal-title').textContent = title;
  document.getElementById('modal-body').innerHTML    = body;
  const btn = document.getElementById('modal-confirm-btn');
  btn.textContent = btnLabel;
  btn.className   = 'btn-confirm ' + btnColor;
  btn.disabled    = false;
  _modalAction    = onConfirm;
  document.getElementById('modal-backdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('modal-backdrop').classList.remove('open');
  document.body.style.overflow = '';
  _modalAction = null;
}
function modalConfirmAction() { if (_modalAction) _modalAction(); }
document.getElementById('modal-backdrop').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// ================================================================
//  GESTIÓN DE VERSIONES
// ================================================================
function activarVersion(vid, nombre) {
  openModal({
    icon: '▶', title: 'Activar versión',
    body: `¿Deseas activar la versión <strong>"${escH(nombre)}"</strong>?<br><br>
           La versión actualmente activa quedará archivada y el cotizador
           empezará a usar esta lista de inmediato.`,
    btnLabel: '▶ Sí, activar', btnColor: 'green',
    onConfirm: () => doActivar(vid, nombre),
  });
}

async function doActivar(vid, nombre) {
  const btn = document.getElementById('modal-confirm-btn');
  btn.disabled = true; btn.textContent = 'Activando…';
  try {
    const res  = await fetch('API/api_importar.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'activar', version_id: vid }),
    });
    const data = await res.json();
    if (data.ok) {
      closeModal();
      showAlert('success',
        `✓ Versión <strong>"${escH(data.nombre)}"</strong> activada — `
        + `${(data.total_skus || 0).toLocaleString()} productos en uso. `
        + `<a href="lista_precios.php" style="color:var(--orange-glow);font-weight:600">Recargar →</a>`
      );
      actualizarUIVersiones(vid);
    } else {
      closeModal(); showAlert('error', 'Error: ' + (data.error ?? 'Respuesta inesperada'));
    }
  } catch(err) { closeModal(); showAlert('error', 'Error de conexión: ' + err.message); }
}

function eliminarVersion(vid, nombre) {
  openModal({
    icon: '🗑', title: 'Eliminar versión archivada',
    body: `¿Seguro que deseas eliminar la versión <strong>"${escH(nombre)}"</strong>?<br><br>
           <span style="color:#e54818;">⚠ Se borrarán todos sus productos de la base de datos.<br>
           Esta acción no se puede deshacer.</span>`,
    btnLabel: 'Sí, eliminar', btnColor: 'red',
    onConfirm: () => doEliminar(vid, nombre),
  });
}

async function doEliminar(vid) {
  const btn = document.getElementById('modal-confirm-btn');
  btn.disabled = true; btn.textContent = 'Eliminando…';
  try {
    const res  = await fetch('API/api_importar.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'eliminar', version_id: vid }),
    });
    const data = await res.json();
    if (data.ok) {
      closeModal();
      showAlert('success', `🗑 Versión eliminada correctamente.`);
      const row = document.getElementById('ver-row-' + vid);
      if (row) {
        row.style.transition = 'opacity .3s, max-height .3s';
        row.style.opacity    = '0';
        row.style.maxHeight  = '0';
        row.style.overflow   = 'hidden';
        setTimeout(() => row.remove(), 350);
      }
    } else {
      closeModal(); showAlert('error', 'Error: ' + (data.error ?? 'Respuesta inesperada'));
    }
  } catch(err) { closeModal(); showAlert('error', 'Error de conexión: ' + err.message); }
}

function actualizarUIVersiones(nuevoVid) {
  document.querySelectorAll('[id^="ver-row-"]').forEach(row => {
    const rowVid = parseInt(row.id.replace('ver-row-', ''));
    const dot    = document.getElementById('ver-dot-'   + rowVid);
    const badge  = document.getElementById('ver-badge-' + rowVid);
    if (rowVid === nuevoVid) {
      if (dot)   { dot.className   = 'ver-dot active'; }
      if (badge) { badge.className = 'ver-badge active'; badge.textContent = 'Activa'; }
      row.querySelectorAll('.btn-ver-activar, .btn-ver-eliminar').forEach(b => b.remove());
      const sp = document.createElement('span');
      sp.style.cssText = 'width:60px;display:inline-block;';
      row.querySelector('.ver-actions').appendChild(sp);
    } else {
      if (dot)   { dot.className   = 'ver-dot old'; }
      if (badge) { badge.className = 'ver-badge old'; badge.textContent = 'Archivada'; }
    }
  });
}

// ================================================================
//  UTILIDADES
// ================================================================
function escH(s) { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; }

function showAlert(type, html) {
  const el = document.getElementById('js-alert');
  el.className = 'alert ' + type;
  el.innerHTML = (type === 'success' ? '✓ ' : '⚠ ') + html;
  el.style.display = 'flex';
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
</body>
</html>