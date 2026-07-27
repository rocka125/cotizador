<?php
/**
 * views/lista_cotizaciones.view.php — Listado de cotizaciones, tema "Sello y Folio"
 * sobre el fondo negro/morado oscuro original de Fortress8 (Vivid theme).
 * Recibe variables del controlador. No contiene queries ni lógica de negocio.
 *
 * Variables disponibles (inyectadas por ListaCotizacionesController):
 *   $ctrl->cotizaciones      array   — registros de la página actual
 *   $ctrl->totalRegistros    int     — total de cotizaciones
 *   $ctrl->totalPaginas      int     — número de páginas
 *   $ctrl->paginaActual      int     — página activa
 *   $ctrl->busqueda          string  — término de búsqueda actual
 *   $ctrl->showSuccessToast  bool    — mostrar toast de éxito
 *   $auth->esAdmin()         bool
 *   $auth->usuarioNombre()   string
 *   $auth->iniciales()       string
 *   $auth->rol()             string
 */

// ── Helpers de presentación (sin acceso a BD) ──────────────────────────────
function clienteDisplay(array $c): string {
    $empresa  = trim($c['empresa']  ?? '');
    $atencion = trim($c['atencion'] ?? '');
    if ($empresa)  return $empresa;
    if ($atencion) return $atencion;
    return trim($c['cliente_nombre'] ?? $c['nombre'] ?? '—');
}

function estadoBadgeClass(string $estado): string {
    $e = normalizar_estado($estado);
    if ($e === 'aprobada')  return 't-gr';
    if ($e === 'rechazada') return 't-re';
    return 't-or';
}

function estadoStampText(string $estado): string {
    $e = normalizar_estado($estado);
    if ($e === 'aprobada')  return 'APROBADO';
    if ($e === 'rechazada') return 'RECHAZADO';
    return 'EN REVISIÓN';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Fortress8 | Mis Cotizaciones</title>
<link rel="icon" href="assets/img/favicon.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Inter:wght@300;400;500;600&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<!-- ═══════════════════ PWA ═══════════════════ -->
<link rel="manifest" href="/cotizador/manifest.json">
<meta name="theme-color" content="#e63946">
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
<!-- ════════════════════════════════════════════ -->

<style>
/* ===== ESTILOS COMPLETOS DEL DASHBOARD (Vivid theme, fondo original) ===== */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0D0A14;--s1:#13101C;--s2:#1A1626;--s3:#221E30;
  --line:rgba(255,255,255,.07);
  --or:#F57B02;--re:#E54818;--pk:#FF2D6B;
  --ink:#F5F0FF;--ink2:#9B94B8;--ink3:#5C5478;
  --green:#4ade80;
  --or-soft:rgba(245,123,2,.12);--re-soft:rgba(229,72,24,.12);--green-soft:rgba(74,222,128,.10);
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--ink);height:100vh;height:100dvh;overflow:hidden;position:relative;}
body::before{content:'';position:fixed;inset:0;background:
  radial-gradient(ellipse 55% 45% at 5% 5%,rgba(245,123,2,.28) 0%,transparent 65%),
  radial-gradient(ellipse 45% 55% at 95% 95%,rgba(0,0,0,.65) 0%,transparent 65%),
  radial-gradient(ellipse 30% 35% at 90% 10%,rgba(255,140,61,.16) 0%,transparent 55%);
  pointer-events:none;z-index:0;}
.shell{display:grid;grid-template-columns:56px 1fr;grid-template-rows:54px 1fr;height:100vh;height:100dvh;position:relative;z-index:1;}

/* TOPBAR — vidrio esmerilado, igual que dashboard.view.php */
.topbar{grid-column:1/-1;background:rgba(19,16,28,.75);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px;padding:0 16px;z-index:20;}
.tb-logo{width:32px;height:32px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.tb-logo img{width:100%;height:100%;object-fit:contain;}
.tb-name{font-family:'Fraunces',serif;font-size:15px;font-weight:600;color:var(--ink);flex-shrink:0;letter-spacing:.2px;}
.tb-name span{font-style:italic;color:var(--or);font-weight:500;}
.tb-search{flex:1;background:rgba(255,255,255,.035);border:1px solid var(--line);border-radius:10px;height:28px;
  display:flex;align-items:center;gap:7px;padding:0 10px;max-width:300px;margin:0 auto;}
.tb-search i{font-size:13px;color:var(--ink3);}
.tb-search span{font-size:11px;color:var(--ink3);}
.tb-pill{font-size:11px;color:var(--ink2);background:rgba(255,255,255,.035);border:1px solid var(--line);
  border-radius:20px;padding:5px 12px;display:flex;align-items:center;gap:5px;backdrop-filter:blur(10px);}
.tb-pill b{color:var(--green);font-weight:500;}
.tb-actions{display:flex;align-items:center;gap:8px;margin-left:auto;}
.tb-icon{width:32px;height:32px;border-radius:10px;background:rgba(255,255,255,.035);border:1px solid var(--line);
  display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;
  transition:background .2s,border-color .2s,transform .15s;}
.tb-icon:hover{background:rgba(255,255,255,.05);border-color:rgba(245,123,2,.3);transform:translateY(-1px);}
.tb-icon i{font-size:15px;color:var(--ink2);}
.tb-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#ffb46b,var(--or));display:flex;align-items:center;
  justify-content:center;font-size:11px;font-weight:700;color:#1a0d04;cursor:pointer;position:relative;flex-shrink:0;
  transition:box-shadow .2s;}
