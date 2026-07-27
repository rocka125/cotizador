<?php
/**
 * views/auditoria.view.php — Auditoría del sistema. Diseño 1: "Línea de tiempo forense".
 *
 * No contiene queries ni lógica de negocio.
 * Solo echo, htmlspecialchars, foreach e if/else de presentación.
 *
 * Variables disponibles (inyectadas por AuditoriaController):
 *   $ctrl->stats          array   — logins_hoy, creadas_hoy, editadas_hoy, eliminadas
 *   $ctrl->usuarios       array   — lista para el selector de filtros
 *   $ctrl->sesiones       array   — registros de sesión de la página actual
 *   $ctrl->totalSesiones  int
 *   $ctrl->totalPagSes    int
 *   $ctrl->auditorias     array   — movimientos de cotizaciones de la página actual
 *   $ctrl->totalAudits    int
 *   $ctrl->totalPagAud    int
 *   $ctrl->filtroTipo     string  — 'todo' | 'sesiones' | 'cotizaciones'
 *   $ctrl->filtroAccion   string
 *   $ctrl->filtroUsuario  int
 *   $ctrl->pagina         int
 *   $ctrl->urlPagina(n)   string  — helper de URL con filtros activos
 *   $auth->esAdmin()      bool
 *   $auth->iniciales()    string
 *   $auth->usuarioNombre() string
 */
function accion_badge(string $accion): string {
    $map = [
        'crear'         => ['color' => '#4ade80', 'icon' => 'ti-file-plus',   'label' => 'Creó'],
        'editar'        => ['color' => '#F57B02', 'icon' => 'ti-edit',        'label' => 'Editó'],
        'eliminar'      => ['color' => '#E54818', 'icon' => 'ti-trash',       'label' => 'Eliminó'],
        'cambio_estado' => ['color' => '#c084fc', 'icon' => 'ti-arrows-shuffle','label' => 'Cambió estado'],
        'ver'           => ['color' => '#94a3b8', 'icon' => 'ti-eye',         'label' => 'Vio'],
    ];
    $d = $map[$accion] ?? ['color' => '#888', 'icon' => 'ti-point', 'label' => ucfirst($accion)];
    return "<span class=\"tl-tag\" style=\"--tc:{$d['color']}\"><i class=\"ti {$d['icon']}\" aria-hidden=\"true\"></i>{$d['label']}</span>";
}
function sesion_badge(string $accion): string {
    return $accion === 'login'
        ? '<span class="tl-tag" style="--tc:#4ade80"><i class="ti ti-login" aria-hidden="true"></i>Login</span>'
        : '<span class="tl-tag" style="--tc:#E54818"><i class="ti ti-logout" aria-hidden="true"></i>Logout</span>';
}
function accion_color(string $accion): string {
    $map = ['crear'=>'#4ade80','editar'=>'#F57B02','eliminar'=>'#E54818','cambio_estado'=>'#c084fc','ver'=>'#94a3b8','login'=>'#4ade80','logout'=>'#E54818'];
    return $map[$accion] ?? '#F57B02';
}
function formatDetalle(string $detalleRaw): string {
    if ($detalleRaw === '') return '';
    $d = json_decode($detalleRaw, true);
    if (!is_array($d)) return $detalleRaw;
    $partes = [];
    if (isset($d['estado_anterior'], $d['estado_nuevo']))
        $partes[] = "Estado: {$d['estado_anterior']} → {$d['estado_nuevo']}";
    if (isset($d['cliente'])) $partes[] = 'Cliente: ' . $d['cliente'];
    if (isset($d['total']))   $partes[] = 'Total: $' . number_format((float)$d['total'], 2);
    return implode(' | ', $partes);
}

$hayFiltros = $ctrl->filtroUsuario || $ctrl->filtroAccion || $ctrl->filtroTipo !== 'todo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Fortress8 | Auditoría</title>
<link rel="icon" href="assets/img/favicon.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
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
        .then(r => console.log("SW registrado:", r.scope)).catch(e => console.error("SW error:", e));
    });
  }
