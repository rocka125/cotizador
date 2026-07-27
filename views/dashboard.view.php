<?php
/**
 * dashboard.view.php — Vista del dashboard. Tema "Ember Glass" (bento grid + glassmorphism).
 * Todos los widgets conectados a datos reales del controlador.
 * Requiere DashboardController actualizado (con calendarioMes, barrasSemana, waveData).
 *
 * CAMBIO: el widget "Evolución de cotizaciones" arranca en una fecha FIJA
 * (1 de julio del año en curso) y muestra TRES series por día: pendientes,
 * aprobadas y rechazadas. Se eliminó el donut de "Conversión" (quedan 3 donuts).
 *
 * El controlador debe entregar, dentro de $ctrl->waveData (ver getWaveData()
 * del DashboardModel actualizado):
 *   - 'labels'     => etiqueta de cada día desde el 1 de julio hasta hoy
 *   - 'pendientes' => conteo diario de cotizaciones pendientes
 *   - 'aprobadas'  => conteo diario de cotizaciones aprobadas
 *   - 'rechazadas' => conteo diario de cotizaciones rechazadas
 * Si el controlador aún no los entrega con el largo correcto, la vista genera
 * etiquetas de fecha automáticamente y usa ceros para los conteos (no truena,
 * pero no muestra datos reales).
 */

// ── Helpers de display ────────────────────────────────────────────────────
function clienteDisplay(array $c): string {
    $empresa  = trim($c['empresa']  ?? '');
    $atencion = trim($c['atencion'] ?? '');
    if ($empresa)  return $empresa;
    if ($atencion) return $atencion;
    return trim($c['cliente_nombre'] ?? $c['nombre'] ?? '—');
}
function estadoClass(string $estado): string {
    $e = normalizar_estado($estado);
    if ($e === 'aprobada')  return 'green';
    if ($e === 'rechazada') return 'red';
    return 'amber';
}
function iniciales(string $nombre): string {
    $nombre = trim($nombre);
    if ($nombre === '' || $nombre === '—') return '—';
    $partes = preg_split('/\s+/', $nombre);
    $ini = strtoupper(mb_substr($partes[0], 0, 1));
    if (count($partes) > 1) $ini .= strtoupper(mb_substr(end($partes), 0, 1));
    return $ini;
}
function diasRestantes(?string $fecha, $vigenciaDias): ?int {
    if (!$fecha || !$vigenciaDias) return null;
    $vence = (new DateTime($fecha))->modify("+{$vigenciaDias} days");
    $diff  = (new DateTime())->diff($vence);
    return $diff->invert ? -$diff->days : $diff->days;
}

// ── Porcentajes para donuts ───────────────────────────────────────────────
$_total  = max($ctrl->stats['total'], 1);
$_pctAp  = round(($ctrl->estadosCount['aprobada']  ?? 0) / $_total * 100);
$_pctPe  = round(($ctrl->estadosCount['pendiente'] ?? 0) / $_total * 100);
$_pctRe  = round(($ctrl->estadosCount['rechazada'] ?? 0) / $_total * 100);
$_circ   = 125.66; // 2π×20

function dasharray(int $pct, float $circ): string {
    $fill = round($pct / 100 * $circ, 1);
    return "{$fill} " . round($circ - $fill, 1);
}

// ── Evolución: desde el 1 de julio hasta hoy, por estado ──────────────────
$_wave        = $ctrl->waveData;
$_inicioJulio = new DateTime(date('Y') . '-07-01');
$_hoyDT       = new DateTime('today');
if ($_hoyDT < $_inicioJulio) {
    $_inicioJulio->modify('-1 year');
}
$_NDIAS = max(1, (int)$_inicioJulio->diff($_hoyDT)->days + 1); // inclusive de hoy

// Etiquetas: usar las del controlador si vienen con el largo correcto;
// si no, generarlas automáticamente como resguardo (fechas "1 Jul", "2 Jul"...).
$_labels = $_wave['labels'] ?? [];
if (count($_labels) !== $_NDIAS) {
    $_labels = [];
    for ($i = 0; $i < $_NDIAS; $i++) {
        $_labels[] = (clone $_inicioJulio)->modify("+{$i} days")->format('j M');
    }
}

$_evPend  = $_wave['pendientes'] ?? array_fill(0, $_NDIAS, 0);
$_evAprob = $_wave['aprobadas']  ?? array_fill(0, $_NDIAS, 0);
$_evRech  = $_wave['rechazadas'] ?? array_fill(0, $_NDIAS, 0);
$_evPend  = array_pad(array_slice($_evPend,  0, $_NDIAS), $_NDIAS, 0);
$_evAprob = array_pad(array_slice($_evAprob, 0, $_NDIAS), $_NDIAS, 0);
$_evRech  = array_pad(array_slice($_evRech,  0, $_NDIAS), $_NDIAS, 0);

$_rangoActual = $_inicioJulio->format('d M') . ' – ' . $_hoyDT->format('d M');

// ── Calendario del mes ────────────────────────────────────────────────────
$_calMapa  = $ctrl->calendarioMes;
$_maxCal   = $_calMapa ? max(max($_calMapa), 1) : 1;
$_hoy      = (int)(new DateTime())->format('j');
$_diaSem   = (int)(new DateTime('first day of this month'))->format('N') - 1;
$_diasMes  = (int)(new DateTime('last day of this month'))->format('j');
$_mesPrevD = (int)(new DateTime('last day of last month'))->format('j');

// ── Top productos para pirámide ───────────────────────────────────────────
$_topProds = $ctrl->topProductos;
$_maxTop   = $_topProds ? max(array_column($_topProds, 'count')) : 1;

// ── Seguimiento: datos para widget de barras ──────────────────────────────
$_seg            = $ctrl->resumenSeguimiento;
$_segActivas     = max((int)($_seg['total_activas']   ?? 0), 1);
$_segEnviadas    = (int)($_seg['enviadas']            ?? 0);
$_segAbiertos    = (int)($_seg['abiertos']            ?? 0);
$_segConSeg      = (int)($_seg['con_seguimiento']     ?? 0);
$_segSinContacto = (int)($_seg['sin_contacto']        ?? 0);
$_segUrgentes    = (int)($_seg['urgentes']            ?? 0);
$_segCerradas    = (int)($_seg['cerradas_mes']        ?? 0);
$_pctEnviadas    = min(round($_segEnviadas    / $_segActivas * 100), 100);
$_pctAbiertos    = min(round($_segAbiertos    / $_segActivas * 100), 100);
$_pctConSeg      = min(round($_segConSeg      / $_segActivas * 100), 100);
$_pctSinContacto = min(round($_segSinContacto / $_segActivas * 100), 100);
$_tasaApertura   = $_segEnviadas > 0 ? min(round($_segAbiertos / $_segEnviadas * 100), 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Fortress8 | Dashboard</title>
<link rel="icon" href="assets/img/favicon.png" type="image/png">
<link rel="manifest" href="/cotizador/manifest.json">
<meta name="theme-color" content="#e63946">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Cotizador">
<link rel="apple-touch-icon" href="/cotizador/assets/icons/icon-192.png">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0B0708;
  --glass:rgba(255,255,255,.035);
  --glass2:rgba(255,255,255,.05);
  --glass-brd:rgba(255,255,255,.09);
  --or:#FF8A3D;
  --or-deep:#C9500F;
  --amber:#FFC876;
  --ink:#F7EFE6;
  --ink2:#B7A99A;
  --ink3:#6E6255;
  --green:#8FE3A6;
  --red:#FF7A6E;
  --blue:#5AA3FF;
}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes glowPulse{0%,100%{text-shadow:0 0 8px currentColor;}50%{text-shadow:0 0 20px currentColor;}}
@keyframes badgePop{0%{transform:scale(0);}60%{transform:scale(1.2);}100%{transform:scale(1);}}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-14px);}to{opacity:1;transform:translateX(0);}}