.tb-avatar:hover{box-shadow:0 0 0 3px rgba(245,123,2,.25);}
.hamburger{display:none;background:none;border:none;color:var(--ink);font-size:20px;cursor:pointer;padding:4px;}
.push-btn{background:rgba(255,255,255,.035);border:1px solid var(--line);color:var(--ink2);
  border-radius:20px;padding:6px 12px;font-size:11px;cursor:pointer;white-space:nowrap;font-family:inherit;
  transition:border-color .2s,color .2s;}
.push-btn:hover{border-color:rgba(245,123,2,.4);color:var(--or);}

/* USER MENU (topbar) */
.user-menu{position:absolute;top:42px;right:0;background:#15100e;border:1px solid var(--line);
  border-radius:14px;min-width:170px;z-index:200;display:none;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.6);}
.user-menu.open{display:block;}
.user-menu a{display:block;padding:9px 14px;font-size:12px;color:var(--ink2);text-decoration:none;transition:background .15s;}
.user-menu a:hover{background:rgba(255,255,255,.05);color:var(--ink);}
.user-menu .sep{height:1px;background:var(--line);}
.user-name{padding:10px 14px 7px;font-size:12px;font-weight:500;color:var(--ink);}

/* ============================================================
   SIDEBAR — modificado con fondo negro e íconos más grandes
   ============================================================ */
.sidebar{
  background: #0B0708;               /* fondo negro sólido */
  border-right: 1px solid var(--line);
  padding: 16px 0;                   /* más aire arriba/abajo */
  display:flex;
  flex-direction:column;
  align-items:center;
  gap: 8px;                          /* separación entre íconos */
  overflow:hidden;
  box-shadow: 4px 0 20px rgba(0,0,0,.4); /* profundidad */
}
.si{
  width: 44px;                       /* más grande */
  height: 44px;
  border-radius: 12px;
  display:flex;
  align-items:center;
  justify-content:center;            /* centrado perfecto */
  cursor:pointer;
  text-decoration:none;
  transition:background .18s,transform .15s;
}
.si i{
  font-size: 22px;                   /* más grande */
  color:var(--ink3);
  transition:color .18s;
  line-height: 1;                    /* evita desbordes */
}
.si.on{background:linear-gradient(135deg,rgba(245,123,2,.22),rgba(229,72,24,.14));box-shadow:0 0 16px rgba(245,123,2,.18);}
.si.on i{color:var(--or);}
.si:hover:not(.on){background:rgba(255,255,255,.06);transform:scale(1.08);}
.si:hover:not(.on) i{color:var(--ink2);}
.si-sep{
  height: 1px;
  width: 32px;                       /* proporcionado */
  background:var(--line);
  margin: 4px 0;
}

/* MAIN */
.main-scroll{overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;background:transparent;position:relative;z-index:1;}
.main-scroll::-webkit-scrollbar{width:5px;}
.main-scroll::-webkit-scrollbar-track{background:transparent;}
.main-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px;}


/* PAGE HEADER */
.ph{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:2px;flex-wrap:wrap;gap:10px;}
.ph-title{font-family:'Archivo',sans-serif;font-weight:800;font-size:19px;color:var(--ink);letter-spacing:-.01em;}
.ph-sub{font-size:11px;color:var(--ink3);margin-top:3px;}

.btn-new{
  background:var(--or);color:#fff;border:none;border-radius:20px;padding:8px 16px;
  font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;
  transition:transform .18s ease, box-shadow .18s ease, background .18s ease;
}
.btn-new:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(245,123,2,.32);background:#ff8a1f;}
.btn-new:active{transform:translateY(0) scale(.96);}

/* FILTROS */
.filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:2px;}
.search-input{background:var(--s2);border:0.5px solid var(--line);border-radius:9px;
  padding:9px 13px;font-size:12px;color:var(--ink);outline:none;flex:1;min-width:200px;transition:border-color .15s;}