</script>
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0D0A14;--s1:#13101C;--s2:#1A1626;--s3:#221E30;
  --line:rgba(255,255,255,.07);
  --or:#F57B02;--re:#E54818;--pk:#FF2D6B;
  --ink:#F5F0FF;--ink2:#9B94B8;--ink3:#5C5478;
  --green:#4ade80;
}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes drawLine{from{transform:scaleY(0);}to{transform:scaleY(1);}}
@keyframes pulseDot{0%,100%{box-shadow:0 0 0 0 var(--tc,rgba(245,123,2,.5));}50%{box-shadow:0 0 0 6px transparent;}}

body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--ink);height:100vh;height:100dvh;overflow:hidden;position:relative;}
body::before{content:'';position:fixed;inset:0;background:
  radial-gradient(ellipse 55% 45% at 8% 0%,rgba(245,123,2,.16) 0%,transparent 60%),
  radial-gradient(ellipse 45% 55% at 100% 100%,rgba(229,72,24,.13) 0%,transparent 60%),
  radial-gradient(ellipse 30% 35% at 90% 5%,rgba(255,45,107,.08) 0%,transparent 55%);
  pointer-events:none;z-index:0;animation:fadeIn 1s ease both;}
.shell{display:grid;grid-template-columns:50px 1fr;grid-template-rows:50px 1fr;height:100vh;height:100dvh;position:relative;z-index:1;}

.topbar{grid-column:1/-1;background:rgba(19,16,28,.90);backdrop-filter:blur(10px);
  border-bottom:0.5px solid var(--line);display:flex;align-items:center;gap:10px;padding:0 14px;z-index:20;}
.tb-logo{width:30px;height:30px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.tb-logo img{width:100%;height:100%;object-fit:contain;}
.tb-name{font-size:13px;font-weight:500;color:var(--ink);flex-shrink:0;}
.tb-name span{color:var(--or);}
.tb-pill{font-size:10px;color:var(--ink3);background:var(--s2);border:0.5px solid var(--line);
  border-radius:20px;padding:3px 9px;display:flex;align-items:center;gap:5px;}
.tb-pill b{color:var(--green);font-weight:400;}
.tb-actions{display:flex;align-items:center;gap:6px;margin-left:auto;}
.tb-avatar{width:28px;height:28px;border-radius:50%;background:var(--re);display:flex;align-items:center;
  justify-content:center;font-size:10px;font-weight:500;color:#fff;cursor:pointer;position:relative;flex-shrink:0;}
.hamburger{display:none;background:none;border:none;color:var(--ink);font-size:20px;cursor:pointer;padding:4px;}
.user-menu{position:absolute;top:38px;right:0;background:var(--s1);border:0.5px solid var(--line);
  border-radius:10px;min-width:160px;z-index:200;display:none;overflow:hidden;}
.user-menu.open{display:block;animation:fadeUp .18s ease both;}
.user-menu a{display:block;padding:8px 12px;font-size:12px;color:var(--ink2);text-decoration:none;transition:background .15s;}
.user-menu a:hover{background:rgba(255,255,255,.04);color:var(--ink);}
.user-menu .sep{height:0.5px;background:var(--line);}
.user-name{padding:8px 12px 6px;font-size:11px;font-weight:500;color:var(--ink);}
.btn-new{background:var(--or);color:#fff;border:none;border-radius:20px;padding:5px 12px;
  font-size:11px;font-weight:500;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;
  transition:transform .16s,box-shadow .16s;}
.btn-new:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(245,123,2,.35);}

/* ============================================================
   SIDEBAR — modificado con fondo negro e íconos más grandes
   ============================================================ */
.sidebar{
  background: #0B0708;               /* fondo negro sólido */
  border-right: 0.5px solid var(--line);
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
  transition:background .15s,transform .15s;
}
.si i{
  font-size: 22px;                   /* más grande */
  color:var(--ink3);
  transition:color .15s;
  line-height: 1;                    /* evita desbordes */
}
.si.on{
  background:rgba(245,123,2,.14);
}
.si.on i{
  color:var(--or);
}
.si:hover:not(.on){
  background:rgba(255,255,255,.05);
  transform:scale(1.06);
}
.si:hover:not(.on) i{
  color:var(--ink2);
}
.si-sep{
  height:0.5px;
  width:32px;                        /* más ancho */
  background:var(--line);
  margin:4px 0;
}

.main-scroll{overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:14px;background:var(--bg);}
.main-scroll::-webkit-scrollbar{width:4px;}
.main-scroll::-webkit-scrollbar-thumb{background:var(--s3);border-radius:2px;}

.ph{margin-bottom:2px;animation:fadeUp .35s ease both;}
.ph-title{font-size:18px;font-weight:700;color:var(--ink);}
.ph-sub{font-size:11.5px;color:var(--ink3);margin-top:3px;}

/* KPI glow strip */
.kpi-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.kpi-glow{background:var(--s1);border:0.5px solid var(--line);border-radius:14px;padding:14px 16px;
  position:relative;overflow:hidden;transition:transform .2s,border-color .2s;animation:fadeUp .4s ease both;}
.kpi-glow:nth-child(1){animation-delay:.05s;} .kpi-glow:nth-child(2){animation-delay:.10s;}
.kpi-glow:nth-child(3){animation-delay:.15s;} .kpi-glow:nth-child(4){animation-delay:.20s;}
.kpi-glow:hover{transform:translateY(-3px);border-color:var(--tc);box-shadow:0 12px 28px -8px var(--tc);}
.kpi-glow::after{content:'';position:absolute;width:70px;height:70px;border-radius:50%;background:var(--tc);
  filter:blur(30px);opacity:.35;top:-25px;right:-25px;}
.kpi-icon{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;
  background:color-mix(in srgb, var(--tc) 18%, transparent);margin-bottom:9px;position:relative;z-index:1;}
.kpi-icon i{font-size:15px;color:var(--tc);}
.kpi-num{font-size:24px;font-weight:700;color:var(--tc);position:relative;z-index:1;}
.kpi-lbl{font-size:10.5px;color:var(--ink3);margin-top:3px;position:relative;z-index:1;}

/* Filtros pill */
.filter-pillbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;background:var(--s1);
  border:0.5px solid var(--line);border-radius:16px;padding:10px 14px;animation:fadeUp .4s .1s ease both;}