body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--ink);height:100vh;height:100dvh;overflow:hidden;position:relative;}
/* difuminados orgánicos (ambos fijos, detrás de todo) */
.blob{position:fixed;border-radius:50%;filter:blur(90px);pointer-events:none;z-index:0;animation:fadeIn 1.2s ease both;}
.blob1{width:520px;height:520px;background:radial-gradient(circle,var(--or) 0%,transparent 70%);opacity:.30;top:-180px;left:-140px;}
.blob2{width:460px;height:460px;background:radial-gradient(circle,var(--or-deep) 0%,transparent 70%);opacity:.26;bottom:-160px;right:-100px;}
.blob3{width:300px;height:300px;background:radial-gradient(circle,var(--amber) 0%,transparent 70%);opacity:.12;top:45%;left:50%;}

.shell{display:grid;grid-template-columns:56px 1fr;grid-template-rows:54px 1fr;height:100vh;height:100dvh;position:relative;z-index:1;}

/* TOPBAR */
.topbar{grid-column:1/-1;background:rgba(11,7,8,.75);backdrop-filter:blur(16px);
  border-bottom:1px solid var(--glass-brd);display:flex;align-items:center;gap:10px;padding:0 16px;z-index:20;
  animation:fadeIn .35s ease both;}
.tb-logo{width:32px;height:32px;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
.tb-logo img{width:100%;height:100%;object-fit:contain;}
.tb-name{font-family:'Fraunces',serif;font-size:15px;font-weight:600;color:var(--ink);flex-shrink:0;letter-spacing:.2px;}
.tb-name span{font-style:italic;color:var(--or);font-weight:500;}
.tb-pill{font-size:11px;color:var(--ink2);background:var(--glass);border:1px solid var(--glass-brd);
  border-radius:20px;padding:5px 12px;display:flex;align-items:center;gap:5px;backdrop-filter:blur(10px);}
.tb-pill b{color:var(--green);font-weight:500;}
.tb-actions{display:flex;align-items:center;gap:8px;margin-left:auto;}
.tb-icon{width:32px;height:32px;border-radius:10px;background:var(--glass);border:1px solid var(--glass-brd);
  display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;
  transition:background .2s,border-color .2s,transform .15s;}
.tb-icon:hover{background:var(--glass2);border-color:rgba(255,138,61,.3);transform:translateY(-1px);}
.tb-icon i{font-size:15px;color:var(--ink2);}
.tb-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--amber),var(--or));
  display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#1a0d04;
  cursor:pointer;position:relative;flex-shrink:0;transition:box-shadow .2s;}
.tb-avatar:hover{box-shadow:0 0 0 3px rgba(255,138,61,.25);}
.hamburger{display:none;background:none;border:none;color:var(--ink);font-size:20px;cursor:pointer;padding:4px;}
.push-btn{background:var(--glass);border:1px solid var(--glass-brd);color:var(--ink2);
  border-radius:20px;padding:6px 12px;font-size:11px;cursor:pointer;white-space:nowrap;font-family:inherit;
  transition:border-color .2s,color .2s;}
.push-btn:hover{border-color:rgba(255,138,61,.4);color:var(--or);}

/* NOTIF MENU */
.notif-menu{position:absolute;top:42px;right:0;background:#15100e;border:1px solid var(--glass-brd);
  border-radius:14px;min-width:270px;max-height:340px;overflow-y:auto;z-index:200;
  opacity:0;transform:scale(.95) translateY(-8px);transform-origin:top right;pointer-events:none;
  transition:opacity .2s,transform .22s;box-shadow:0 20px 50px rgba(0,0,0,.6);}
.notif-menu.open{opacity:1;transform:scale(1) translateY(0);pointer-events:auto;}
.notif-menu-header{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;
  font-size:12px;font-weight:500;border-bottom:1px solid var(--glass-brd);position:sticky;top:0;background:#15100e;}
.notif-mark-all{font-size:11px;color:var(--or);background:none;border:none;cursor:pointer;font-family:inherit;}
.notif-item{display:block;padding:10px 14px;font-size:12px;color:var(--ink2);text-decoration:none;
  border-top:1px solid var(--glass-brd);line-height:1.4;transition:background .15s;}
.notif-item:hover{background:rgba(255,255,255,.04);}
.notif-item.unread{background:rgba(255,138,61,.07);border-left:2px solid var(--or);}
.notif-dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--or);margin-right:5px;vertical-align:middle;
  animation:glowPulse 2s ease-in-out infinite;}
.notif-time{display:block;font-size:10px;color:var(--ink3);margin-top:2px;}
.notif-empty{padding:20px 14px;text-align:center;font-size:12px;color:var(--ink3);}
.notif-badge{position:absolute;top:-3px;right:-3px;min-width:16px;height:16px;padding:0 4px;
  border-radius:10px;background:var(--red);color:#2a0805;font-size:9px;font-weight:700;
  display:flex;align-items:center;justify-content:center;animation:badgePop .3s ease both;}

/* USER MENU */
.user-menu{position:absolute;top:42px;right:0;background:#15100e;border:1px solid var(--glass-brd);
  border-radius:14px;min-width:170px;z-index:200;display:none;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.6);}
.user-menu.open{display:block;animation:fadeUp .18s ease both;}
.user-menu a{display:block;padding:9px 14px;font-size:12px;color:var(--ink2);text-decoration:none;transition:background .15s,color .15s;}
.user-menu a:hover{background:rgba(255,255,255,.05);color:var(--ink);}
.user-menu .sep{height:1px;background:var(--glass-brd);}
.user-name{padding:10px 14px 7px;font-size:12px;font-weight:500;color:var(--ink);}

/* SIDEBAR — MODIFICADO: fondo negro, íconos más grandes y centrados */
.sidebar{
  background: #0B0708; /* fondo negro sólido */
  border-right: 1px solid var(--glass-brd);
  padding: 16px 0;     /* más aire arriba/abajo */
  display:flex;
  flex-direction:column;
  align-items:center;
  gap: 8px;            /* más separación entre íconos */
  overflow:hidden;
  animation:slideInLeft .4s ease both;
  box-shadow: 4px 0 20px rgba(0,0,0,.4); /* sombra para profundidad */
}
.si{
  width: 44px;          /* más grande */
  height: 44px;
  border-radius: 12px;
  display:flex;
  align-items:center;
  justify-content:center; /* centrado perfecto */
  cursor:pointer;
  text-decoration:none;
  transition:background .18s,transform .15s;
}
.si i{
  font-size: 22px;      /* más grande */
  color:var(--ink3);
  transition:color .18s;
  line-height: 1;       /* evita desbordes */
}
.si.on{background:linear-gradient(135deg,rgba(255,138,61,.22),rgba(201,80,15,.14));box-shadow:0 0 16px rgba(255,138,61,.18);}
.si.on i{color:var(--or);}
.si:hover:not(.on){background:rgba(255,255,255,.06);transform:scale(1.08);}
.si:hover:not(.on) i{color:var(--ink2);}
.si-sep{
  height: 1px;
  width: 32px;          /* proporcionado */
  background:var(--glass-brd);
  margin: 4px 0;
}

/* MAIN */
.main-scroll{overflow-y:auto;padding:22px 26px 40px;background:transparent;position:relative;z-index:1;}
.main-scroll::-webkit-scrollbar{width:5px;}
.main-scroll::-webkit-scrollbar-track{background:transparent;}
.main-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:3px;}

/* HEADER */
.hdr{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:14px;
  animation:fadeUp .35s .05s ease both;}
.hdr-title{font-family:'Fraunces',serif;font-size:28px;font-weight:600;letter-spacing:-.4px;}
.hdr-title em{font-style:italic;color:var(--or);font-weight:500;}
.hdr-sub{font-size:12.5px;color:var(--ink2);margin-top:5px;}
.btn-new{background:linear-gradient(135deg,var(--or),var(--or-deep));color:#1a0d04;font-weight:600;
  border:none;border-radius:100px;padding:10px 20px;font-size:13px;cursor:pointer;text-decoration:none;
  display:inline-flex;align-items:center;gap:7px;box-shadow:0 8px 26px rgba(255,138,61,.28);
  transition:transform .18s,box-shadow .18s;}