.search-input::placeholder{color:var(--ink3);}
.search-input:focus{border-color:var(--or);}

.btn-ghost{
  background:var(--s2);border:0.5px solid var(--line);border-radius:9px;color:var(--ink2);
  padding:9px 15px;font-size:12px;font-weight:500;cursor:pointer;text-decoration:none;
  display:inline-flex;align-items:center;gap:6px;transition:transform .16s, border-color .16s, color .16s;
}
.btn-ghost:hover{transform:translateY(-2px);border-color:var(--or);color:var(--or);}
.btn-ghost:active{transform:translateY(0) scale(.96);}

/* KPI mini stats */
.stat-row{display:flex;gap:8px;flex-wrap:wrap;}
.stat-chip{background:var(--s1);border:0.5px solid var(--line);border-radius:11px;padding:8px 14px;text-align:center;min-width:76px;}
.stat-chip b{display:block;font-family:'Archivo';font-size:16px;font-weight:800;color:var(--ink);}
.stat-chip small{font-size:8.5px;color:var(--ink3);text-transform:uppercase;letter-spacing:.06em;}

/* ═══ DOSSIER CARD GRID (Sello y Folio) ═══ */
.section-hdr{display:flex;align-items:center;justify-content:space-between;padding:0 2px;}
.section-hdr span{font-size:11px;font-weight:600;color:var(--ink);display:flex;align-items:center;gap:6px;}
.section-hdr small{font-size:10px;color:var(--ink3);font-weight:400;}

.grid{display:grid;grid-template-columns:repeat(auto-fill, minmax(268px,1fr));gap:12px;}

.dossier{
  background:var(--s1);border:0.5px solid var(--line);border-radius:14px;padding:16px;
  position:relative;overflow:hidden;transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
}
.dossier:hover{transform:translateY(-3px);box-shadow:0 16px 34px rgba(0,0,0,.45);border-color:rgba(255,255,255,.13);}