.filter-pillbar select{background:var(--s2);border:0.5px solid var(--line);border-radius:20px;
  padding:6px 14px;color:var(--ink);font-family:'Inter';font-size:11px;outline:none;
  appearance:none;cursor:pointer;transition:border-color .15s;}
.filter-pillbar select:focus,.filter-pillbar select:hover{border-color:var(--or);}
.btn-filtrar{background:linear-gradient(135deg,var(--or),#ff9f3d);color:#1a0d04;border:none;border-radius:20px;
  padding:7px 16px;font-size:11px;font-weight:700;cursor:pointer;font-family:'Inter';
  display:inline-flex;align-items:center;gap:5px;transition:transform .16s,box-shadow .16s;}
.btn-filtrar:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(245,123,2,.4);}
.btn-filtrar:active{transform:scale(.96);}
.btn-limpiar{background:transparent;color:var(--ink3);border:0.5px solid var(--line);border-radius:20px;
  padding:7px 14px;font-size:11px;text-decoration:none;cursor:pointer;font-family:'Inter';
  display:inline-flex;align-items:center;gap:5px;transition:.16s;}
.btn-limpiar:hover{border-color:var(--re);color:var(--re);}

/* Section header */
.section-header{display:flex;align-items:center;gap:8px;margin-top:4px;}
.section-title{font-size:12.5px;font-weight:700;color:var(--ink);display:flex;align-items:center;gap:6px;}
.section-count{font-size:10px;color:var(--ink3);font-weight:400;}
.section-line{flex:1;height:0.5px;background:linear-gradient(90deg,var(--line),transparent);}

/* TIMELINE */
.timeline{position:relative;padding-left:26px;}
.timeline::before{content:'';position:absolute;left:8px;top:6px;bottom:6px;width:2px;
  background:linear-gradient(var(--or),transparent);transform-origin:top;animation:drawLine .6s ease both;}
.tl-item{position:relative;padding:10px 14px;margin-bottom:8px;background:var(--s1);border:0.5px solid var(--line);
  border-radius:12px;transition:transform .18s,border-color .18s,background .18s;
  animation:fadeUp .35s ease both;}