.btn-new:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(255,138,61,.4);}

/* GLASS base */
.glass{background:var(--glass);backdrop-filter:blur(20px);border:1px solid var(--glass-brd);
  border-radius:24px;padding:20px;transition:border-color .2s,transform .2s,box-shadow .2s;}
.glass:hover{border-color:rgba(255,138,61,.18);}

/* BENTO GRID */
.bento{display:grid;grid-template-columns:repeat(12,1fr);grid-auto-rows:minmax(88px,auto);gap:16px;}
.b-hero{grid-column:span 5;grid-row:span 2;}
.b-chart{grid-column:span 7;grid-row:span 2;}
.b-kpi{grid-column:span 3;}
.b-clients{grid-column:span 5;grid-row:span 2;}
.b-follow{grid-column:span 4;grid-row:span 2;}
.b-cal{grid-column:span 3;grid-row:span 2;}
.b-estados{grid-column:span 5;}
.b-products{grid-column:span 7;}
@media(max-width:1100px){
  .bento{grid-template-columns:1fr;}
  .b-hero,.b-chart,.b-kpi,.b-clients,.b-follow,.b-cal,.b-estados,.b-products{grid-column:span 1;grid-row:span 1;}
}

.bento .glass:nth-child(1){animation:fadeUp .4s .08s ease both;}
.bento .glass:nth-child(2){animation:fadeUp .4s .13s ease both;}
.bento .glass:nth-child(3){animation:fadeUp .4s .18s ease both;}
.bento .glass:nth-child(4){animation:fadeUp .4s .22s ease both;}
.bento .glass:nth-child(5){animation:fadeUp .4s .26s ease both;}
.bento .glass:nth-child(6){animation:fadeUp .4s .30s ease both;}

/* HERO */
.b-hero{display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;}
.b-hero .eyebrow{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--ink2);}
.hero-num{font-family:'Fraunces',serif;font-size:52px;font-weight:600;line-height:1;margin:12px 0;
  background:linear-gradient(135deg,var(--amber),var(--or));-webkit-background-clip:text;background-clip:text;color:transparent;}
.hero-foot{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--ink2);gap:8px;flex-wrap:wrap;}
.hero-foot b{color:var(--green);}
.hero-ring{position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;
  border:1px solid rgba(255,138,61,.22);pointer-events:none;}
.hero-ring::before{content:'';position:absolute;inset:24px;border-radius:50%;border:1px solid rgba(255,138,61,.15);}

/* CHART */
.p-hd{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:12px;flex-wrap:wrap;gap:6px;}
.p-title{font-family:'Fraunces',serif;font-size:15.5px;font-weight:600;}
.p-tag{font-size:11px;color:var(--ink3);}
.wave-legend{display:flex;gap:16px;margin-bottom:12px;font-size:11px;color:var(--ink2);}
.wave-legend span{display:flex;align-items:center;gap:6px;}
.dotL{width:8px;height:8px;border-radius:50%;display:inline-block;}

/* DONUTS */
.donut-row{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;}
.donut-wrap{display:flex;flex-direction:column;align-items:center;gap:4px;}
.donut-pct{font-size:13px;font-weight:700;}
.donut-lbl{font-size:9.5px;color:var(--ink3);text-align:center;}
.donut-wrap circle.ring{transition:stroke-dasharray .9s cubic-bezier(.22,1,.36,1);}
.donut-wrap:hover .donut-pct{animation:glowPulse 1s ease-in-out 1;}

/* KPI mini */
.kpi-mini{display:flex;flex-direction:column;justify-content:center;gap:6px;}
.kpi-mini .n{font-family:'Fraunces',serif;font-size:27px;font-weight:600;}
.kpi-mini .l{font-size:11px;color:var(--ink2);}
a.glass.kpi-mini{text-decoration:none;color:inherit;display:flex;cursor:pointer;}

/* CLIENTS list */
.client-row{display:flex;align-items:center;gap:11px;padding:9px 0;border-bottom:1px solid var(--glass-brd);flex-wrap:wrap;}
.client-row:last-child{border:none;}
.avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:700;color:#1a0d04;flex-shrink:0;background:linear-gradient(135deg,var(--amber),var(--or));}
.client-info{flex:1;min-width:0;}
.client-info b{display:block;font-size:12.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.client-info span{font-size:10.5px;color:var(--ink3);}
.client-amt{font-size:12.5px;font-weight:600;text-align:right;white-space:nowrap;}
.client-tag{font-size:9px;padding:3px 8px;border-radius:100px;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap;}
.client-tag.green{background:rgba(143,227,166,.14);color:var(--green);}
.client-tag.amber{background:rgba(255,138,61,.14);color:var(--or);}
.client-tag.red{background:rgba(255,122,110,.14);color:var(--red);}
.client-actions{display:flex;gap:5px;width:100%;margin-top:6px;flex-wrap:wrap;}
.mini-icon-btn{background:var(--glass2);border:1px solid var(--glass-brd);color:var(--ink2);border-radius:7px;
  padding:4px 8px;font-size:10px;cursor:pointer;text-decoration:none;transition:.16s;font-family:inherit;}
.mini-icon-btn:hover{border-color:rgba(255,138,61,.35);color:var(--or);transform:translateY(-1px);}
.mini-icon-btn:active{transform:translateY(0) scale(.94);}
.mini-icon-btn.danger:hover{border-color:rgba(255,122,110,.4);color:var(--red);}
.mini-icon-btn.email:hover{border-color:rgba(90,163,255,.4);color:var(--blue);}
.client-search{width:100%;background:var(--glass2);border:1px solid var(--glass-brd);border-radius:10px;
  padding:8px 12px;font-size:12px;color:var(--ink);outline:none;margin-bottom:10px;transition:border-color .2s;}
.client-search::placeholder{color:var(--ink3);}
.client-search:focus{border-color:rgba(255,138,61,.4);}
.clients-scroll{max-height:300px;overflow-y:auto;padding-right:2px;}
.clients-scroll::-webkit-scrollbar{width:4px;}
.clients-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:2px;}

/* FOLLOW (hbar) */
.hbar-list{display:flex;flex-direction:column;gap:9px;}
.hbar-row{display:flex;flex-direction:column;gap:4px;}
.hbar-top{display:flex;justify-content:space-between;align-items:baseline;}
.hbar-label{font-size:11px;color:var(--ink2);display:flex;align-items:center;gap:5px;}
.hbar-label i{font-size:12px;}
.hbar-val{font-size:13px;font-weight:600;}
.hbar-track{height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden;}
.hbar-fill{height:100%;border-radius:3px;width:0%;transition:width 1.1s cubic-bezier(.22,1,.36,1);}
.hbar-footer{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;margin-top:13px;padding-top:12px;border-top:1px solid var(--glass-brd);}
.hbar-stat{text-align:center;}
.hbar-stat-n{font-family:'Fraunces',serif;font-size:18px;font-weight:600;line-height:1;}
.hbar-stat-l{font-size:9.5px;color:var(--ink3);margin-top:4px;}
.urgente-pill{display:inline-flex;align-items:center;gap:3px;background:rgba(255,122,110,.14);
  color:var(--red);border:1px solid rgba(255,122,110,.28);border-radius:20px;
  padding:3px 8px;font-size:9.5px;font-weight:600;animation:glowPulse 2.5s ease-in-out infinite;}

