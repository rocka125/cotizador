<?php
/**
 * views/comparar_listas.view.php — Comparador "Obsidiana" (naranja + negro minimalista).
 *
 * NO contiene queries ni lógica de negocio.
 * Solo echo, htmlspecialchars, foreach e if/else de presentación.
 *
 * Variables disponibles (inyectadas por CompararListasController):
 *   $ctrl->versiones   array   — todas las versiones para los selectores
 *   $auth->esAdmin()   bool
 *   $auth->iniciales() string
 *   $auth->usuarioNombre() string
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Fortress8 · Comparar · Obsidiana</title>
<link rel="icon" href="assets/img/favicon.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<!-- PWA -->
<link rel="manifest" href="/cotizador/manifest.json">
<meta name="theme-color" content="#1a1a1a">
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
   DISEÑO "OBSIDIANA" — Negro mate + Naranja como acento
   ============================================================== */
*,
*::before,
*::after {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --bg-deep: #0d0d0d;
  --bg-card: #141414;
  --bg-elevated: #1c1c1c;
  --border-subtle: rgba(255, 255, 255, 0.04);
  --border-orange: rgba(245, 123, 2, 0.25);
  --orange: #f57b02;
  --orange-hover: #e06e02;
  --orange-glow: rgba(245, 123, 2, 0.08);
  --text-primary: #f0ede8;
  --text-secondary: #b0a898;
  --text-muted: #6a625a;
  --green: #4ade80;
  --red: #f87171;
  --yellow: #fbbf24;
  --radius: 12px;
  --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.6);
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg-deep);
  color: var(--text-primary);
  height: 100vh;
  height: 100dvh;
  overflow: hidden;
  position: relative;
}

/* === Difuminados orgánicos (mismo estilo "Ember Glass" del dashboard) === */
.blob {
  position: fixed;
  border-radius: 50%;
  filter: blur(90px);
  pointer-events: none;
  z-index: 0;
}
.blob1 { width: 560px; height: 560px; background: radial-gradient(circle, var(--orange) 0%, transparent 70%); opacity: .30; top: -200px; left: -160px; }
.blob2 { width: 480px; height: 480px; background: radial-gradient(circle, #000 0%, transparent 70%); opacity: .55; bottom: -160px; right: -100px; }
.blob3 { width: 340px; height: 340px; background: radial-gradient(circle, var(--orange-hover) 0%, transparent 70%); opacity: .20; top: 45%; left: 55%; }

/* === Shell (mismo grid que dashboard.view.php: 56px / 54px) === */
.app-shell {
  display: grid;
  grid-template-columns: 56px 1fr;
  grid-template-rows: 54px 1fr;
  height: 100vh;
  height: 100dvh;
  position: relative;
  z-index: 1;
}

/* === TOPBAR de vidrio esmerilado (glassmorphism), igual que el dashboard === */
.topbar {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 0 16px;
  background: rgba(13, 13, 13, 0.75);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border-subtle);
  z-index: 20;
}

.topbar .brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: 'Fraunces', serif;
  font-weight: 600;
  font-size: 15px;
  letter-spacing: -0.2px;
  flex-shrink: 0;
}
.topbar .brand img {
  height: 26px;
  width: auto;
}
.topbar .brand .accent {
  font-style: italic;
  color: var(--orange);
  font-weight: 500;
}

.topbar .search-wrap {
  flex: 1;
  max-width: 300px;
  position: relative;
  margin: 0 auto;
}
.topbar .search-wrap i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 14px;
}
.topbar .search-wrap input {
  width: 100%;
  padding: 6px 14px 6px 36px;
  background: rgba(255, 255, 255, 0.035);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  color: var(--text-primary);
  font-size: 12.5px;
  outline: none;
  transition: border-color 0.2s;
  font-family: inherit;
  height: 28px;
}
.topbar .search-wrap input:focus {
  border-color: var(--orange);
}

.topbar .tc-pill {
  font-size: 11px;
  color: var(--text-secondary);
  background: rgba(255, 255, 255, 0.035);
  border: 1px solid var(--border-subtle);
  border-radius: 20px;
  padding: 5px 12px;
  display: flex;
  align-items: center;
  gap: 5px;
  backdrop-filter: blur(10px);
  white-space: nowrap;
}
.topbar .tc-pill strong {
  color: var(--green);
  font-weight: 500;
}