.tl-item:hover{transform:translateX(4px);border-color:rgba(245,123,2,.25);background:var(--s2);}
.tl-item::before{content:'';position:absolute;left:-22px;top:16px;width:9px;height:9px;border-radius:50%;
  background:var(--tc,var(--or));animation:pulseDot 2.4s ease-in-out infinite;}
.tl-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.tl-tag{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:600;
  background:color-mix(in srgb, var(--tc) 16%, transparent);color:var(--tc);}
.tl-tag i{font-size:11px;}
.tl-user{font-size:12px;font-weight:600;color:var(--ink);}
.tl-sub{font-size:10px;color:var(--ink3);}
.tl-meta{margin-left:auto;display:flex;align-items:center;gap:10px;font-size:10px;color:var(--ink3);flex-wrap:wrap;}
.tl-ip{font-family:monospace;}
.tl-detail{font-size:10.5px;color:var(--ink2);margin-top:5px;padding-top:6px;border-top:0.5px dashed var(--line);}
.tl-cot{color:var(--or);text-decoration:none;font-weight:600;font-size:11px;}
.tl-cot:hover{text-decoration:underline;}

.empty{padding:22px;font-size:12px;color:var(--ink3);text-align:center;background:var(--s1);
  border:0.5px dashed var(--line);border-radius:12px;}

.pagination{display:flex;justify-content:center;gap:6px;padding:8px 0 2px;flex-wrap:wrap;}
.pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;
  border-radius:50%;font-size:11px;background:var(--s2);border:0.5px solid var(--line);color:var(--ink2);
  text-decoration:none;transition:.15s;}