/* CALENDAR */
.cal-nav{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.cal-nav span{font-size:11.5px;font-weight:600;color:var(--ink);}
.cal-nav i{font-size:14px;color:var(--ink3);cursor:pointer;transition:color .15s;}
.cal-nav i:hover{color:var(--or);}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;}
.cal-hdr{font-size:9px;color:var(--ink3);text-align:center;padding:2px 0;}
.cal-day{font-size:10px;color:var(--ink2);text-align:center;padding:5px 1px;border-radius:7px;cursor:pointer;position:relative;
  transition:background .15s;background:rgba(255,255,255,.02);}
.cal-day:hover{background:rgba(255,255,255,.07);}
.cal-day.today{background:linear-gradient(135deg,var(--amber),var(--or));color:#1a0d04;font-weight:700;
  box-shadow:0 0 12px rgba(255,138,61,.4);}
.cal-day.dim{color:var(--ink3);background:transparent;}
.cal-day.has-cot{font-weight:600;color:var(--ink);}
.cal-dot{width:4px;height:4px;border-radius:50%;background:var(--or);position:absolute;bottom:2px;left:50%;transform:translateX(-50%);}
.cal-day:hover .cal-tip{display:block;}
.cal-tip{display:none;position:absolute;bottom:calc(100% + 5px);left:50%;transform:translateX(-50%);
  background:#15100e;border:1px solid rgba(255,138,61,.3);border-radius:6px;
  padding:4px 9px;font-size:10px;color:var(--ink);white-space:nowrap;z-index:20;pointer-events:none;
  box-shadow:0 4px 16px rgba(0,0,0,.5);}

/* ESTADOS (tarjetas independientes) */
.estados-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;}
.estado-item{
  background:var(--glass2);border:1px solid var(--glass-brd);border-radius:16px;
  padding:14px;display:flex;flex-direction:column;gap:10px;min-width:0;
  transition:transform .15s,border-color .2s;
}
.estado-item:hover{transform:translateY(-2px);border-color:rgba(255,138,61,.2);}
.estado-top{display:flex;align-items:center;gap:9px;min-width:0;}
.estado-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.estado-icon i{font-size:15px;}
.estado-icon.amber{background:rgba(255,138,61,.15);} .estado-icon.amber i{color:var(--or);}
.estado-icon.green{background:rgba(143,227,166,.14);} .estado-icon.green i{color:var(--green);}
.estado-icon.red{background:rgba(255,122,110,.14);} .estado-icon.red i{color:var(--red);}
.estado-txt{min-width:0;}
.estado-n{font-family:'Fraunces',serif;font-size:19px;font-weight:600;line-height:1.15;}
.estado-n.amber{color:var(--or);} .estado-n.green{color:var(--green);} .estado-n.red{color:var(--red);}
.estado-label{font-size:10px;color:var(--ink3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.estado-sum{
  font-size:13px;font-weight:600;color:var(--ink2);
  padding-top:9px;border-top:1px solid var(--glass-brd);
  display:flex;align-items:baseline;justify-content:space-between;gap:6px;
  white-space:nowrap;overflow:hidden;
}
.estado-sum .val{overflow:hidden;text-overflow:ellipsis;}
.estado-sum span{font-size:9px;color:var(--ink3);font-weight:400;flex-shrink:0;}
@media(max-width:520px){
  .estados-row{grid-template-columns:1fr;}
}

/* TOP PRODUCTOS pirámide */
.pyramid{display:flex;flex-direction:column;gap:6px;padding:2px 0;}
.py-row{display:flex;align-items:center;justify-content:space-between;padding:0 11px;
  height:26px;border-radius:8px;font-size:10.5px;font-weight:600;color:#1a0d04;
  transition:width .7s cubic-bezier(.22,1,.36,1),filter .2s;}
.py-row:hover{filter:brightness(1.1);}
.py-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:70%;}
.py-count{opacity:.85;flex-shrink:0;}

.empty,.widget-empty{padding:18px;font-size:12px;color:var(--ink3);text-align:center;}

/* MODAL */
.modal-backdrop{position:fixed;inset:0;background:rgba(5,3,2,.7);z-index:100;
  display:none;align-items:center;justify-content:center;backdrop-filter:blur(6px);}
.modal-backdrop.open{display:flex;}
.modal{background:#15100e;border:1px solid var(--glass-brd);border-radius:20px;
  padding:24px 26px;max-width:380px;width:90%;box-shadow:0 30px 70px rgba(0,0,0,.6);
  opacity:0;transform:translateY(16px) scale(.96);animation:popIn .3s cubic-bezier(.22,.9,.32,1.15) forwards;}
@keyframes popIn{to{opacity:1;transform:translateY(0) scale(1);}}
.modal-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;
  font-size:19px;margin-bottom:12px;background:rgba(255,122,110,.14);color:var(--red);}
.modal-title{font-family:'Fraunces',serif;font-size:18px;font-weight:600;margin-bottom:9px;color:var(--ink);}
.modal-body{font-size:12.5px;color:var(--ink2);line-height:1.65;margin-bottom:18px;}
.modal-body strong{color:var(--ink);}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;}
.modal-cancel{background:var(--glass2);border:1px solid var(--glass-brd);border-radius:10px;
  padding:9px 16px;font-size:12.5px;color:var(--ink2);cursor:pointer;font-family:inherit;transition:.16s;}