.topbar .user-area {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
}
.topbar .user-area .avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ffb46b, var(--orange));
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 11px;
  color: #1a0d04;
  cursor: pointer;
  position: relative;
  flex-shrink: 0;
  transition: box-shadow 0.2s;
}
.topbar .user-area .avatar:hover {
  box-shadow: 0 0 0 3px rgba(245, 123, 2, 0.25);
}
.topbar .user-area .avatar .user-menu {
  display: none;
  position: absolute;
  top: 42px;
  right: 0;
  background: #15100e;
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  min-width: 170px;
  padding: 0;
  z-index: 200;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
}
.topbar .user-area .avatar .user-menu.open {
  display: block;
}
.topbar .user-area .avatar .user-menu .user-name {
  padding: 10px 14px 7px;
  font-weight: 500;
  font-size: 12px;
  color: var(--text-primary);
}
.topbar .user-area .avatar .user-menu a {
  display: block;
  padding: 9px 14px;
  font-size: 12px;
  color: var(--text-secondary);
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
}
.topbar .user-area .avatar .user-menu a:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
}
.topbar .user-area .avatar .user-menu .sep {
  height: 1px;
  background: var(--border-subtle);
}

.topbar .btn-new {
  background: linear-gradient(135deg, var(--orange), var(--orange-hover));
  border: none;
  padding: 8px 16px;
  border-radius: 100px;
  color: #1a0d04;
  font-weight: 600;
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition: transform 0.18s, box-shadow 0.18s;
  text-decoration: none;
  box-shadow: 0 8px 22px rgba(245, 123, 2, 0.28);
}
.topbar .btn-new:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(245, 123, 2, 0.4);
}

.hamburger {
  display: none;
  background: none;
  border: none;
  color: var(--text-primary);
  font-size: 20px;
  cursor: pointer;
  padding: 4px;
}

/* ============================================================
   SIDEBAR — modificado con fondo negro e íconos más grandes
   ============================================================ */
.sidebar {
  background: #0B0708;               /* fondo negro sólido */
  border-right: 1px solid var(--border-subtle);
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
  text-decoration: none;
  transition: background 0.18s, transform 0.15s;
  color: var(--text-muted);
}
.sidebar .si i {
  font-size: 22px;                   /* más grande */
  transition: color 0.18s;
  line-height: 1;                    /* evita desbordes */
}
.sidebar .si:hover:not(.on) {
  background: rgba(255, 255, 255, 0.06);
  transform: scale(1.08);
}
.sidebar .si:hover:not(.on) i {
  color: var(--text-secondary);
}
.sidebar .si.on {
  background: linear-gradient(135deg, rgba(245, 123, 2, 0.22), rgba(224, 110, 2, 0.14));
  box-shadow: 0 0 16px rgba(245, 123, 2, 0.18);
}
.sidebar .si.on i {
  color: var(--orange);
}
.sidebar .si-sep {
  height: 1px;
  width: 32px;                       /* más ancho */
  background: var(--border-subtle);
  margin: 4px 0;
}

/* === MAIN CONTENT === */
.main-content {
  overflow-y: auto;
  padding: 16px 18px 20px;
  position: relative;
  z-index: 1;
}
.main-content::-webkit-scrollbar {
  width: 5px;
}
.main-content::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
}


/* === KPI WIDGETS === */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}

.kpi-widget {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: var(--shadow-card);
  transition: border-color 0.2s;
}
.kpi-widget:hover {
  border-color: var(--border-orange);
}

.kpi-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--orange-glow);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--orange);
  font-size: 20px;
  flex-shrink: 0;
}
.kpi-icon.green { background: rgba(74, 222, 128, 0.08); color: var(--green); }
.kpi-icon.red   { background: rgba(248, 113, 113, 0.08); color: var(--red); }
.kpi-icon.yellow { background: rgba(251, 191, 36, 0.08); color: var(--yellow); }

.kpi-content .kpi-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-muted);
  font-weight: 500;
}
.kpi-content .kpi-value {
  font-size: 26px;
  font-weight: 700;
  line-height: 1.2;
  color: var(--text-primary);
}
.kpi-content .kpi-value.green { color: var(--green); }
.kpi-content .kpi-value.red   { color: var(--red); }
.kpi-content .kpi-value.yellow { color: var(--yellow); }
.kpi-content .kpi-value.orange { color: var(--orange); }
.kpi-content .kpi-sub {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 2px;
}