.pagination a:hover{background:rgba(245,123,2,.15);border-color:var(--or);color:var(--or);transform:translateY(-1px);}
.pagination .active{background:var(--or);border-color:var(--or);color:#fff;}

@media(max-width:768px){
  .shell{grid-template-columns:1fr;grid-template-rows:50px 1fr;height:100vh;}
  .sidebar{display:none;position:fixed;top:0;left:0;bottom:0;width:50px;z-index:210;}
  .sidebar.open{display:flex;box-shadow:8px 0 24px rgba(0,0,0,.45);}
  .hamburger{display:block;}
  .kpi-strip{grid-template-columns:repeat(2,1fr);}
  .filter-pillbar{flex-direction:column;align-items:stretch;}
  .filter-pillbar select{width:100%;}
  .tl-meta{margin-left:0;width:100%;justify-content:space-between;}
}
</style>
</head>
<body>

<div class="shell">

  <header class="topbar">
    <button class="hamburger" onclick="toggleSidenav()" aria-label="Menú">☰</button>
    <div class="tb-logo"><img src="assets/img/logoss.png" alt="Fortress8"></div>
    <div class="tb-name">FORTRESS<span>8</span></div>
    <div class="tb-pill"><span style="color:var(--ink3)">USD/MXN</span><b id="tc-val">—</b></div>
    <div class="tb-actions">
      <div class="tb-avatar" onclick="toggleUserMenu()" id="user-trigger">
        <?= htmlspecialchars($auth->iniciales()) ?>
        <div class="user-menu" id="user-menu">
          <div class="user-name"><?= htmlspecialchars($auth->usuarioNombre()) ?> (<?= $auth->esAdmin() ? 'Admin' : 'Vendedor' ?>)</div>
          <div class="sep"></div>
          <a href="cotizaciones.php?action=nueva">Nueva cotización</a>
          <a href="lista_cotizaciones.php">Mis cotizaciones</a>
          <a href="lista_precios.php">Lista de precios</a>
          <a href="comparar_listas.php">Comparar listas</a>
          <div class="sep"></div>
          <a href="logout.php">Cerrar sesión</a>
        </div>
      </div>
      <a href="cotizaciones.php?action=nueva" class="btn-new"><i class="ti ti-plus" aria-hidden="true"></i> Nueva</a>
    </div>
  </header>

  <!-- SIDEBAR (modificado) -->
  <nav class="sidebar" id="sidenav">
    <a class="si" href="dashboard.php" title="Dashboard"><i class="ti ti-layout-dashboard" aria-hidden="true"></i></a>
    <a class="si" href="lista_cotizaciones.php" title="Cotizaciones"><i class="ti ti-file-invoice" aria-hidden="true"></i></a>
    <a class="si" href="seguimiento.php" title="Seguimiento de clientes"><i class="ti ti-timeline" aria-hidden="true"></i></a>
    <div class="si-sep"></div>
    <a class="si" href="lista_precios.php" title="Lista de precios"><i class="ti ti-list-details" aria-hidden="true"></i></a>
    <a class="si" href="comparar_listas.php" title="Comparar listas"><i class="ti ti-copy" aria-hidden="true"></i></a>
    <?php if ($auth->esAdmin()): ?>
    <div class="si-sep"></div>
    <a class="si on" href="auditoria.php" title="Auditoría"><i class="ti ti-shield" aria-hidden="true"></i></a>
    <?php endif; ?>
    <div class="si-sep"></div>
    <a class="si" href="logout.php" title="Cerrar sesión"><i class="ti ti-logout" aria-hidden="true"></i></a>
  </nav>

  <main class="main-scroll">

    <div class="ph">
      <div class="ph-title">Auditoría del Sistema</div>
      <div class="ph-sub">Solo visible para administradores · Línea de tiempo de accesos y movimientos</div>
    </div>

    <div class="kpi-strip">
      <div class="kpi-glow" style="--tc:#F57B02"><div class="kpi-icon"><i class="ti ti-user-check" aria-hidden="true"></i></div><div class="kpi-num"><?= $ctrl->stats['logins_hoy'] ?></div><div class="kpi-lbl">Accesos hoy</div></div>
      <div class="kpi-glow" style="--tc:#4ade80"><div class="kpi-icon"><i class="ti ti-file-plus" aria-hidden="true"></i></div><div class="kpi-num"><?= $ctrl->stats['creadas_hoy'] ?></div><div class="kpi-lbl">Creadas hoy</div></div>
      <div class="kpi-glow" style="--tc:#FF2D6B"><div class="kpi-icon"><i class="ti ti-file-edit" aria-hidden="true"></i></div><div class="kpi-num"><?= $ctrl->stats['editadas_hoy'] ?></div><div class="kpi-lbl">Editadas hoy</div></div>
      <div class="kpi-glow" style="--tc:#E54818"><div class="kpi-icon"><i class="ti ti-file-x" aria-hidden="true"></i></div><div class="kpi-num"><?= $ctrl->stats['eliminadas'] ?></div><div class="kpi-lbl">Eliminaciones totales</div></div>
    </div>

    <form method="GET" class="filter-pillbar">
      <select name="usuario" id="f-usuario">
        <option value="0">Todos los usuarios</option>
        <?php foreach ($ctrl->usuarios as $u): ?>
        <option value="<?= $u['id'] ?>" <?= $ctrl->filtroUsuario == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="accion" id="f-accion">
        <option value="">Toda acción</option>
        <?php foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar', 'cambio_estado' => 'Cambio de estado'] as $val => $lbl): ?>
        <option value="<?= $val ?>" <?= $ctrl->filtroAccion === $val ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
      <select name="tipo" id="f-tipo">
        <option value="todo" <?= $ctrl->filtroTipo === 'todo' ? 'selected' : '' ?>>Todo</option>
        <option value="sesiones" <?= $ctrl->filtroTipo === 'sesiones' ? 'selected' : '' ?>>Solo accesos</option>
        <option value="cotizaciones" <?= $ctrl->filtroTipo === 'cotizaciones' ? 'selected' : '' ?>>Solo cotizaciones</option>
      </select>
      <button type="submit" class="btn-filtrar"><i class="ti ti-filter" aria-hidden="true"></i> Filtrar</button>
      <?php if ($hayFiltros): ?>
      <a href="auditoria.php" class="btn-limpiar"><i class="ti ti-x" aria-hidden="true"></i> Limpiar</a>
      <?php endif; ?>
    </form>

    <?php if ($ctrl->filtroTipo !== 'cotizaciones'): ?>
    <div class="section-header">
      <span class="section-title"><i class="ti ti-login" style="color:var(--or);"></i> Accesos al sistema <span class="section-count">(<?= number_format($ctrl->totalSesiones) ?>)</span></span>
      <div class="section-line"></div>
    </div>
    <?php if (!empty($ctrl->sesiones)): ?>
    <div class="timeline">
      <?php foreach ($ctrl->sesiones as $i => $row): $c = accion_color($row['accion']); ?>
      <div class="tl-item" style="--tc:<?= $c ?>;animation-delay:<?= min($i * .04, .4) ?>s;">
        <div class="tl-row">
          <?= sesion_badge($row['accion']) ?>
          <span class="tl-user"><?= htmlspecialchars($row['usuario_nombre']) ?></span>
          <span class="tl-sub"><?= htmlspecialchars($row['correo'] ?? '') ?></span>
          <span class="tl-meta">
            <span class="tl-ip"><?= htmlspecialchars($row['ip']) ?></span>
            <span><?= date('d/m/Y H:i:s', strtotime($row['created_at'])) ?></span>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty">Sin registros de acceso aún.</div>
    <?php endif; ?>
    <?php if ($ctrl->totalPagSes > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $ctrl->totalPagSes; $p++): ?>
      <a href="<?= htmlspecialchars($ctrl->urlPagina($p)) ?>" class="<?= $p === $ctrl->pagina ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($ctrl->filtroTipo !== 'sesiones'): ?>
    <div class="section-header">
      <span class="section-title"><i class="ti ti-file-invoice" style="color:var(--or);"></i> Movimientos de cotizaciones <span class="section-count">(<?= number_format($ctrl->totalAudits) ?>)</span></span>
      <div class="section-line"></div>
    </div>
    <?php if (!empty($ctrl->auditorias)): ?>
    <div class="timeline">
      <?php foreach ($ctrl->auditorias as $i => $row):
        $detalle = formatDetalle($row['detalle'] ?? '');
        $c = accion_color($row['accion']);
      ?>
      <div class="tl-item" style="--tc:<?= $c ?>;animation-delay:<?= min($i * .04, .4) ?>s;">
        <div class="tl-row">
          <?= accion_badge($row['accion']) ?>
          <span class="tl-user"><?= htmlspecialchars($row['usuario_nombre']) ?></span>
          <?php if ($row['cotizacion_id']): ?>
            <a class="tl-cot" href="ver_cotizacion.php?id=<?= $row['cotizacion_id'] ?>"><?= htmlspecialchars($row['numero_cotizacion']) ?></a>
          <?php endif; ?>
          <span class="tl-meta">
            <span class="tl-ip"><?= htmlspecialchars($row['ip']) ?></span>
            <span><?= date('d/m/Y H:i:s', strtotime($row['created_at'])) ?></span>
          </span>
        </div>
        <?php if ($detalle !== ''): ?>
        <div class="tl-detail"><?= htmlspecialchars($detalle) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty">Sin movimientos registrados aún.</div>
    <?php endif; ?>
    <?php if ($ctrl->totalPagAud > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $ctrl->totalPagAud; $p++): ?>
      <a href="<?= htmlspecialchars($ctrl->urlPagina($p)) ?>" class="<?= $p === $ctrl->pagina ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

  </main>
</div>

<script>
function toggleSidenav() { document.getElementById('sidenav').classList.toggle('open'); }
document.addEventListener('click', function(e) {
  const nav = document.getElementById('sidenav');
  const hamb = document.querySelector('.hamburger');
  if (nav && hamb && !nav.contains(e.target) && !hamb.contains(e.target)) nav.classList.remove('open');
});
function toggleUserMenu(){ document.getElementById('user-menu').classList.toggle('open'); }
document.addEventListener('click', e => {
  const t = document.getElementById('user-trigger');
  if (t && !t.contains(e.target)) document.getElementById('user-menu')?.classList.remove('open');
});
fetch('API/api_tipo_cambio.php').then(r => r.json()).then(d => {
  const v = d.MXN || d.mxn || null;
  if (v) document.getElementById('tc-val').textContent = parseFloat(v).toFixed(2);
}).catch(() => { document.getElementById('tc-val').textContent = 'N/D'; });
</script>
</body>
</html>