.modal-cancel:hover{background:rgba(255,255,255,.08);}
.modal-confirm{background:linear-gradient(135deg,var(--red),#c9432f);border:none;border-radius:10px;
  padding:9px 18px;font-size:12.5px;font-weight:600;color:#2a0805;cursor:pointer;font-family:inherit;transition:box-shadow .2s;}
.modal-confirm:hover{box-shadow:0 0 18px rgba(255,122,110,.45);}

/* TOAST */
.toast{position:fixed;bottom:22px;right:22px;background:#15100e;border:1px solid var(--glass-brd);
  border-left:3px solid var(--or);border-radius:12px;padding:12px 17px;font-size:13px;
  color:var(--ink);z-index:200;display:none;box-shadow:0 10px 28px rgba(0,0,0,.5);}
.toast.show{display:block;animation:fadeUp .25s ease both;}

@media(max-width:768px){
  .shell{grid-template-columns:1fr;}
  .sidebar{display:none;position:fixed;top:0;left:0;bottom:0;width:56px;z-index:210;}
  .sidebar.open{display:flex;box-shadow:8px 0 24px rgba(0,0,0,.45);}
  .hamburger{display:block;}
  .main-scroll{padding:14px;}
  .topbar{padding:0 12px;gap:6px;}
  .tb-name{font-size:13px;}
  .tb-pill{display:none;}
  .push-btn{padding:6px 9px;font-size:0;}
  .push-btn::before{content:'🔔';font-size:14px;}
  .hdr-title{font-size:22px;}
}
@media(max-width:400px){
  .tb-name{display:none;}
}

</style>
</head>
<body>
<div class="blob blob1"></div>
<div class="blob blob2"></div>
<div class="blob blob3"></div>

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

  <!-- TOPBAR -->
  <header class="topbar">
    <button class="hamburger" onclick="toggleSidenav()">☰</button>
    <div class="tb-logo"><img src="assets/img/logoss.png" alt="Fortress8"></div>
    <div class="tb-name">FORTRESS<span>8</span></div>
    <div class="tb-pill">
      <span style="color:var(--ink3)">USD/MXN</span>
      <b id="tc-val">—</b>
    </div>
    <div class="tb-actions">
      <button class="tb-icon" id="btn-instalar-app" onclick="instalarApp()" title="Instalar app" style="display:none;">
        <i class="ti ti-download" aria-hidden="true"></i>
      </button>
      <button class="push-btn" id="push-toggle-btn"
        onclick="window.pushClient?.toggleSubscription()" data-state="inactive">🔔 Push</button>

      <!-- Notificaciones -->
      <div class="tb-icon" onclick="toggleNotifMenu()" id="notif-trigger">
        <i class="ti ti-bell" aria-hidden="true"></i>
        <?php if ($ctrl->notifNoLeidas > 0): ?>
          <span class="notif-badge" id="notif-badge">
            <?= $ctrl->notifNoLeidas > 9 ? '9+' : $ctrl->notifNoLeidas ?>
          </span>
        <?php endif; ?>
        <div class="notif-menu" id="notif-menu">
          <div class="notif-menu-header">
            <span>Notificaciones</span>
            <button class="notif-mark-all" onclick="marcarTodasLeidas(event)">Marcar todas</button>
          </div>
          <?php if (empty($ctrl->notificaciones)): ?>
            <div class="notif-empty">Sin notificaciones 🎉</div>
          <?php else: ?>
            <?php foreach ($ctrl->notificaciones as $n):
              $href   = $n['cotizacion_id'] ? "cotizaciones.php?action=editar&id={$n['cotizacion_id']}" : '#';
              $fechaN = (new DateTime($n['created_at']))->format('d/m/Y H:i');
            ?>
            <a href="<?= htmlspecialchars($href) ?>"
               class="notif-item <?= $n['leido'] ? '' : 'unread' ?>"
               data-id="<?= $n['id'] ?>"
               onclick="marcarLeida(<?= $n['id'] ?>)">
              <?php if (!$n['leido']): ?><span class="notif-dot"></span><?php endif; ?>
              <?= htmlspecialchars($n['mensaje']) ?>
              <span class="notif-time"><?= htmlspecialchars($fechaN) ?></span>
            </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Avatar -->
      <div class="tb-avatar" onclick="toggleUserMenu()" id="user-trigger">
        <?= $auth->iniciales() ?>
        <div class="user-menu" id="user-menu">
          <div class="user-name">
            <?= htmlspecialchars($auth->usuarioNombre()) ?>
            (<?= $auth->esAdmin() ? 'Admin' : 'Vendedor' ?>)
          </div>
          <div class="sep"></div>
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

  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidenav">
    <a class="si on" href="dashboard.php" title="Dashboard">
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

  <!-- MAIN -->
  <main class="main-scroll">

    <div class="hdr">
      <div>
        <div class="hdr-title">Bienvenido, <em><?= htmlspecialchars($auth->usuarioNombre()) ?></em></div>
        <div class="hdr-sub">
          <?= (new DateTime())->format('d \d\e F \d\e Y') ?>
          <?= $auth->esAdmin() ? ' · Vista global' : '' ?>
        </div>
      </div>
    </div>

    <div class="bento">

      <!-- HERO: total cotizado -->
      <div class="glass b-hero">
        <div class="hero-ring"></div>
        <div>
          <div class="eyebrow">Total cotizado</div>
          <div class="hero-num">$<?= number_format($ctrl->totalUsd, 0) ?></div>
        </div>
        <div class="hero-foot">
          <span><?= $ctrl->totalMxn > 0 ? '+ $'.number_format($ctrl->totalMxn,0).' MXN adicional' : 'Solo cotizaciones en USD' ?></span>
          <span><b>↑ <?= $ctrl->cotizacionesMes ?></b> nuevas este mes</span>
        </div>
      </div>

      <!-- CHART: pendientes / aprobadas / rechazadas desde el 1 de julio + donuts -->
      <div class="glass b-chart" style="position:relative;">
        <div class="p-hd">
          <div class="p-title">Evolución de cotizaciones</div>
          <div class="p-tag"><?= htmlspecialchars($_rangoActual) ?></div>
        </div>

        <div style="position:relative;height:190px;">
          <canvas id="compareChart"></canvas>
        </div>

        <div class="wave-legend">
          <span><i class="dotL" style="background:var(--amber);"></i>Pendientes</span>
          <span><i class="dotL" style="background:var(--green);"></i>Aprobadas</span>
          <span><i class="dotL" style="background:var(--red);"></i>Rechazadas</span>
        </div>

        <div class="donut-row" style="grid-template-columns:repeat(3,1fr);">
          <?php
            $donuts = [
              ['pct' => $_pctPe, 'color' => '#FFC876', 'label' => 'Pendientes'],
              ['pct' => $_pctAp, 'color' => '#8FE3A6', 'label' => 'Aprobadas'],
              ['pct' => $_pctRe, 'color' => '#FF7A6E', 'label' => 'Rechazadas'],
            ];
            foreach ($donuts as $dn):
          ?>
          <div class="donut-wrap">
            <svg width="46" height="46" viewBox="0 0 52 52">
              <circle cx="26" cy="26" r="20" fill="none" stroke="rgba(255,255,255,.07)" stroke-width="7"/>
              <circle cx="26" cy="26" r="20" fill="none"
                stroke="<?= $dn['color'] ?>" stroke-width="7"
                stroke-dasharray="0 <?= $_circ ?>"
                stroke-linecap="round"
                transform="rotate(-90 26 26)"
                class="ring"
                data-full="<?= dasharray($dn['pct'], $_circ) ?>"/>
            </svg>
            <div class="donut-pct" style="color:<?= $dn['color'] ?>;"><?= $dn['pct'] ?>%</div>
            <div class="donut-lbl"><?= $dn['label'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- KPI minis -->
      <div class="glass b-kpi kpi-mini">
        <div class="n" style="color:var(--or);"><?= number_format($ctrl->stats['total']) ?></div>
        <div class="l">Cotizaciones totales</div>
      </div>
      <div class="glass b-kpi kpi-mini">
        <div class="n" style="color:var(--green);"><?= $ctrl->tasaConversion ?>%</div>
        <div class="l">Tasa de aprobación</div>
      </div>
      <div class="glass b-kpi kpi-mini">
        <div class="n" style="color:var(--amber);"><?= $ctrl->cotizacionesMes ?></div>
        <div class="l">Nuevas este mes</div>
      </div>
      <a href="seguimiento.php" class="glass b-kpi kpi-mini">
        <div class="n" style="color:var(--red);"><?= $_segSinContacto ?></div>
        <div class="l">Sin respuesta +5 días</div>
      </a>

      <!-- CLIENTES / cotizaciones recientes -->
      <div class="glass b-clients">
        <div class="p-hd">
          <div class="p-title">Cotizaciones recientes</div>
          <div class="p-tag"><a href="lista_cotizaciones.php" style="color:var(--or);text-decoration:none;">Ver todas →</a></div>
        </div>
        <input type="text" id="searchTable" class="client-search" placeholder="Buscar cliente o número...">
        <?php if (empty($ctrl->cotizaciones)): ?>
          <div class="empty">Sin cotizaciones. <a href="cotizaciones.php?action=nueva" style="color:var(--or)">Crear la primera →</a></div>
        <?php else: ?>
        <div class="clients-scroll" id="cot-tbody">
          <?php foreach ($ctrl->cotizaciones as $c):
            $cliente  = clienteDisplay($c);
            $badgeCls = estadoClass($c['estado'] ?? '');
            $estadoL  = estado_label($c['estado'] ?? '');
            $fecha    = $c['fecha'] ?? substr($c['created_at'] ?? '', 0, 10);
          ?>
          <div class="client-row tbl-row" id="row-<?= $c['id'] ?>">
            <div class="avatar"><?= htmlspecialchars(iniciales($cliente)) ?></div>
            <div class="client-info">
              <b title="<?= htmlspecialchars($cliente) ?>"><?= htmlspecialchars($cliente) ?></b>
              <span><?= htmlspecialchars($c['numero_cotizacion'] ?? '—') ?> · <?= htmlspecialchars($fecha) ?></span>
            </div>
            <span class="client-tag <?= $badgeCls ?>"><?= htmlspecialchars($estadoL) ?></span>
            <div class="client-amt">$<?= number_format($c['total'] ?? 0, 0) ?> <span style="color:var(--ink3);font-weight:400;"><?= htmlspecialchars($c['moneda'] ?? 'USD') ?></span></div>
            <div class="client-actions">
              <a href="ver_cotizacion.php?id=<?= $c['id'] ?>" class="mini-icon-btn">Ver</a>
              <a href="cotizaciones.php?action=editar&id=<?= $c['id'] ?>" class="mini-icon-btn">Editar</a>
              <button class="mini-icon-btn email"
                onclick="abrirEmailDash(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['cliente_email'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($c['numero_cotizacion'] ?? '')) ?>')">✉ Correo</button>
              <button class="mini-icon-btn danger"
                onclick="askDelete(<?= $c['id'] ?>,'<?= htmlspecialchars(addslashes($c['numero_cotizacion'] ?? '—')) ?>','<?= htmlspecialchars(addslashes($cliente)) ?>')">Eliminar</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- SEGUIMIENTO -->
      <div class="glass b-follow">
        <div class="p-hd">
          <div class="p-title">Seguimiento</div>
          <?php if ($_segUrgentes > 0): ?>
            <span class="urgente-pill"><i class="ti ti-alert-triangle" aria-hidden="true" style="font-size:10px;"></i> <?= $_segUrgentes ?> urgente<?= $_segUrgentes > 1 ? 's' : '' ?></span>
          <?php else: ?>
            <div class="p-tag"><?= $_segActivas ?> activas</div>
          <?php endif; ?>
        </div>
        <div class="hbar-list">
          <div class="hbar-row">
            <div class="hbar-top">
              <span class="hbar-label" style="color:var(--blue);"><i class="ti ti-mail-check" aria-hidden="true"></i>Enviados</span>
              <span class="hbar-val" style="color:var(--blue);"><?= $_segEnviadas ?></span>
            </div>
            <div class="hbar-track"><div class="hbar-fill" data-width="<?= $_pctEnviadas ?>" style="background:var(--blue);"></div></div>
          </div>
          <div class="hbar-row">
            <div class="hbar-top">
              <span class="hbar-label" style="color:var(--green);"><i class="ti ti-eye" aria-hidden="true"></i>Abiertos <?= $_tasaApertura > 0 ? "({$_tasaApertura}%)" : '' ?></span>
              <span class="hbar-val" style="color:var(--green);"><?= $_segAbiertos ?></span>
            </div>
            <div class="hbar-track"><div class="hbar-fill" data-width="<?= $_pctAbiertos ?>" style="background:var(--green);"></div></div>
          </div>
          <div class="hbar-row">
            <div class="hbar-top">
              <span class="hbar-label" style="color:var(--or);"><i class="ti ti-message-circle" aria-hidden="true"></i>Con seguimiento</span>
              <span class="hbar-val" style="color:var(--or);"><?= $_segConSeg ?></span>
            </div>
            <div class="hbar-track"><div class="hbar-fill" data-width="<?= $_pctConSeg ?>" style="background:var(--or);"></div></div>
          </div>
          <div class="hbar-row">
            <div class="hbar-top">
              <span class="hbar-label" style="color:var(--red);"><i class="ti ti-clock-exclamation" aria-hidden="true"></i>Sin respuesta +5d</span>
              <span class="hbar-val" style="color:var(--red);"><?= $_segSinContacto ?></span>
            </div>
            <div class="hbar-track"><div class="hbar-fill" data-width="<?= $_pctSinContacto ?>" style="background:var(--red);"></div></div>
          </div>
        </div>
        <div class="hbar-footer">
          <div class="hbar-stat"><div class="hbar-stat-n" style="color:var(--ink);"><?= $_segActivas ?></div><div class="hbar-stat-l">Total activas</div></div>
          <div class="hbar-stat"><div class="hbar-stat-n" style="color:var(--green);"><?= $_segCerradas ?></div><div class="hbar-stat-l">Cerradas este mes</div></div>
          <div class="hbar-stat"><div class="hbar-stat-n" style="color:var(--or);"><?= $_tasaApertura ?>%</div><div class="hbar-stat-l">Tasa de apertura</div></div>
        </div>
        <div class="p-tag" style="margin-top:12px;text-align:center;">
          <a href="seguimiento.php" style="color:var(--or);text-decoration:none;">Ver centro de seguimiento →</a>
        </div>
      </div>

      <!-- CALENDARIO -->
      <div class="glass b-cal">
        <div class="cal-nav">
          <i class="ti ti-chevron-left" aria-hidden="true"></i>
          <span><?= (new DateTime())->format('F Y') ?></span>
          <i class="ti ti-chevron-right" aria-hidden="true"></i>
        </div>
        <div class="cal-grid">
          <div class="cal-hdr">L</div><div class="cal-hdr">M</div><div class="cal-hdr">M</div>
          <div class="cal-hdr">J</div><div class="cal-hdr">V</div><div class="cal-hdr">S</div><div class="cal-hdr">D</div>
          <?php for ($i = 0; $i < $_diaSem; $i++): ?>
            <div class="cal-day dim"><?= $_mesPrevD - $_diaSem + $i + 1 ?></div>
          <?php endfor; ?>
          <?php for ($d = 1; $d <= $_diasMes; $d++):
            $n = $_calMapa[$d] ?? 0;
            $isHoy = ($d === $_hoy);
            $hasCot = $n > 0;
            $cls = $isHoy ? 'today' : ($hasCot ? 'has-cot' : '');
          ?>
          <div class="cal-day <?= $cls ?>">
            <?= $d ?>
            <?php if ($hasCot && !$isHoy): ?>
              <span class="cal-dot"></span>
              <span class="cal-tip"><?= $n ?> cotización<?= $n > 1 ? 'es' : '' ?></span>
            <?php endif; ?>
          </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- ESTADOS -->
      <div class="glass b-estados">
        <div class="p-title" style="margin-bottom:12px;">Estados</div>
        <div class="estados-row">
          <div class="estado-item">
            <div class="estado-top">
              <div class="estado-icon amber"><i class="ti ti-clock" aria-hidden="true"></i></div>
              <div class="estado-txt">
                <div class="estado-n amber"><?= $ctrl->estadosCount['pendiente'] ?></div>
                <div class="estado-label">Pendientes</div>
              </div>
            </div>
            <div class="estado-sum">
              <span class="val">$<?= number_format($ctrl->estadosSuma['pendiente'], 0) ?></span>
              <span>USD</span>
            </div>
          </div>
          <div class="estado-item">
            <div class="estado-top">
              <div class="estado-icon green"><i class="ti ti-check" aria-hidden="true"></i></div>
              <div class="estado-txt">
                <div class="estado-n green"><?= $ctrl->estadosCount['aprobada'] ?></div>
                <div class="estado-label">Aprobadas</div>
              </div>
            </div>
            <div class="estado-sum">
              <span class="val">$<?= number_format($ctrl->estadosSuma['aprobada'], 0) ?></span>
              <span>USD</span>
            </div>
          </div>
          <div class="estado-item">
            <div class="estado-top">
              <div class="estado-icon red"><i class="ti ti-x" aria-hidden="true"></i></div>
              <div class="estado-txt">
                <div class="estado-n red"><?= $ctrl->estadosCount['rechazada'] ?></div>
                <div class="estado-label">Rechazadas</div>
              </div>
            </div>
            <div class="estado-sum">
              <span class="val">$<?= number_format($ctrl->estadosSuma['rechazada'], 0) ?></span>
              <span>USD</span>
            </div>
          </div>
        </div>
      </div>

      <!-- TOP PRODUCTOS -->
      <div class="glass b-products">
        <div class="p-title" style="margin-bottom:12px;">Top productos <span style="font-size:11px;color:var(--ink3);font-weight:400;">Más cotizados</span></div>
        <?php if (empty($_topProds)): ?>
          <div class="widget-empty">Sin productos aún</div>
        <?php else: ?>
        <div class="pyramid">
          <?php
            $colores = ['#FF7A6E','#FF8A3D','#FFA85C','#FFC876','rgba(255,200,118,.6)','rgba(255,200,118,.35)'];
            $i = 0;
            foreach ($_topProds as $cat => $info):
              $widthPct = round($info['count'] / $_maxTop * 100);
              $color    = $colores[$i % count($colores)];
          ?>
          <div class="py-row" style="background:<?= $color ?>;width:<?= $widthPct ?>%;"
               title="<?= htmlspecialchars($cat) ?>: <?= $info['count'] ?> uds · $<?= number_format($info['total'],0) ?>">
            <span class="py-label"><?= htmlspecialchars($cat) ?></span>
            <span class="py-count"><?= $info['count'] ?></span>
          </div>
          <?php $i++; endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /bento -->

  </main>
</div><!-- /shell -->

<script src="assets/js/notif-sound.js"></script>
<script src="assets/js/push-client.js" defer></script>
<script>
function toggleSidenav(){ document.getElementById('sidenav').classList.toggle('open'); }
document.addEventListener('click', function(e) {
  const nav = document.getElementById('sidenav');
  const hamb = document.querySelector('.hamburger');
  if (nav && hamb && !nav.contains(e.target) && !hamb.contains(e.target)) nav.classList.remove('open');
});

// Botón "Instalar app" (PWA)
let deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredInstallPrompt = e;
  const btn = document.getElementById('btn-instalar-app');
  if (btn) btn.style.display = 'flex';
});
async function instalarApp() {
  if (!deferredInstallPrompt) return;
  deferredInstallPrompt.prompt();
  await deferredInstallPrompt.userChoice;
  deferredInstallPrompt = null;
  document.getElementById('btn-instalar-app').style.display = 'none';
}
window.addEventListener('appinstalled', () => {
  const btn = document.getElementById('btn-instalar-app');
  if (btn) btn.style.display = 'none';
});

function toggleUserMenu(){ document.getElementById('user-menu').classList.toggle('open'); }
document.addEventListener('click', e => {
  const t = document.getElementById('user-trigger');
  if (t && !t.contains(e.target)) document.getElementById('user-menu')?.classList.remove('open');
});

function toggleNotifMenu(){ document.getElementById('notif-menu').classList.toggle('open'); }
document.addEventListener('click', e => {
  const t = document.getElementById('notif-trigger');
  if (t && !t.contains(e.target)) document.getElementById('notif-menu')?.classList.remove('open');
});
function marcarLeida(id) {
  const fd = new FormData(); fd.append('accion','marcar_leida'); fd.append('id', id);
  fetch('notificaciones.php', {method:'POST', body:fd}).catch(()=>{});
  const item = document.querySelector(`.notif-item[data-id="${id}"]`);
  if (item) { item.classList.remove('unread'); item.querySelector('.notif-dot')?.remove(); }
  cambiarContadorBadge(-1);
}
function marcarTodasLeidas(e) {
  e.preventDefault(); e.stopPropagation();
  const fd = new FormData(); fd.append('accion','marcar_todas');
  fetch('notificaciones.php', {method:'POST', body:fd}).then(() => {
    document.querySelectorAll('.notif-item.unread').forEach(it => {
      it.classList.remove('unread'); it.querySelector('.notif-dot')?.remove();
    });
    document.getElementById('notif-badge')?.remove();
  }).catch(()=>{});
}
function cambiarContadorBadge(delta) {
  let badge = document.getElementById('notif-badge');
  if (!badge && delta > 0) {
    badge = document.createElement('span'); badge.id = 'notif-badge';
    badge.className = 'notif-badge'; badge.textContent = delta > 9 ? '9+' : delta;
    document.getElementById('notif-trigger').prepend(badge); return;
  }
  if (!badge) return;
  let n = parseInt(badge.textContent.replace('+','')) || 0; n += delta;
  if (n <= 0) { badge.remove(); return; }
  badge.textContent = n > 9 ? '9+' : n;
}

let toastTimer = null;
function showToast(msg, borderColor) {
  const el = document.getElementById('toast');
  el.textContent = msg; el.style.borderLeftColor = borderColor || 'var(--or)';
  el.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 3000);
}
<?php if ($ctrl->showSuccessToast): ?>showToast('✓ Cotización guardada'); window.NotifSound?.playCreate();<?php endif; ?>

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/cotizador/service-worker.js')
    .then(reg => console.info('[SW] registrado:', reg.scope))
    .catch(err => console.warn('[SW] error:', err));
}