/* === TARJETAS === */
.card {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius);
  padding: 18px 22px 22px;
  box-shadow: var(--shadow-card);
  transition: border-color 0.2s;
}
.card:hover {
  border-color: var(--border-orange);
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.card-header h3 {
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}
.card-header h3 i {
  color: var(--orange);
}
.card-header .badge {
  font-size: 12px;
  background: var(--orange-glow);
  color: var(--orange);
  padding: 2px 16px;
  border-radius: 40px;
  font-weight: 500;
}

/* === SELECTORES === */
.selector-grid {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 16px;
  align-items: end;
}
.sel-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-muted);
  margin-bottom: 6px;
}
.sel-vs {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-muted);
  padding-bottom: 8px;
  text-align: center;
  font-family: monospace;
}
select {
  width: 100%;
  padding: 8px 32px 8px 14px;
  background: var(--bg-deep);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  color: var(--text-primary);
  font-family: 'Inter', sans-serif;
  font-size: 13px;
  outline: none;
  cursor: pointer;
  transition: border-color 0.2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M0 0l6 8 6-8z' fill='%236a625a'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
}
select:focus {
  border-color: var(--orange);
}
.sel-meta {
  font-size: 11px;
  color: var(--text-muted);
  font-family: monospace;
  margin-top: 6px;
  min-height: 18px;
}

/* === FILTROS === */
.filter-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--border-subtle);
  flex-wrap: wrap;
}
.filter-row label {
  font-size: 12px;
  color: var(--text-secondary);
  white-space: nowrap;
}
.filter-row select {
  flex: 1;
  min-width: 160px;
}
.btn-comparar {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 24px;
  border-radius: 40px;
  border: none;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  background: var(--orange);
  color: #fff;
  transition: background 0.2s, transform 0.15s;
  white-space: nowrap;
}
.btn-comparar:hover {
  background: var(--orange-hover);
  transform: translateY(-1px);
}
.btn-comparar:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  transform: none !important;
}

/* === TABS === */
.tabs {
  display: flex;
  gap: 0;
  border-bottom: 1px solid var(--border-subtle);
  padding: 0 4px;
}
.tab {
  padding: 10px 18px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: all 0.2s;
  user-select: none;
  display: flex;
  align-items: center;
  gap: 6px;
}
.tab:hover {
  color: var(--text-secondary);
}
.tab.active {
  color: var(--text-primary);
  border-bottom-color: var(--orange);
}
.tab-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 1px 10px;
  border-radius: 40px;
  font-family: monospace;
}
.tab.nuevos .tab-badge     { background: rgba(74, 222, 128, 0.08); color: var(--green); }
.tab.cambios .tab-badge    { background: rgba(251, 191, 36, 0.08); color: var(--yellow); }
.tab.eliminados .tab-badge { background: rgba(248, 113, 113, 0.08); color: var(--red); }

/* === BUSCADOR === */
.search-wrap {
  padding: 10px 16px;
  border-bottom: 1px solid var(--border-subtle);
  position: relative;
}
.search-wrap input {
  width: 100%;
  padding: 8px 14px 8px 38px;
  background: var(--bg-deep);
  border: 1px solid var(--border-subtle);
  border-radius: 40px;
  color: var(--text-primary);
  font-size: 13px;
  outline: none;
  font-family: inherit;
  transition: border-color 0.2s;
}
.search-wrap input:focus {
  border-color: var(--orange);
}
.search-icon {
  position: absolute;
  left: 30px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 16px;
  pointer-events: none;
}

/* === TABLA === */
.table-wrap {
  max-height: 480px;
  overflow-y: auto;
  overflow-x: auto;
}
.table-wrap::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}
.table-wrap::-webkit-scrollbar-track {
  background: transparent;
}
.table-wrap::-webkit-scrollbar-thumb {
  background: var(--border-subtle);
  border-radius: 4px;
}

.cmp-table {
  width: 100%;
  border-collapse: collapse;
}
.cmp-table th {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-muted);
  padding: 8px 14px;
  text-align: left;
  border-bottom: 1px solid var(--border-subtle);
  background: var(--bg-deep);
  position: sticky;
  top: 0;
  z-index: 1;
}
.cmp-table th.r { text-align: right; }
.cmp-table th.c { text-align: center; }
.cmp-table td {
  padding: 8px 14px;
  border-bottom: 1px solid var(--border-subtle);
  font-size: 13px;
  color: var(--text-secondary);
  vertical-align: middle;
}
.cmp-table tr:hover td {
  background: var(--orange-glow);
}
.t-sku {
  font-family: monospace;
  font-weight: 500;
  font-size: 12px;
}
.t-sku.nuevo { color: var(--green); }
.t-sku.cambio { color: var(--yellow); }
.t-sku.eliminado { color: var(--red); }
.t-cat {
  font-size: 11px;
  padding: 2px 10px;
  border-radius: 40px;
  background: var(--bg-deep);
  border: 1px solid var(--border-subtle);
  color: var(--text-muted);
  white-space: nowrap;
}
.t-desc {
  line-height: 1.5;
  color: var(--text-secondary);
  max-width: 280px;
}
.t-price {
  font-family: monospace;
  font-size: 13px;
  text-align: right;
  white-space: nowrap;
}
.t-price.a { color: var(--text-muted); }
.t-price.b { color: var(--text-primary); }
.t-price.nuevo { color: var(--green); }
.t-price.eliminado { color: var(--red); }
.var-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  font-family: monospace;
  padding: 2px 12px;
  border-radius: 40px;
  white-space: nowrap;
}
.var-badge.up   { background: rgba(248, 113, 113, 0.06); color: var(--red); border: 1px solid rgba(248, 113, 113, 0.10); }
.var-badge.down { background: rgba(74, 222, 128, 0.06); color: var(--green); border: 1px solid rgba(74, 222, 128, 0.10); }
.var-badge.neutral { background: rgba(251, 191, 36, 0.06); color: var(--yellow); border: 1px solid rgba(251, 191, 36, 0.06); }