.ribbon{
  position:absolute;top:12px;right:-30px;width:120px;text-align:center;padding:3px 0;
  transform:rotate(40deg);font-size:8.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  box-shadow:0 3px 8px rgba(0,0,0,.35);
}
.ribbon.aprobada{background:var(--green);color:#0d2015;}
.ribbon.rechazada{background:var(--re);color:#fff;}
.ribbon.pendiente{background:var(--or);color:#2a1400;}

.dossier-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;padding-right:46px;}
.dossier-id{font-family:'Archivo';font-weight:700;font-size:11px;color:var(--or);letter-spacing:.01em;}
.dossier-fecha{font-size:10px;color:var(--ink3);margin-top:2px;}
.dossier-cliente{font-family:'Archivo';font-weight:700;font-size:15px;margin-bottom:2px;color:var(--ink);
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.dossier-sub{font-size:11px;color:var(--ink2);margin-bottom:14px;}

.dossier-monto{display:flex;align-items:baseline;gap:6px;margin-bottom:14px;}
.dossier-monto .n{font-family:'Archivo';font-size:22px;font-weight:800;color:var(--ink);}
.dossier-monto .c{font-size:10px;color:var(--ink3);font-weight:600;}

/* sello de tinta */
.stamp-seal{
  position:absolute;bottom:14px;right:16px;width:52px;height:52px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;transform:rotate(-11deg);
  font-family:'Archivo';font-size:7.3px;font-weight:800;letter-spacing:.03em;text-align:center;
  border:2px solid;opacity:.85;line-height:1.15;pointer-events:none;
}
.stamp-seal.aprobada{color:var(--green);border-color:var(--green);background:var(--green-soft);}
.stamp-seal.rechazada{color:var(--re);border-color:var(--re);background:var(--re-soft);}
.stamp-seal.pendiente{color:var(--or);border-color:var(--or);border-style:dashed;background:var(--or-soft);}

.dossier-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:2px;padding-right:56px;}
.pill-btn{
  font-size:10.5px;font-weight:600;border:0.5px solid var(--line);background:var(--s3);color:var(--ink2);
  border-radius:8px;padding:6px 10px;cursor:pointer;transition:transform .16s, background .16s, border-color .16s, color .16s;
  text-decoration:none;font-family:inherit;
}
.pill-btn:hover{transform:translateY(-1px);background:rgba(245,123,2,.14);border-color:rgba(245,123,2,.3);color:var(--or);}
.pill-btn:active{transform:translateY(0) scale(.94);}
.pill-btn.approve:hover{background:var(--green-soft);border-color:rgba(74,222,128,.3);color:var(--green);}
.pill-btn.reject:hover{background:var(--re-soft);border-color:rgba(229,72,24,.3);color:var(--re);}
.pill-btn.danger:hover{background:var(--re-soft);border-color:rgba(229,72,24,.3);color:var(--re);}
.pill-btn.email:hover{background:rgba(96,165,250,.14);border-color:rgba(96,165,250,.3);color:#60a5fa;}

.empty{padding:26px 16px;font-size:12px;color:var(--ink3);text-align:center;background:var(--s1);
  border:0.5px dashed var(--line);border-radius:14px;}

/* PAGINACIÓN */
.pagination{display:flex;justify-content:center;gap:6px;padding:14px 0 6px;flex-wrap:wrap;}
.pagination a,.pagination span{display:inline-block;padding:5px 11px;border-radius:8px;
  font-size:11px;background:var(--s2);border:0.5px solid var(--line);color:var(--ink2);
  text-decoration:none;transition:transform .15s, background .15s, color .15s, border-color .15s;}
.pagination a:hover{background:rgba(245,123,2,.12);border-color:var(--or);color:var(--or);transform:translateY(-1px);}
.pagination .active{background:var(--or);border-color:var(--or);color:#fff;font-weight:600;}

/* ═══ MODAL: eliminar (Sello y Folio, dark) ═══ */
.modal-backdrop{position:fixed;inset:0;background:rgba(5,3,10,.65);backdrop-filter:blur(2px);z-index:100;
  display:none;align-items:center;justify-content:center;}
.modal-backdrop.open{display:flex;}
.modal{
  background:var(--s1);border:0.5px solid var(--line);border-radius:18px;
  padding:24px 24px 20px;max-width:380px;width:90%;box-shadow:0 30px 70px rgba(0,0,0,.55);
  opacity:0;transform:translateY(16px) scale(.96);animation:popIn .3s cubic-bezier(.22,.9,.32,1.15) forwards;
}
@keyframes popIn{ to{opacity:1;transform:translateY(0) scale(1);} }
.modal-icon{
  width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;
  font-size:19px;margin-bottom:12px;background:var(--re-soft);color:var(--re);
}
.modal-title{font-family:'Archivo';font-size:16px;font-weight:700;margin-bottom:8px;color:var(--ink);}
.modal-body{font-size:12.5px;color:var(--ink2);line-height:1.6;margin-bottom:18px;}
.modal-body strong{color:var(--ink);}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;}
.modal-cancel{background:var(--s3);border:0.5px solid var(--line);border-radius:10px;
  padding:9px 15px;font-size:12px;color:var(--ink2);cursor:pointer;font-family:inherit;transition:.16s;}
.modal-cancel:hover{background:rgba(255,255,255,.08);}
.modal-cancel:active{transform:scale(.96);}
.modal-confirm{background:var(--re);border:none;border-radius:10px;
  padding:9px 17px;font-size:12px;font-weight:600;color:#fff;cursor:pointer;font-family:inherit;transition:.16s;}
.modal-confirm:hover{background:#c93d16;box-shadow:0 8px 20px rgba(229,72,24,.35);}
.modal-confirm:active{transform:scale(.96);}

/* TOAST */
.toast{position:fixed;bottom:20px;right:20px;background:var(--s1);border:0.5px solid var(--line);
  border-left:3px solid var(--or);border-radius:10px;padding:12px 16px;font-size:12px;
  color:var(--ink);z-index:200;transform:translateX(130%);transition:transform .4s cubic-bezier(.2,.8,.3,1);
  box-shadow:0 14px 30px rgba(0,0,0,.4);}
.toast.show{transform:translateX(0);}

/* RESPONSIVE */
@media(max-width:768px){
  .shell{grid-template-columns:1fr;}
  .sidebar{display:none;position:fixed;top:0;left:0;bottom:0;width:56px;z-index:210;}
  .sidebar.open{display:flex;box-shadow:8px 0 24px rgba(0,0,0,.45);}
  .hamburger{display:block;}
  .topbar .tb-search{display:none;}
  .topbar{padding:0 12px;gap:6px;}
  .tb-name{font-size:13px;}
  .tb-pill{display:none;}
  .main-scroll{padding:10px;}
  .grid{grid-template-columns:1fr;}
}
@media(max-width:400px){
  .tb-name{display:none;}
}
</style>
</head>
<body>

<div class="toast" id="toast"></div>
<div class="modal-backdrop" id="modal">
  <div class="modal">
    <div class="modal-icon"><i class="ti ti-trash" aria-hidden="true"></i></div>
    <div class="modal-title">¿Eliminar cotización?</div>
    <div class="modal-body" id="modal-body">Esta acción no se puede deshacer.</div>
    <div class="modal-actions">
      <button class="modal-cancel" onclick="closeModal()">Cancelar</button>
      <button class="modal-confirm" id="modal-confirm-btn" onclick="confirmDelete()">Eliminar</button>
    </div>
  </div>
</div>

<div class="shell">

  <!-- TOPBAR (idéntica al dashboard) -->
  <header class="topbar">
    <button class="hamburger" onclick="toggleSidenav()" aria-label="Menú">☰</button>
    <div class="tb-logo"><img src="assets/img/logoss.png" alt="Fortress8"></div>
    <div class="tb-name">FORTRESS<span>8</span></div>
    <div class="tb-pill">
      <span style="color:var(--ink3)">USD/MXN</span>
      <b id="tc-val">—</b>
    </div>
    <div class="tb-actions">
      <div class="tb-avatar" onclick="toggleUserMenu()" id="user-trigger">
        <?= htmlspecialchars($auth->iniciales()) ?>
        <div class="user-menu" id="user-menu">
          <div class="user-name">
            <?= htmlspecialchars($auth->usuarioNombre()) ?>
            (<?= htmlspecialchars($auth->rol()) ?>)
          </div>
          <div class="sep"></div>
          <a href="cotizaciones.php?action=nueva">Nueva cotización</a>
          <a href="lista_cotizaciones.php">Mis cotizaciones</a>
          <a href="lista_precios.php">Lista de precios</a>
          <a href="comparar_listas.php">Comparar listas</a>
          <div class="sep"></div>
          <a href="logout.php">Cerrar sesión</a>
        </div>
      </div>
      <a href="cotizaciones.php?action=nueva" class="btn-new">
        <i class="ti ti-plus" aria-hidden="true"></i> Nueva
      </a>
    </div>
  </header>

  <!-- SIDEBAR (modificado) -->
  <nav class="sidebar" id="sidenav">
    <a class="si" href="dashboard.php" title="Dashboard">
      <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
    </a>
    <a class="si on" href="lista_cotizaciones.php" title="Cotizaciones">
      <i class="ti ti-file-invoice" aria-hidden="true"></i>
    </a>
    <a class="si" href="seguimiento.php" title="Seguimiento de clientes">
      <i class="ti ti-timeline" aria-hidden="true"></i>
    </a>
    <div class="si-sep"></div>
    <a class="si" href="lista_precios.php" title="Lista de precios">
      <i class="ti ti-list-details" aria-hidden="true"></i>
    </a>
    <a class="si" href="comparar_listas.php" title="Comparar listas">
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

  <!-- MAIN (contenido del listado) -->
  <main class="main-scroll">

    <div class="ph">
      <div>
        <div class="ph-title">Mis Cotizaciones</div>
        <div class="ph-sub">
          <?= $auth->esAdmin() ? 'Todas las cotizaciones del sistema' : 'Solo tus cotizaciones' ?>
          · <?= (new DateTime())->format('d \d\e F \d\e Y') ?>
          · Cada folio lleva su sello de estado
        </div>
      </div>
    </div>

    <!-- Filtro de búsqueda -->
    <div class="filter-bar">
      <form method="GET" action="lista_cotizaciones.php" style="display:flex;gap:6px;flex:1;flex-wrap:wrap;">
        <input
          type="text"
          name="buscar"
          class="search-input"
          placeholder="Buscar por N° cotización, cliente o empresa..."
          value="<?= htmlspecialchars($ctrl->busqueda) ?>"
        >
        <button type="submit" class="btn-new" style="border-radius:9px;">Buscar</button>
        <?php if ($ctrl->busqueda !== ''): ?>
          <a href="lista_cotizaciones.php" class="btn-ghost">Limpiar</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Cabecera de sección -->
    <div class="section-hdr">
      <span>
        <i class="ti ti-file-invoice" aria-hidden="true" style="font-size:13px;color:var(--or);"></i>
        Listado completo
      </span>
      <small>Total: <?= number_format($ctrl->totalRegistros) ?> cotizaciones</small>
    </div>

    <?php if (empty($ctrl->cotizaciones)): ?>
      <div class="empty">
        <?php if ($ctrl->busqueda !== ''): ?>
          No se encontraron cotizaciones con "<?= htmlspecialchars($ctrl->busqueda) ?>".
        <?php else: ?>
          Sin cotizaciones. <a href="cotizaciones.php?action=nueva" style="color:var(--or)">Crear la primera →</a>
        <?php endif; ?>
      </div>
    <?php else: ?>

    <!-- Grid de dossiers -->
    <div class="grid" id="cot-grid">
      <?php foreach ($ctrl->cotizaciones as $c):
        $cliente      = clienteDisplay($c);
        $badgeClass   = estadoBadgeClass($c['estado'] ?? '');
        $estadoLabel  = estado_label($c['estado'] ?? '');
        $estadoNorm   = normalizar_estado($c['estado'] ?? '');
        $stampText    = estadoStampText($c['estado'] ?? '');
        $fecha        = $c['fecha'] ?? substr($c['created_at'] ?? '', 0, 10);
        $esPendiente  = $estadoNorm === 'pendiente';
      ?>
      <div class="dossier" id="row-<?= $c['id'] ?>">
        <div class="ribbon <?= $estadoNorm ?>"><?= htmlspecialchars($estadoLabel) ?></div>

        <div class="dossier-top">
          <div>
            <div class="dossier-id"><?= htmlspecialchars($c['numero_cotizacion'] ?? '—') ?></div>
            <div class="dossier-fecha"><?= htmlspecialchars($fecha) ?></div>
          </div>
        </div>

        <div class="dossier-cliente" title="<?= htmlspecialchars($cliente) ?>"><?= htmlspecialchars($cliente) ?></div>
        <div class="dossier-sub">Cotización comercial</div>

        <div class="dossier-monto">
          <span class="n">$<?= number_format($c['total'] ?? 0, 2) ?></span>
          <span class="c">USD</span>
        </div>

        <div class="dossier-actions">
          <a href="ver_cotizacion.php?id=<?= $c['id'] ?>" class="pill-btn">Ver</a>
          <a href="cotizaciones.php?action=editar&id=<?= $c['id'] ?>" class="pill-btn">Editar</a>
          <?php if ($auth->esAdmin() && $esPendiente): ?>
            <button class="pill-btn approve" data-id="<?= $c['id'] ?>" data-estado="aprobada">✓ Aprobar</button>
            <button class="pill-btn reject"  data-id="<?= $c['id'] ?>" data-estado="rechazada">✗ Rechazar</button>
          <?php endif; ?>
          <button
            class="pill-btn email"
            title="Enviar por correo"
            onclick="abrirEmail(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['cliente_email'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($c['numero_cotizacion'] ?? '')) ?>')"
          >✉ Correo</button>
          <button
            class="pill-btn danger"
            onclick="askDelete(
              <?= $c['id'] ?>,
              '<?= htmlspecialchars(addslashes($c['numero_cotizacion'] ?? '—')) ?>',
              '<?= htmlspecialchars(addslashes($cliente)) ?>'
            )"
          >Eliminar</button>
        </div>

        <div class="stamp-seal <?= $estadoNorm ?>"><?= $stampText ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <?php if ($ctrl->totalPaginas > 1): ?>
    <div class="pagination">
      <?php if ($ctrl->paginaActual > 1): ?>
        <a href="?pagina=<?= $ctrl->paginaActual - 1 ?>&buscar=<?= urlencode($ctrl->busqueda) ?>">← Anterior</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $ctrl->totalPaginas; $i++): ?>
        <?php if ($i === $ctrl->paginaActual): ?>
          <span class="active"><?= $i ?></span>
        <?php else: ?>
          <a href="?pagina=<?= $i ?>&buscar=<?= urlencode($ctrl->busqueda) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($ctrl->paginaActual < $ctrl->totalPaginas): ?>
        <a href="?pagina=<?= $ctrl->paginaActual + 1 ?>&buscar=<?= urlencode($ctrl->busqueda) ?>">Siguiente →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

  </main>
</div>

<script>
// ── Sidenav móvil ─────────────────────────────────────────────────────────
function toggleSidenav(){ document.getElementById('sidenav').classList.toggle('open'); }
document.addEventListener('click', function(e) {
  const nav = document.getElementById('sidenav');
  const hamb = document.querySelector('.hamburger');
  if (nav && hamb && !nav.contains(e.target) && !hamb.contains(e.target)) {
    nav.classList.remove('open');
  }
});

// ── User menu ─────────────────────────────────────────────────────────────
function toggleUserMenu(){ document.getElementById('user-menu').classList.toggle('open'); }
document.addEventListener('click', e => {
  const t = document.getElementById('user-trigger');
  if (t && !t.contains(e.target)) document.getElementById('user-menu')?.classList.remove('open');
});

// ── Toast ─────────────────────────────────────────────────────────────────
function showToast(msg, borderColor) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.style.borderLeftColor = borderColor || 'var(--or)';
  el.classList.add('show');
  clearTimeout(window.toastTimer);
  window.toastTimer = setTimeout(() => el.classList.remove('show'), 3000);
}
<?php if ($ctrl->showSuccessToast): ?>showToast('✓ Cotización guardada', 'var(--green)'); window.NotifSound?.playCreate();<?php endif; ?>

// ── Modal eliminar ────────────────────────────────────────────────────────
let pendingDeleteId = null;
function askDelete(id, num, cliente) {
  pendingDeleteId = id;
  document.getElementById('modal-body').innerHTML =
    `¿Seguro que deseas eliminar la cotización <strong style="color:var(--or)">${num}</strong>
    de <strong>${cliente}</strong>?<br><br>Esta acción no se puede deshacer.`;
  document.getElementById('modal').classList.add('open');
}
function closeModal() {
  document.getElementById('modal').classList.remove('open');
  pendingDeleteId = null;
}
document.getElementById('modal').addEventListener('click', e => {
  if (e.target === document.getElementById('modal')) closeModal();
});
async function confirmDelete() {
  if (!pendingDeleteId) return;
  const btn = document.getElementById('modal-confirm-btn');
  btn.textContent = 'Eliminando…'; btn.disabled = true;
  try {
    const fd = new FormData(); fd.append('action','eliminar'); fd.append('id', pendingDeleteId);
    const res = await fetch('lista_cotizaciones.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
      const row = document.getElementById('row-' + pendingDeleteId);
      if (row) { row.style.transition = 'opacity .3s, transform .3s'; row.style.opacity = '0'; row.style.transform = 'scale(.94)';
                 setTimeout(() => row.remove(), 320); }
      showToast('✓ Cotización eliminada', 'var(--re)');
    } else {
      showToast('✗ ' + (data.error || 'Error al eliminar'), 'var(--re)');
    }
  } catch(_) { showToast('✗ Error de conexión', 'var(--re)'); }
  btn.textContent = 'Eliminar'; btn.disabled = false; closeModal();
}

// ── Aprobar / Rechazar (admin) ───────────────────────────────────────────
document.querySelectorAll('.pill-btn.approve, .pill-btn.reject').forEach(btn => {
  btn.addEventListener('click', async function() {
    const id = this.dataset.id;
    const estado = this.dataset.estado;
    if (!confirm(`¿Cambiar estado a ${estado.toUpperCase()}?`)) return;
    const fd = new FormData(); fd.append('id', id); fd.append('estado', estado);
    try {
      const res = await fetch('cambiar_estado.php', { method:'POST', body:fd });
      const data = await res.json();
      if (data.ok) {
        showToast(`✓ Cotización ${estado}`, 'var(--green)');
        window.NotifSound?.[estado === 'aprobada' ? 'playApprove' : 'playReject']?.();
        setTimeout(() => location.reload(), 450);
      } else {
        showToast('✗ ' + (data.error || 'Error al cambiar estado'), 'var(--re)');
      }
    } catch(_) { showToast('✗ Error de conexión', 'var(--re)'); }
  });
});

// ── Tipo de cambio ────────────────────────────────────────────────────────
fetch('API/api_tipo_cambio.php').then(r => r.json()).then(d => {
  const v = d.MXN || d.mxn || null;
  if (v) document.getElementById('tc-val').textContent = parseFloat(v).toFixed(2);
}).catch(() => { document.getElementById('tc-val').textContent = 'N/D'; });


</script>
<script src="assets/js/notif-sound.js"></script>
<script src="assets/js/push-client.js" defer></script>
<!-- ── Modal: enviar cotización por correo (Sello y Folio, dark) ───────── -->
<style>
.email-backdrop{position:fixed;inset:0;background:rgba(5,3,10,.65);backdrop-filter:blur(3px);
  z-index:500;display:none;align-items:center;justify-content:center;}
.email-backdrop.open{display:flex;}
.email-modal{
  background:var(--s1);border:0.5px solid var(--line);border-radius:18px;
  padding:24px 26px;width:100%;max-width:440px;box-shadow:0 30px 70px rgba(0,0,0,.55);
  opacity:0;transform:translateY(16px) scale(.96);animation:popIn .3s cubic-bezier(.22,.9,.32,1.15) forwards;
}
.email-modal .modal-icon{background:rgba(96,165,250,.14);color:#60a5fa;}
.email-modal h2{font-family:'Archivo';font-size:16px;font-weight:700;color:var(--ink);margin:0 0 4px;}
.email-field{margin-bottom:13px;}
.email-field label{display:block;font-size:10px;color:var(--ink3);text-transform:uppercase;
  letter-spacing:.06em;margin-bottom:6px;font-weight:600;}
.email-field input,.email-field textarea{width:100%;background:var(--s2);
  border:0.5px solid var(--line);border-radius:9px;color:var(--ink);
  font-family:'Inter',sans-serif;font-size:13px;padding:9px 12px;outline:none;
  box-sizing:border-box;transition:border-color .15s;}
.email-field input:focus,.email-field textarea:focus{border-color:#60a5fa;}
.email-field textarea{resize:vertical;min-height:70px;line-height:1.5;}
.email-modal-actions{display:flex;gap:8px;margin-top:18px;justify-content:flex-end;}
.btn-em-cancel{padding:9px 16px;border-radius:10px;font-size:12px;cursor:pointer;
  background:var(--s3);border:0.5px solid var(--line);
  color:var(--ink2);font-family:inherit;transition:.16s;}
.btn-em-cancel:hover{background:rgba(255,255,255,.08);}
.btn-em-cancel:active{transform:scale(.96);}
.btn-em-send{padding:9px 18px;border-radius:10px;font-size:12px;font-weight:600;
  cursor:pointer;background:#1d4ed8;border:none;color:#fff;font-family:inherit;
  display:flex;align-items:center;gap:6px;transition:background .16s,transform .12s, box-shadow .16s;}
.btn-em-send:hover{background:#1e40af;box-shadow:0 8px 20px rgba(29,78,216,.35);}
.btn-em-send:active{transform:scale(.96);}
.btn-em-send:disabled{opacity:.5;cursor:not-allowed;}
.em-status{font-size:11px;text-align:center;margin-top:8px;min-height:16px;}
.em-status.ok{color:#4ade80;}.em-status.err{color:#f87171;}
</style>

<div class="email-backdrop" id="email-backdrop" onclick="if(event.target===this)cerrarEmail()">
  <div class="email-modal">
    <div class="modal-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
    </div>
    <h2>Enviar cotización por correo</h2>
    <p id="em-num" style="font-size:11px;color:var(--or);margin:2px 0 16px;font-weight:600;"></p>
    <div class="email-field">
      <label>Correo del cliente *</label>
      <input type="email" id="em-destino" placeholder="cliente@empresa.com">
    </div>
    <div class="email-field">
      <label>Con copia (CC)</label>
      <input type="email" id="em-cc" placeholder="opcional">
    </div>
    <div class="email-field">
      <label>Mensaje personalizado</label>
      <textarea id="em-mensaje" placeholder="Estimado cliente, adjunto nuestra propuesta..."></textarea>
    </div>
    <div class="email-modal-actions">
      <button class="btn-em-cancel" onclick="cerrarEmail()">Cancelar</button>
      <button class="btn-em-send" id="btn-em-send" onclick="enviarEmail()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Enviar
      </button>
    </div>
    <div class="em-status" id="em-status"></div>
  </div>
</div>

<script>
let _emailCotId = 0;

function abrirEmail(id, email, numero) {
  _emailCotId = id;
  document.getElementById('em-destino').value = email || '';
  document.getElementById('em-cc').value      = '';
  document.getElementById('em-mensaje').value = '';
  document.getElementById('em-num').textContent = numero;
  document.getElementById('em-status').textContent = '';
  document.getElementById('em-status').className = 'em-status';
  document.getElementById('btn-em-send').disabled = false;
  document.getElementById('btn-em-send').textContent = 'Enviar';
  document.getElementById('email-backdrop').classList.add('open');
  setTimeout(() => document.getElementById('em-destino').focus(), 80);
}

function cerrarEmail() {
  document.getElementById('email-backdrop').classList.remove('open');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') cerrarEmail();
});

async function enviarEmail() {
  const destino = document.getElementById('em-destino').value.trim();
  const cc      = document.getElementById('em-cc').value.trim();
  const mensaje = document.getElementById('em-mensaje').value.trim();
  const status  = document.getElementById('em-status');
  const btn     = document.getElementById('btn-em-send');

  if (!destino || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(destino)) {
    status.textContent = 'Ingresa un correo válido.';
    status.className = 'em-status err';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Enviando...';
  status.textContent = '';

  try {
    const res  = await fetch('API/api_enviar_correo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cotizacion_id: _emailCotId, email_destino: destino, email_cc: cc, mensaje }),
    });
    const data = await res.json();

    if (data.ok) {
      status.textContent = `✓ Correo enviado a ${data.email_destino}`;
      status.className = 'em-status ok';
      btn.textContent = '✓ Enviado';
      window.NotifSound?.playSend();
      setTimeout(cerrarEmail, 2000);
    } else {
      status.textContent = data.error || 'Error al enviar.';
      status.className = 'em-status err';
      btn.disabled = false;
      btn.textContent = 'Enviar';
    }
  } catch {
    status.textContent = 'Error de conexión.';
    status.className = 'em-status err';
    btn.disabled = false;
    btn.textContent = 'Enviar';
  }
}
</script>

</body>
</html>