let lastNoLeidas = <?= $ctrl->notifNoLeidas ?>;
let notifCacheList = <?= json_encode($ctrl->notificaciones) ?>;

function renderNotifDropdown(notifs) {
  const menu = document.getElementById('notif-menu');
  if (!menu) return;
  const header = menu.querySelector('.notif-menu-header');
  menu.innerHTML = '';
  if (header) menu.appendChild(header);
  if (!notifs || notifs.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'notif-empty';
    empty.textContent = 'Sin notificaciones 🎉';
    menu.appendChild(empty);
    return;
  }
  notifs.forEach(n => {
    const a = document.createElement('a');
    a.href = n.cotizacion_id ? `cotizaciones.php?id=${n.cotizacion_id}` : '#';
    a.dataset.id = n.id;
    a.className = 'notif-item' + (n.leido == '0' || n.leido === 0 ? ' unread' : '');
    a.onclick = () => marcarLeida(n.id);
    const fecha = new Date(n.created_at.replace(' ','T'));
    const fechaStr = fecha.toLocaleDateString('es-MX',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
    a.innerHTML = (n.leido == '0' || n.leido === 0 ? '<span class="notif-dot"></span>' : '') +
                  escHtml(n.mensaje) + `<span class="notif-time">${fechaStr}</span>`;
    menu.appendChild(a);
  });
}
function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
async function checkNotifs() {
  try {
    const d = await fetch('notificaciones.php').then(r => r.json());
    if (!d.ok) return;
    const nuevas = d.no_leidas - lastNoLeidas;
    if (nuevas > 0) {
      cambiarContadorBadge(nuevas);
      showToast(`🔔 ${nuevas} notificación${nuevas>1?'es':''} nueva${nuevas>1?'s':''}`, 'var(--or)');
      lastNoLeidas = d.no_leidas;
    }
    if (JSON.stringify(d.notificaciones) !== JSON.stringify(notifCacheList)) {
      notifCacheList = d.notificaciones;
      renderNotifDropdown(notifCacheList);
    }
  } catch(_) {}
}
setInterval(checkNotifs, 30000);

fetch('API/api_tipo_cambio.php').then(r => r.json()).then(d => {
  const v = d.MXN || d.mxn || null;
  if (v) document.getElementById('tc-val').textContent = parseFloat(v).toFixed(2);
}).catch(() => { document.getElementById('tc-val').textContent = 'N/D'; });

window.addEventListener('load', () => {
  document.querySelectorAll('.ring').forEach((ring, i) => {
    setTimeout(() => { ring.setAttribute('stroke-dasharray', ring.dataset.full); }, 300 + i * 100);
  });
  document.querySelectorAll('.hbar-fill').forEach((bar, i) => {
    setTimeout(() => { bar.style.width = (bar.dataset.width || '0') + '%'; }, 400 + i * 120);
  });
});

document.getElementById('searchTable')?.addEventListener('keyup', function() {
  const f = this.value.toLowerCase();
  document.querySelectorAll('#cot-tbody .tbl-row').forEach(r => {
    r.style.display = r.innerText.toLowerCase().includes(f) ? '' : 'none';
  });
});

let pendingDeleteId = null;
function askDelete(id, num, cliente) {
  pendingDeleteId = id;
  document.getElementById('modal-body').innerHTML =
    `¿Seguro que deseas eliminar la cotización <strong style="color:var(--or)">${num}</strong>` +
    ` de <strong>${cliente}</strong>?<br><br>Esta acción no se puede deshacer.`;
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
    const d = await fetch('dashboard.php', {method:'POST', body:fd}).then(r => r.json());
    if (d.ok) {
      const row = document.getElementById('row-' + pendingDeleteId);
      if (row) { row.style.transition = 'opacity .3s'; row.style.opacity = '0';
                 setTimeout(() => row.remove(), 320); }
      showToast('✓ Cotización eliminada', 'var(--red)');
    } else { showToast('Error al eliminar.', 'var(--red)'); }
  } catch(_) { showToast('Error de conexión.', 'var(--red)'); }
  btn.textContent = 'Eliminar'; btn.disabled = false; closeModal();
}