/* === FOOTER TABLA === */
.table-footer {
  padding: 8px 16px;
  border-top: 1px solid var(--border-subtle);
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11px;
  color: var(--text-muted);
  font-family: monospace;
}

/* === ESTADOS VACÍOS === */
.empty-msg,
.nodata {
  padding: 40px 16px;
  text-align: center;
  color: var(--text-muted);
  font-size: 13px;
}
.empty-msg .em-icon,
.nodata-icon {
  font-size: 32px;
  display: block;
  margin-bottom: 12px;
  opacity: 0.4;
}
.nodata-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 4px;
}
.nodata-sub {
  font-size: 13px;
  color: var(--text-muted);
}

/* === ALERTAS === */
.alert {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 12px 18px;
  border-radius: var(--radius);
  font-size: 13px;
  font-weight: 500;
  border: 1px solid rgba(248, 113, 113, 0.15);
  background: rgba(248, 113, 113, 0.04);
  color: var(--red);
}
.alert a {
  color: var(--red);
  font-weight: 600;
  text-decoration: underline;
}
.alert i {
  font-size: 18px;
  flex-shrink: 0;
}

/* === SPINNER === */
.spinner {
  display: inline-block;
  width: 22px;
  height: 22px;
  border: 2px solid var(--border-subtle);
  border-top-color: var(--orange);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* === RESPONSIVE === */
@media (max-width: 1024px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 768px) {
  .app-shell {
    grid-template-columns: 1fr;
  }
  .sidebar {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 56px;
    z-index: 210;
  }
  .sidebar.open {
    display: flex;
    box-shadow: 8px 0 24px rgba(0,0,0,.45);
  }
  .hamburger {
    display: block;
  }
  .topbar {
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px 16px;
    height: auto;
  }
  .topbar .search-wrap {
    order: 10;
    flex: 1 1 100%;
    max-width: 100%;
    margin: 4px 0 0;
  }
  .topbar .brand {
    font-size: 14px;
  }
  .topbar .btn-new {
    font-size: 12px;
    padding: 5px 14px;
  }
  .selector-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .sel-vs {
    display: none;
  }
  .filter-row {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-row select {
    min-width: 0;
  }
  .btn-comparar {
    justify-content: center;
  }
  .kpi-grid {
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .kpi-widget {
    padding: 12px 16px;
  }
  .kpi-icon {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
  .kpi-content .kpi-value {
    font-size: 22px;
  }
  .card {
    padding: 14px 16px;
  }
  .t-desc {
    max-width: 120px;
  }
  .cmp-table {
    min-width: 500px;
  }
}
@media (max-width: 480px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  .topbar .tc-pill {
    display: none;
  }
}
</style>
</head>
<body>
<div class="blob blob1"></div>
<div class="blob blob2"></div>
<div class="blob blob3"></div>

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
    <a class="si" href="dashboard.php" title="Dashboard">
      <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
    </a>
    <a class="si" href="lista_cotizaciones.php" title="Cotizaciones">
      <i class="ti ti-file-invoice" aria-hidden="true"></i>
    </a>
    <a class="si" href="seguimiento.php" title="Seguimiento de clientes">
      <i class="ti ti-timeline" aria-hidden="true"></i>
    </a>
    <div class="si-sep"></div>
    <a class="si" href="lista_precios.php" title="Lista de precios">
      <i class="ti ti-list-details" aria-hidden="true"></i>
    </a>
    <a class="si on" href="comparar_listas.php" title="Comparar listas">
      <i class="ti ti-copy" aria-hidden="true"></i>
    </a>
    <?php if ($auth->esAdmin()): ?>
    <div class="si-sep"></div>
    <a class="si" href="auditoria.php" title="Auditoría">
      <i class="ti ti-shield" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
    <div class="si-sep"></div>
    <a class="si" href="logout.php" title="Cerrar sesión">
      <i class="ti ti-logout" aria-hidden="true"></i>
    </a>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="main-content">

    <!-- Page header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
      <div>
        <div style="font-size:20px;font-weight:700;letter-spacing:-0.3px;">Comparar Listas</div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:2px;">Detecta SKUs nuevos, eliminados y cambios de precio</div>
      </div>
    </div>

    <!-- Card de selección -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header">
        <h3><i class="ti ti-switch-horizontal"></i> Seleccionar versiones</h3>
        <span class="badge"><i class="ti ti-database"></i> <?= count($ctrl->versiones) ?> disponibles</span>
      </div>

      <?php if (count($ctrl->versiones) < 2): ?>
        <div class="alert">
          <i class="ti ti-alert-triangle"></i>
          Necesitas al menos 2 versiones importadas para comparar.
          Ve a <a href="lista_precios.php">Lista de precios</a> e importa otra versión.
        </div>
      <?php else: ?>

      <div class="selector-grid">
        <div>
          <div class="sel-label">Versión A — Base</div>
          <select id="sel-a" onchange="onSelChange()">
            <option value="">— Selecciona —</option>
            <?php foreach ($ctrl->versiones as $v): ?>
            <option value="<?= $v['id'] ?>"
                    data-skus="<?= $v['total_skus'] ?>"
                    data-fecha="<?= date('d/m/Y', strtotime($v['created_at'])) ?>"
                    data-activa="<?= $v['activa'] ? '1' : '0' ?>">
              <?= htmlspecialchars($v['nombre']) ?><?= $v['activa'] ? ' ★' : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="sel-meta" id="meta-a"></div>
        </div>

        <div class="sel-vs">⚡ VS</div>

        <div>
          <div class="sel-label">Versión B — Nueva</div>
          <select id="sel-b" onchange="onSelChange()">
            <option value="">— Selecciona —</option>
            <?php foreach ($ctrl->versiones as $v): ?>
            <option value="<?= $v['id'] ?>"
                    data-skus="<?= $v['total_skus'] ?>"
                    data-fecha="<?= date('d/m/Y', strtotime($v['created_at'])) ?>"
                    data-activa="<?= $v['activa'] ? '1' : '0' ?>">
              <?= htmlspecialchars($v['nombre']) ?><?= $v['activa'] ? ' ★' : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="sel-meta" id="meta-b"></div>
        </div>
      </div>

      <div class="filter-row">
        <label><i class="ti ti-filter"></i> Categoría:</label>
        <select id="sel-cat">
          <option value="">Todas las categorías</option>
        </select>
        <button class="btn-comparar" id="btn-comparar" onclick="comparar()" disabled>
          <i class="ti ti-arrow-left-right"></i> Comparar
        </button>
      </div>

      <?php endif; ?>
    </div>

    <!-- Resultado (oculto hasta comparar) -->
    <div id="resultado" style="display:none;flex-direction:column;gap:16px;">

      <!-- KPI Stats -->
      <div class="kpi-grid">
        <div class="kpi-widget">
          <div class="kpi-icon green"><i class="ti ti-plus"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">SKUs Nuevos</div>
            <div class="kpi-value green" id="cnt-nuevos">—</div>
            <div class="kpi-sub">Solo en versión B</div>
          </div>
        </div>
        <div class="kpi-widget">
          <div class="kpi-icon yellow"><i class="ti ti-arrow-up"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">Precios modificados</div>
            <div class="kpi-value yellow" id="cnt-cambios">—</div>
            <div class="kpi-sub">En ambas, precio distinto</div>
          </div>
        </div>
        <div class="kpi-widget">
          <div class="kpi-icon red"><i class="ti ti-minus"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">SKUs Eliminados</div>
            <div class="kpi-value red" id="cnt-eliminados">—</div>
            <div class="kpi-sub">Solo en versión A</div>
          </div>
        </div>
        <div class="kpi-widget">
          <div class="kpi-icon"><i class="ti ti-check"></i></div>
          <div class="kpi-content">
            <div class="kpi-label">Sin cambios</div>
            <div class="kpi-value orange" id="cnt-iguales">—</div>
            <div class="kpi-sub">Precio idéntico</div>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="card" style="padding:0;">
        <div class="tabs" id="tabs">
          <div class="tab nuevos active" onclick="switchTab('nuevos')">
            <i class="ti ti-plus"></i> Nuevos <span class="tab-badge" id="tbadge-nuevos">0</span>
          </div>
          <div class="tab cambios" onclick="switchTab('cambios')">
            <i class="ti ti-arrow-right"></i> Cambios <span class="tab-badge" id="tbadge-cambios">0</span>
          </div>
          <div class="tab eliminados" onclick="switchTab('eliminados')">
            <i class="ti ti-minus"></i> Eliminados <span class="tab-badge" id="tbadge-eliminados">0</span>
          </div>
        </div>

        <div class="search-wrap">
          <span class="search-icon"><i class="ti ti-search"></i></span>
          <input type="text" id="search-input" placeholder="Buscar por SKU o descripción…" oninput="renderTabActivo()">
        </div>

        <div class="table-wrap" id="table-wrap">
          <div class="empty-msg"><span class="em-icon">📋</span>Selecciona las versiones y haz clic en Comparar.</div>
        </div>

        <div class="table-footer">
          <span id="tfoot-left"></span>
          <span id="tfoot-right">USD · Fortinet AMER</span>
        </div>
      </div>

    </div><!-- /resultado -->

    <!-- Estado inicial -->
    <div id="nodata-panel" class="card">
      <div class="nodata">
        <div class="nodata-icon">⇄</div>
        <div class="nodata-title">Elige dos versiones para comparar</div>
        <div class="nodata-sub">Verás los SKUs nuevos, eliminados y con cambios de precio entre ambas listas.</div>
      </div>
    </div>

  </main>
</div>

<script>
// ================================================================
//  NAVEGACIÓN Y UTILIDADES
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

// ── Tipo de cambio ─────────────────────────────────────────────
fetch('API/api_tipo_cambio.php').then(r => r.json()).then(d => {
  const v = d.MXN || d.mxn || null;
  if (v) document.getElementById('tc-val').textContent = parseFloat(v).toFixed(2);
}).catch(() => { document.getElementById('tc-val').textContent = 'N/D'; });

// ── Búsqueda global (placeholder) ────────────────────────────
let _globalSearchTimer = null;
function globalSearchInput() {
  // Placeholder para futura funcionalidad
}

// ================================================================
//  LÓGICA DEL COMPARADOR
// ================================================================
let DATA       = { nuevos: [], cambios: [], eliminados: [], iguales: 0 };
let TAB_ACTIVO = 'nuevos';
let NOMBRES    = { a: '', b: '' };

function escH(s) {
  const d = document.createElement('div');
  d.textContent = String(s ?? '');
  return d.innerHTML;
}
function fmtUSD(n) {
  return '$' + parseFloat(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── Selector: actualiza meta y carga categorías ──────────────
function onSelChange() {
  const selA = document.getElementById('sel-a');
  const selB = document.getElementById('sel-b');
  const optA = selA.options[selA.selectedIndex];
  const optB = selB.options[selB.selectedIndex];

  const fmtMeta = (sel, opt) => sel.value
    ? `${opt.dataset.skus ? Number(opt.dataset.skus).toLocaleString() : '?'} SKUs · ${opt.dataset.fecha || ''}${opt.dataset.activa === '1' ? ' · ★ Activa' : ''}`
    : '';

  document.getElementById('meta-a').textContent = fmtMeta(selA, optA);
  document.getElementById('meta-b').textContent = fmtMeta(selB, optB);

  document.getElementById('btn-comparar').disabled =
    !(selA.value && selB.value && selA.value !== selB.value);

  // Cargar categorías de la versión A
  const catSel = document.getElementById('sel-cat');
  catSel.innerHTML = '<option value="">Todas las categorías</option>';
  if (selA.value) {
    fetch(`comparar_listas.php?api=cats&vid=${selA.value}`)
      .then(r => r.json())
      .then(cats => cats.forEach(c => {
        const o = document.createElement('option');
        o.value       = c.categoria;
        o.textContent = `${c.categoria} (${Number(c.n).toLocaleString()})`;
        catSel.appendChild(o);
      }))
      .catch(() => {});
  }
}

// ── Comparar ────────────────────────────────────────────────────
async function comparar() {
  const selA = document.getElementById('sel-a');
  const selB = document.getElementById('sel-b');
  const vidA = selA.value;
  const vidB = selB.value;
  const cat  = document.getElementById('sel-cat').value;

  if (!vidA || !vidB || vidA === vidB) return;

  NOMBRES.a = selA.options[selA.selectedIndex].text.replace(' ★', '').trim();
  NOMBRES.b = selB.options[selB.selectedIndex].text.replace(' ★', '').trim();

  const btn = document.getElementById('btn-comparar');
  btn.disabled    = true;
  btn.innerHTML   = '<i class="ti ti-loader"></i> Comparando…';

  document.getElementById('nodata-panel').style.display = 'none';
  document.getElementById('resultado').style.display    = 'flex';

  const skusA = Number(selA.options[selA.selectedIndex].dataset.skus || 0).toLocaleString();
  const skusB = Number(selB.options[selB.selectedIndex].dataset.skus || 0).toLocaleString();
  document.getElementById('table-wrap').innerHTML =
    `<div class="empty-msg"><div class="spinner"></div><br><br>Analizando ${skusA} vs ${skusB} SKUs…</div>`;

  try {
    const url  = `comparar_listas.php?api=comparar&a=${vidA}&b=${vidB}` + (cat ? `&cat=${encodeURIComponent(cat)}` : '');
    const res  = await fetch(url);
    const data = await res.json();

    if (!data.ok) {
      document.getElementById('table-wrap').innerHTML =
        `<div class="empty-msg" style="color:var(--red)">⚠ ${escH(data.error)}</div>`;
      btn.disabled = false; btn.innerHTML = '<i class="ti ti-arrow-left-right"></i> Comparar';
      return;
    }

    DATA = data;
    TAB_ACTIVO = data.nuevos.length > 0 ? 'nuevos'
               : data.cambios.length > 0 ? 'cambios'
               : 'eliminados';

    document.getElementById('cnt-nuevos').textContent     = data.nuevos.length.toLocaleString();
    document.getElementById('cnt-cambios').textContent    = data.cambios.length.toLocaleString();
    document.getElementById('cnt-eliminados').textContent = data.eliminados.length.toLocaleString();
    document.getElementById('cnt-iguales').textContent    = data.iguales.toLocaleString();

    document.getElementById('tbadge-nuevos').textContent     = data.nuevos.length.toLocaleString();
    document.getElementById('tbadge-cambios').textContent    = data.cambios.length.toLocaleString();
    document.getElementById('tbadge-eliminados').textContent = data.eliminados.length.toLocaleString();

    document.getElementById('tfoot-right').textContent = `${NOMBRES.a}  →  ${NOMBRES.b}`;
    switchTab(TAB_ACTIVO);

  } catch(err) {
    document.getElementById('table-wrap').innerHTML =
      `<div class="empty-msg" style="color:var(--red)">⚠ Error de conexión: ${escH(err.message)}</div>`;
  }

  btn.disabled = false; btn.innerHTML = '<i class="ti ti-arrow-left-right"></i> Comparar';
}

// ── Tabs ────────────────────────────────────────────────────────
function switchTab(tab) {
  TAB_ACTIVO = tab;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelector(`.tab.${tab}`).classList.add('active');
  document.getElementById('search-input').value = '';
  renderTabActivo();
}

function renderTabActivo() {
  const q = document.getElementById('search-input').value.toLowerCase();
  if (TAB_ACTIVO === 'nuevos')     renderNuevos(q);
  if (TAB_ACTIVO === 'cambios')    renderCambios(q);
  if (TAB_ACTIVO === 'eliminados') renderEliminados(q);
}

// ── Render: SKUs nuevos ────────────────────────────────────────
function renderNuevos(q = '') {
  const rows = DATA.nuevos.filter(r =>
    !q || r.sku.toLowerCase().includes(q) || (r.descripcion||'').toLowerCase().includes(q)
  );
  const wrap = document.getElementById('table-wrap');
  if (!rows.length) {
    wrap.innerHTML = q
      ? `<div class="empty-msg">Sin resultados para "<strong>${escH(q)}</strong>"</div>`
      : `<div class="empty-msg"><span class="em-icon">✓</span>No hay SKUs nuevos entre estas versiones.</div>`;
    document.getElementById('tfoot-left').textContent = '0 registros';
    return;
  }
  wrap.innerHTML = `<table class="cmp-table">
    <thead><tr>
      <th style="width:130px">SKU</th><th style="width:160px">Categoría</th>
      <th>Descripción</th><th class="r" style="width:110px">Precio (B)</th>
      <th class="c" style="width:90px">Estado</th>
    </tr></thead>
    <tbody>${rows.map(r => `
      <tr>
        <td><span class="t-sku nuevo">${escH(r.sku)}</span></td>
        <td><span class="t-cat">${escH(r.categoria)}</span></td>
        <td class="t-desc">${escH(r.descripcion || '—')}</td>
        <td class="t-price nuevo">${fmtUSD(r.precio_b)}</td>
        <td style="text-align:center"><span class="var-badge down"><i class="ti ti-plus"></i> Nuevo</span></td>
      </tr>`).join('')}
    </tbody></table>`;
  document.getElementById('tfoot-left').textContent =
    `${rows.length.toLocaleString()} de ${DATA.nuevos.length.toLocaleString()} SKUs nuevos`;
}

// ── Render: cambios de precio ──────────────────────────────────
function renderCambios(q = '') {
  const rows = DATA.cambios.filter(r =>
    !q || r.sku.toLowerCase().includes(q) || (r.descripcion||'').toLowerCase().includes(q)
  );
  const wrap = document.getElementById('table-wrap');
  if (!rows.length) {
    wrap.innerHTML = q
      ? `<div class="empty-msg">Sin resultados para "<strong>${escH(q)}</strong>"</div>`
      : `<div class="empty-msg"><span class="em-icon">✓</span>No hay cambios de precio entre estas versiones.</div>`;
    document.getElementById('tfoot-left').textContent = '0 registros';
    return;
  }
  wrap.innerHTML = `<table class="cmp-table">
    <thead><tr>
      <th style="width:130px">SKU</th><th style="width:150px">Categoría</th>
      <th>Descripción</th><th class="r" style="width:100px">Precio A</th>
      <th class="r" style="width:100px">Precio B</th>
      <th class="r" style="width:90px">Diferencia</th>
      <th class="c" style="width:80px">Variación</th>
    </tr></thead>
    <tbody>${rows.map(r => {
      const subida  = r.diff > 0;
      const pctTxt  = r.pct !== null ? (subida ? '+' : '') + r.pct.toFixed(2) + '%' : '—';
      const diffTxt = (r.diff > 0 ? '+' : '') + fmtUSD(r.diff);
      return `<tr>
        <td><span class="t-sku cambio">${escH(r.sku)}</span></td>
        <td><span class="t-cat">${escH(r.categoria)}</span></td>
        <td class="t-desc">${escH(r.descripcion || '—')}</td>
        <td class="t-price a">${fmtUSD(r.precio_a)}</td>
        <td class="t-price b">${fmtUSD(r.precio_b)}</td>
        <td style="text-align:right;font-family:monospace;font-size:13px;color:${subida ? 'var(--red)' : 'var(--green)'}">${diffTxt}</td>
        <td style="text-align:center"><span class="var-badge ${subida ? 'up' : 'down'}">${pctTxt}</span></td>
      </tr>`;
    }).join('')}
    </tbody></table>`;
  document.getElementById('tfoot-left').textContent =
    `${rows.length.toLocaleString()} de ${DATA.cambios.length.toLocaleString()} cambios`;
}

// ── Render: SKUs eliminados ────────────────────────────────────
function renderEliminados(q = '') {
  const rows = DATA.eliminados.filter(r =>
    !q || r.sku.toLowerCase().includes(q) || (r.descripcion||'').toLowerCase().includes(q)
  );
  const wrap = document.getElementById('table-wrap');
  if (!rows.length) {
    wrap.innerHTML = q
      ? `<div class="empty-msg">Sin resultados para "<strong>${escH(q)}</strong>"</div>`
      : `<div class="empty-msg"><span class="em-icon">✓</span>No hay SKUs eliminados entre estas versiones.</div>`;
    document.getElementById('tfoot-left').textContent = '0 registros';
    return;
  }
  wrap.innerHTML = `<table class="cmp-table">
    <thead><tr>
      <th style="width:130px">SKU</th><th style="width:160px">Categoría</th>
      <th>Descripción</th><th class="r" style="width:110px">Precio (A)</th>
      <th class="c" style="width:90px">Estado</th>
    </tr></thead>
    <tbody>${rows.map(r => `
      <tr>
        <td><span class="t-sku eliminado">${escH(r.sku)}</span></td>
        <td><span class="t-cat">${escH(r.categoria)}</span></td>
        <td class="t-desc">${escH(r.descripcion || '—')}</td>
        <td class="t-price eliminado">${fmtUSD(r.precio_a)}</td>
        <td style="text-align:center"><span class="var-badge up"><i class="ti ti-minus"></i> Eliminado</span></td>
      </tr>`).join('')}
    </tbody></table>`;
  document.getElementById('tfoot-left').textContent =
    `${rows.length.toLocaleString()} de ${DATA.eliminados.length.toLocaleString()} SKUs eliminados`;
}
</script>
</body>
</html>