/* ── Widget "Evolución de cotizaciones": pendientes/aprobadas/rechazadas ── */
const evLabels = <?= json_encode($_labels) ?>;
const evPend   = <?= json_encode($_evPend) ?>;
const evAprob  = <?= json_encode($_evAprob) ?>;
const evRech   = <?= json_encode($_evRech) ?>;

const compareCtx = document.getElementById('compareChart');
if (compareCtx) {
  new Chart(compareCtx, {
    type: 'line',
    data: {
      labels: evLabels,
      datasets: [
        {
          label: 'Pendientes',
          data: evPend,
          borderColor: '#FFC876',
          backgroundColor: 'rgba(255,200,118,.10)',
          borderWidth: 2,
          pointRadius: 2,
          pointBackgroundColor: '#FFC876',
          pointBorderColor: '#0B0708',
          tension: 0.3,
          fill: true
        },
        {
          label: 'Aprobadas',
          data: evAprob,
          borderColor: '#8FE3A6',
          backgroundColor: 'rgba(143,227,166,.10)',
          borderWidth: 2,
          pointRadius: 2,
          pointBackgroundColor: '#8FE3A6',
          pointBorderColor: '#0B0708',
          tension: 0.3,
          fill: true
        },
        {
          label: 'Rechazadas',
          data: evRech,
          borderColor: '#FF7A6E',
          backgroundColor: 'rgba(255,122,110,.10)',
          borderWidth: 2,
          pointRadius: 2,
          pointBackgroundColor: '#FF7A6E',
          pointBorderColor: '#0B0708',
          tension: 0.3,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(255,255,255,.06)' },
          ticks: { color: '#B7A99A', precision: 0 }
        },
        x: {
          grid: { display: false },
          ticks: { color: '#B7A99A', autoSkip: true, maxRotation: 45, minRotation: 0, maxTicksLimit: 12 }
        }
      }
    }
  });
}
</script>

<!-- ── Modal: enviar cotización por correo ─────────────────────────────── -->
<style>
.email-backdrop{position:fixed;inset:0;background:rgba(5,3,2,.7);backdrop-filter:blur(6px);
  z-index:500;display:none;align-items:center;justify-content:center;}
.email-backdrop.open{display:flex;}
.email-modal{background:#15100e;border:1px solid var(--glass-brd);border-radius:20px;
  padding:24px 26px;width:100%;max-width:440px;box-shadow:0 30px 70px rgba(0,0,0,.6);
  opacity:0;transform:translateY(16px) scale(.96);animation:popIn .3s cubic-bezier(.22,.9,.32,1.15) forwards;}
.email-modal .modal-icon{background:rgba(90,163,255,.14);color:var(--blue);}
.email-modal h2{font-family:'Fraunces',serif;font-size:17px;font-weight:600;color:var(--ink);margin:0 0 4px;}
.em-cot-label{font-size:11px;color:var(--or);font-weight:600;margin:0 0 16px;}
.email-field{margin-bottom:12px;}
.email-field label{display:block;font-size:10px;color:var(--ink3);text-transform:uppercase;
  letter-spacing:.06em;margin-bottom:5px;font-weight:600;}
.email-field input,.email-field textarea{width:100%;background:var(--glass2);
  border:1px solid var(--glass-brd);border-radius:10px;color:var(--ink);
  font-family:'Inter',sans-serif;font-size:13px;padding:9px 12px;outline:none;
  box-sizing:border-box;transition:border-color .15s;}
.email-field input:focus,.email-field textarea:focus{border-color:var(--blue);}
.email-field textarea{resize:vertical;min-height:68px;line-height:1.5;}
.email-modal-foot{display:flex;gap:8px;margin-top:16px;justify-content:flex-end;align-items:center;}
.btn-em-cancel{padding:9px 15px;border-radius:10px;font-size:12px;cursor:pointer;
  background:var(--glass2);border:1px solid var(--glass-brd);
  color:var(--ink2);font-family:inherit;transition:.16s;}
.btn-em-cancel:hover{background:rgba(255,255,255,.08);}
.btn-em-send{padding:9px 17px;border-radius:10px;font-size:12px;font-weight:600;
  cursor:pointer;background:linear-gradient(135deg,var(--blue),#2f6fd6);border:none;color:#04162e;font-family:inherit;
  display:flex;align-items:center;gap:6px;transition:box-shadow .16s,transform .12s;}
.btn-em-send:hover{box-shadow:0 8px 22px rgba(90,163,255,.35);}
.btn-em-send:active{transform:scale(.96);}
.btn-em-send:disabled{opacity:.5;cursor:not-allowed;}
.em-status{font-size:11px;margin-left:auto;min-height:14px;}
.em-status.ok{color:var(--green);}.em-status.err{color:var(--red);}
</style>

<div class="email-backdrop" id="db-email-backdrop" onclick="if(event.target===this)cerrarEmailDash()">
  <div class="email-modal">
    <div class="modal-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
    </div>
    <h2>Enviar cotización por correo</h2>
    <p class="em-cot-label" id="db-em-num"></p>
    <div class="email-field">
      <label>Correo del cliente *</label>
      <input type="email" id="db-em-destino" placeholder="cliente@empresa.com">
    </div>
    <div class="email-field">
      <label>Con copia (CC)</label>
      <input type="email" id="db-em-cc" placeholder="opcional">
    </div>
    <div class="email-field">
      <label>Mensaje personalizado</label>
      <textarea id="db-em-mensaje" placeholder="Estimado cliente, adjunto nuestra propuesta..."></textarea>
    </div>
    <div class="email-modal-foot">
      <div class="em-status" id="db-em-status"></div>
      <button class="btn-em-cancel" onclick="cerrarEmailDash()">Cancelar</button>
      <button class="btn-em-send" id="db-btn-send" onclick="enviarEmailDash()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Enviar
      </button>
    </div>
  </div>
</div>

<script>
let _dbEmailId = 0;

function abrirEmailDash(id, email, numero) {
  _dbEmailId = id;
  document.getElementById('db-em-destino').value = email || '';
  document.getElementById('db-em-cc').value      = '';
  document.getElementById('db-em-mensaje').value = '';
  document.getElementById('db-em-num').textContent = numero;
  document.getElementById('db-em-status').textContent = '';
  document.getElementById('db-em-status').className = 'em-status';
  document.getElementById('db-btn-send').disabled = false;
  document.getElementById('db-btn-send').textContent = 'Enviar';
  document.getElementById('db-email-backdrop').classList.add('open');
  setTimeout(() => document.getElementById('db-em-destino').focus(), 80);
}

function cerrarEmailDash() {
  document.getElementById('db-email-backdrop').classList.remove('open');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') cerrarEmailDash();
});

async function enviarEmailDash() {
  const destino = document.getElementById('db-em-destino').value.trim();
  const cc      = document.getElementById('db-em-cc').value.trim();
  const mensaje = document.getElementById('db-em-mensaje').value.trim();
  const status  = document.getElementById('db-em-status');
  const btn     = document.getElementById('db-btn-send');

  if (!destino || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(destino)) {
    status.textContent = 'Correo inválido.';
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
      body: JSON.stringify({ cotizacion_id: _dbEmailId, email_destino: destino, email_cc: cc, mensaje }),
    });
    const data = await res.json();

    if (data.ok) {
      status.textContent = `✓ Enviado a ${data.email_destino}`;
      status.className = 'em-status ok';
      btn.textContent = '✓ Listo';
      window.NotifSound?.playSend();
      setTimeout(cerrarEmailDash, 2000);
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