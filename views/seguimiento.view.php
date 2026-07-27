<?php
/**
 * views/seguimiento_design2_glass.view.php
 * DISEÑO 2 — "Glassmorphism con neón"
 * Misma funcionalidad que el diseño original, pero con estética radicalmente diferente.
 *
 * Paleta de colores respetada:
 *   - Naranja: #F57B02 (y sus variantes)
 *   - Rojo: #E54818
 *   - Verde: #4ade80
 *   - Fondo oscuro: #0D0A14
 *   - Grises: #9B94B8, #5C5478, etc.
 */

function fc_cliente(array $c): string {
    return trim($c['empresa'] ?? $c['atencion'] ?? $c['cliente_nombre'] ?? '') ?: '—';
}
function fc_iniciales(string $nombre): string {
    $nombre = trim($nombre);
    if ($nombre === '' || $nombre === '—') return '·';
    $partes = preg_split('/\s+/', $nombre);
    $ini = '';
    foreach (array_slice($partes, 0, 2) as $p) { $ini .= mb_strtoupper(mb_substr($p, 0, 1)); }
    return $ini ?: '·';
}

$cots      = $ctrl->alertas;
$totalCots = count($cots);

$kpi_enviadas = count(array_filter($cots, fn($c) => !empty($c['primer_email']) || !empty($c['email_token'])));
$kpi_abiertos = count(array_filter($cots, fn($c) => !empty($c['email_opened_at'])));
$kpi_urgentes = count(array_filter($cots, fn($c) => (int)($c['dias_sin_contacto'] ?? 0) >= 7));
$kpi_valor    = array_sum(array_map(fn($c) => (float)($c['total'] ?? 0), $cots));

$idPresel   = intval($_GET['id'] ?? 0);
$filtroAct  = trim($_GET['filtro'] ?? 'todas');

$idxInicial = 0;
foreach ($cots as $i => $c) {
    if ((int)$c['id'] === $idPresel) { $idxInicial = $i; break; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Fortress8 · Seguimiento</title>
<link rel="icon" href="assets/img/favicon.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
/* ============================================================
   DISEÑO 2 — "Glassmorphism con neón"
   ============================================================ */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#0D0A14;
  --s1:rgba(19,16,28,0.75);
  --s2:rgba(26,22,38,0.65);
  --s3:rgba(34,30,48,0.55);
  --line:rgba(255,255,255,0.08);
  --or:#F57B02;
  --or-glow:rgba(245,123,2,0.3);
  --re:#E54818;
  --re-glow:rgba(229,72,24,0.25);
  --green:#4ade80;
  --green-glow:rgba(74,222,128,0.2);
  --ink:#F5F0FF;
  --ink2:#9B94B8;
  --ink3:#5C5478;
  --radius:20px;
  --glass:0 8px 32px rgba(0,0,0,0.5);
  --neon-shadow:0 0 30px rgba(245,123,2,0.15);
}
body{
  font-family:'Inter',sans-serif;
  background:var(--bg);
  color:var(--ink);
  height:100vh;
  height:100dvh;
  overflow:hidden;
  position:relative;
}
body::before{
  content:'';
  position:fixed;
  inset:0;
  z-index:0;
  background:
    radial-gradient(ellipse 60% 40% at 10% 20%, rgba(245,123,2,0.15) 0%, transparent 60%),
    radial-gradient(ellipse 50% 50% at 90% 80%, rgba(229,72,24,0.10) 0%, transparent 55%),
    radial-gradient(ellipse 30% 30% at 50% 50%, rgba(74,222,128,0.04) 0%, transparent 70%);
  pointer-events:none;
}

/* ===== SHELL (topbar + sidebar) ===== */
.shell{
  display:grid;
  grid-template-columns:64px 1fr;
  grid-template-rows:60px 1fr;
  height:100vh;
  height:100dvh;
  position:relative;
  z-index:1;
}

.topbar{
  grid-column:1/-1;
  background:rgba(13,10,20,0.85);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);
  border-bottom:1px solid var(--line);
  display:flex;
  align-items:center;
  gap:12px;
  padding:0 20px;
  z-index:20;
}
.tb-logo{height:34px;width:auto;flex-shrink:0;}
.tb-logo img{height:100%;width:auto;}
.tb-brand{
  font-family:'Fraunces',serif;
  font-size:18px;
  font-weight:700;
  color:var(--ink);
  letter-spacing:-0.3px;
}
.tb-brand span{color:var(--or);font-style:italic;}
.tb-search{
  flex:1;
  max-width:280px;
  position:relative;
  margin:0 12px;
}
.tb-search i{
  position:absolute;
  left:14px;
  top:50%;
  transform:translateY(-50%);
  color:var(--ink3);
  font-size:16px;
}
.tb-search input{
  width:100%;
  padding:8px 14px 8px 40px;
  background:rgba(255,255,255,0.04);
  border:1px solid var(--line);
  border-radius:40px;
  color:var(--ink);
  font-size:13px;
  outline:none;
  transition:border-color 0.25s, box-shadow 0.25s;
}
.tb-search input:focus{
  border-color:var(--or);
  box-shadow:0 0 0 3px var(--or-glow);
}
.tb-filters{display:flex;gap:6px;}
.rf-chip{
  font-size:11px;
  padding:5px 14px;
  border-radius:40px;
  color:var(--ink2);
  border:0.5px solid var(--line);
  cursor:pointer;
  text-decoration:none;
  font-weight:600;
  transition:all 0.2s;
  white-space:nowrap;
}
.rf-chip.on{
  background:var(--or);
  color:#fff;
  border-color:var(--or);
  box-shadow:0 0 20px var(--or-glow);
}
.rf-chip:hover:not(.on){
  border-color:var(--or);
  color:var(--or);
}

.tb-actions{display:flex;align-items:center;gap:10px;margin-left:auto;}
.tb-avatar{
  width:36px;height:36px;
  border-radius:50%;
  background:linear-gradient(135deg,#ffb46b,var(--or));
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:12px;
  font-weight:700;
  color:#1a0d04;
  cursor:pointer;
  position:relative;
  flex-shrink:0;
  transition:box-shadow 0.2s;
}
.tb-avatar:hover{box-shadow:0 0 0 3px var(--or-glow);}
.user-menu{
  position:absolute;
  top:44px;
  right:0;
  background:rgba(19,16,28,0.95);
  backdrop-filter:blur(14px);
  border:1px solid var(--line);
  border-radius:14px;
  min-width:180px;
  z-index:200;
  display:none;
  overflow:hidden;
  box-shadow:0 20px 50px rgba(0,0,0,0.6);
}
.user-menu.open{display:block;}
.user-menu .user-name{
  padding:10px 16px 6px;
  font-size:13px;
  font-weight:600;
  color:var(--ink);
}
.user-menu a{
  display:block;
  padding:8px 16px;
  font-size:13px;
  color:var(--ink2);
  text-decoration:none;
  transition:background 0.15s;
}
.user-menu a:hover{background:rgba(255,255,255,0.04);color:var(--ink);}
.user-menu .sep{height:1px;background:var(--line);margin:4px 0;}

.hamburger{
  display:none;
  background:none;
  border:none;
  color:var(--ink);
  font-size:22px;
  cursor:pointer;
}
.btn-new{
  background:var(--or);
  color:#fff;
  border:none;
  border-radius:40px;
  padding:8px 20px;
  font-size:13px;
  font-weight:600;
  cursor:pointer;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  gap:6px;
  transition:all 0.25s;
  box-shadow:0 4px 16px var(--or-glow);
}
.btn-new:hover{transform:translateY(-2px);box-shadow:0 8px 30px var(--or-glow);background:#ff8a1f;}

.sidebar{
  background:rgba(255,255,255,0.02);
  backdrop-filter:blur(14px);
  -webkit-backdrop-filter:blur(14px);
  border-right:1px solid var(--line);
  padding:16px 0;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:4px;
  overflow:hidden;
}
.si{
  width:42px;height:42px;
  border-radius:14px;
  display:flex;
  align-items:center;
  justify-content:center;
  text-decoration:none;
  transition:background 0.2s, transform 0.15s;
  color:var(--ink3);
}
.si i{font-size:20px;}
.si:hover:not(.on){background:rgba(255,255,255,0.05);transform:scale(1.05);color:var(--ink2);}
.si.on{
  background:rgba(245,123,2,0.15);
  box-shadow:inset 0 0 20px var(--or-glow);
  color:var(--or);
}
.si-sep{width:28px;height:1px;background:var(--line);margin:6px 0;}
.si-spacer{flex:1;}

/* ===== CONTENIDO PRINCIPAL ===== */
.content-col{
  display:flex;
  flex-direction:column;
  overflow:hidden;
  position:relative;
  z-index:1;
}

/* KPI — píldoras con neón */
.kpi-strip{
  flex-shrink:0;
  display:flex;
  gap:16px;
  padding:16px 24px;
  background:rgba(13,10,20,0.5);
  backdrop-filter:blur(8px);
  border-bottom:1px solid var(--line);
  flex-wrap:nowrap;
  overflow-x:auto;
  scrollbar-width:none;
}
.kpi-strip::-webkit-scrollbar{display:none;}
.kpi-cell{
  flex:0 0 auto;
  display:flex;
  align-items:center;
  gap:14px;
  padding:8px 20px 8px 14px;
  background:var(--s1);
  border:0.5px solid var(--line);
  border-radius:60px;
  backdrop-filter:blur(4px);
  transition:border-color 0.25s;
}
.kpi-cell:hover{border-color:var(--or);}
.kpi-icon{
  width:36px;height:36px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:18px;
  background:rgba(245,123,2,0.12);
  color:var(--or);
}
.kpi-cell.urgent .kpi-icon{background:rgba(229,72,24,0.15);color:var(--re);}
.kpi-label{font-size:10px;color:var(--ink3);text-transform:uppercase;letter-spacing:0.05em;font-weight:600;}
.kpi-value{
  font-size:22px;
  font-weight:800;
  color:var(--ink);
  line-height:1.1;
}
.kpi-value small{font-size:11px;color:var(--ink3);font-weight:400;}

/* ===== LISTA DE CLIENTES (carrusel → lista vertical) ===== */
.client-panel{
  flex:1;
  display:grid;
  grid-template-columns:320px 1fr;
  gap:0;
  overflow:hidden;
  background:rgba(13,10,20,0.3);
}

.client-list{
  background:var(--s1);
  backdrop-filter:blur(8px);
  border-right:1px solid var(--line);
  overflow-y:auto;
  padding:12px 8px;
  display:flex;
  flex-direction:column;
  gap:6px;
}
.client-list::-webkit-scrollbar{width:4px;}
.client-list::-webkit-scrollbar-thumb{background:var(--s3);border-radius:4px;}

.client-item{
  display:flex;
  align-items:center;
  gap:12px;
  padding:10px 14px;
  border-radius:14px;
  background:rgba(255,255,255,0.02);
  border:0.5px solid transparent;
  cursor:pointer;
  transition:all 0.2s;
}
.client-item:hover{
  background:rgba(255,255,255,0.04);
  border-color:var(--line);
}
.client-item.active{
  background:rgba(245,123,2,0.08);
  border-color:var(--or);
  box-shadow:inset 0 0 20px var(--or-glow);
}
.client-avatar{
  width:38px;height:38px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
  font-size:14px;
  background:rgba(245,123,2,0.12);
  color:var(--or);
  flex-shrink:0;
}
.client-info{flex:1;min-width:0;}
.client-name{font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.client-meta{
  display:flex;
  gap:10px;
  font-size:11px;
  color:var(--ink3);
}
.client-meta .days{display:flex;align-items:center;gap:4px;}
.client-badge{font-size:14px;}
.badge-ok{color:var(--green);}
.badge-mail{color:#60a5fa;}
.badge-urgent{color:var(--re);}

/* ===== PANEL DE DETALLE ===== */
.client-detail{
  overflow-y:auto;
  padding:20px 28px 28px;
  display:flex;
  flex-direction:column;
  gap:20px;
}
.client-detail::-webkit-scrollbar{width:4px;}
.client-detail::-webkit-scrollbar-thumb{background:var(--s3);border-radius:4px;}

/* Cabecera */
.detail-header{
  display:flex;
  align-items:flex-start;
  gap:16px;
  padding-bottom:16px;
  border-bottom:1px solid var(--line);
}
.detail-avatar{
  width:56px;height:56px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:22px;
  font-weight:800;
  background:linear-gradient(135deg,#ffb46b,var(--or));
  color:#1a0d04;
  flex-shrink:0;
}
.detail-title{flex:1;}
.detail-num{
  font-size:12px;
  color:var(--or);
  font-weight:700;
  letter-spacing:0.03em;
}
.detail-name{font-size:22px;font-weight:800;margin:2px 0 4px;}
.detail-meta{
  display:flex;
  flex-wrap:wrap;
  gap:12px;
  font-size:13px;
  color:var(--ink2);
}
.detail-meta span{display:flex;align-items:center;gap:5px;}
.detail-nav{
  display:flex;
  gap:6px;
  align-items:center;
}
.nav-btn{
  width:32px;height:32px;
  border-radius:50%;
  border:0.5px solid var(--line);
  background:var(--s2);
  color:var(--ink2);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:all 0.2s;
}
.nav-btn:hover{background:var(--or);color:#fff;border-color:transparent;}
.nav-btn:disabled{opacity:0.25;cursor:not-allowed;}
.nav-count{font-size:13px;color:var(--ink3);font-weight:500;}

/* Acciones rápidas */
.quick-actions{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.q-btn{
  display:flex;
  align-items:center;
  gap:8px;
  padding:8px 16px;
  border-radius:40px;
  border:0.5px solid var(--line);
  background:var(--s2);
  color:var(--ink2);
  font-size:13px;
  font-weight:500;
  cursor:pointer;
  transition:all 0.2s;
  text-decoration:none;
}
.q-btn i{font-size:16px;}
.q-btn:hover{border-color:var(--or);color:var(--or);background:rgba(245,123,2,0.06);}
.q-btn.primary{background:var(--or);color:#fff;border-color:var(--or);}
.q-btn.primary:hover{box-shadow:0 0 30px var(--or-glow);transform:translateY(-2px);}

/* Estados */
.status-row{display:flex;gap:14px;}
.status-chip{
  flex:1;
  display:flex;
  align-items:center;
  gap:10px;
  padding:10px 16px;
  background:var(--s2);
  border:0.5px solid var(--line);
  border-radius:14px;
}
.status-chip .sc-icon{
  width:32px;height:32px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:16px;
  background:var(--s3);
  color:var(--ink3);
}
.status-chip.done .sc-icon{background:rgba(74,222,128,0.12);color:var(--green);}
.status-chip .sc-label{font-size:11px;color:var(--ink3);font-weight:600;}
.status-chip .sc-value{font-size:14px;font-weight:700;}

/* Historial y formulario en dos columnas en escritorio */
.timeline-form{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
}

/* Historial */
.timeline-box{
  background:var(--s1);
  border:0.5px solid var(--line);
  border-radius:var(--radius);
  overflow:hidden;
  backdrop-filter:blur(4px);
}
.timeline-box .tl-header{
  padding:14px 18px;
  border-bottom:1px solid var(--line);
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.tl-header .tl-title{font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:0.04em;}
.tl-header .tl-count{
  background:var(--or);
  color:#fff;
  padding:1px 12px;
  border-radius:20px;
  font-size:12px;
  font-weight:700;
}
.tl-scroll{
  max-height:340px;
  overflow-y:auto;
  padding:14px 18px;
  display:flex;
  flex-direction:column;
  gap:12px;
}
.tl-scroll::-webkit-scrollbar{width:4px;}
.tl-scroll::-webkit-scrollbar-thumb{background:var(--s3);border-radius:4px;}
.tl-item{
  padding:10px 14px;
  border-radius:14px;
  background:rgba(255,255,255,0.02);
  border-left:3px solid var(--or);
}
.tl-item.auto-item{border-left-color:var(--ink3);}
.tl-item.user-item{border-left-color:var(--green);background:rgba(74,222,128,0.04);}
.tl-meta{display:flex;gap:8px;flex-wrap:wrap;font-size:11px;color:var(--ink3);margin-bottom:4px;}
.tl-tag{font-weight:700;color:var(--or);}
.tl-desc{font-size:13px;line-height:1.5;color:var(--ink);}
.tl-proximo{font-size:11px;color:var(--or);margin-top:4px;display:flex;align-items:center;gap:4px;}
.tl-auto{font-size:10px;color:var(--ink3);font-style:italic;margin-top:2px;}

/* Formulario */
.form-box{
  background:var(--s1);
  border:0.5px solid var(--line);
  border-radius:var(--radius);
  backdrop-filter:blur(4px);
  padding:18px 20px;
}
.form-box .form-title{font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:14px;}
.form-group{margin-bottom:12px;}
.form-group label{display:block;font-size:11px;color:var(--ink3);font-weight:600;margin-bottom:4px;}
.form-select,.form-input,.form-textarea{
  width:100%;
  background:rgba(255,255,255,0.04);
  border:0.5px solid var(--line);
  border-radius:12px;
  padding:8px 12px;
  color:var(--ink);
  font-size:13px;
  outline:none;
  transition:border-color 0.2s;
}
.form-select:focus,.form-input:focus,.form-textarea:focus{border-color:var(--or);}
.form-textarea{resize:vertical;min-height:60px;line-height:1.5;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-save{
  padding:10px 24px;
  border-radius:40px;
  border:none;
  background:var(--or);
  color:#fff;
  font-weight:700;
  font-size:13px;
  cursor:pointer;
  transition:all 0.25s;
  display:flex;
  align-items:center;
  gap:8px;
}
.form-save:hover{box-shadow:0 0 30px var(--or-glow);transform:translateY(-2px);}
.form-save:disabled{opacity:0.4;}
.form-msg{font-size:12px;min-height:20px;margin-top:6px;text-align:right;}
.form-msg.ok{color:var(--green);}
.form-msg.err{color:var(--re);}

/* ===== MODAL EMAIL ===== */
.em-backdrop{
  position:fixed;inset:0;
  background:rgba(0,0,0,0.6);
  backdrop-filter:blur(6px);
  z-index:500;
  display:none;
  align-items:center;
  justify-content:center;
}
.em-backdrop.open{display:flex;}
.em-modal{
  background:var(--s1);
  backdrop-filter:blur(18px);
  border:0.5px solid var(--line);
  border-radius:20px;
  padding:28px 32px;
  max-width:440px;
  width:92%;
  box-shadow:0 30px 80px rgba(0,0,0,0.7);
}
.em-modal h2{font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;color:var(--ink);}
.em-modal h2 i{color:var(--or);}
.em-num{font-size:13px;color:var(--or);font-weight:600;margin:4px 0 16px;}
.em-field{margin-bottom:14px;}
.em-field label{display:block;font-size:11px;color:var(--ink3);font-weight:600;margin-bottom:4px;}
.em-field input,.em-field textarea{
  width:100%;
  background:rgba(255,255,255,0.04);
  border:0.5px solid var(--line);
  border-radius:12px;
  padding:8px 12px;
  color:var(--ink);
  font-size:13px;
  outline:none;
  transition:border-color 0.2s;
}
.em-field input:focus,.em-field textarea:focus{border-color:var(--or);}
.em-field textarea{resize:vertical;min-height:60px;}
.em-foot{display:flex;gap:10px;align-items:center;margin-top:18px;flex-wrap:wrap;}
.em-status{flex:1;font-size:13px;min-height:20px;}
.em-status.ok{color:var(--green);}
.em-status.err{color:var(--re);}
.em-cancel{
  padding:8px 16px;
  border-radius:40px;
  background:var(--s3);
  border:0.5px solid var(--line);
  color:var(--ink2);
  cursor:pointer;
  transition:all 0.2s;
}
.em-cancel:hover{background:rgba(255,255,255,0.06);}
.em-send{
  padding:8px 20px;
  border-radius:40px;
  border:none;
  background:#1d4ed8;
  color:#fff;
  font-weight:600;
  cursor:pointer;
  display:flex;
  align-items:center;
  gap:6px;
  transition:all 0.2s;
}
.em-send:hover{background:#1e40af;}
.em-send:disabled{opacity:0.4;}

.toast{
  position:fixed;bottom:24px;right:24px;
  background:var(--s1);
  backdrop-filter:blur(12px);
  border:0.5px solid var(--line);
  border-left:4px solid var(--or);
  border-radius:12px;
  padding:12px 20px;
  font-size:13px;
  color:var(--ink);
  z-index:600;
  transform:translateX(130%);
  transition:transform 0.4s cubic-bezier(0.2,0.8,0.3,1);
  box-shadow:0 16px 40px rgba(0,0,0,0.5);
}
.toast.show{transform:translateX(0);}

/* ===== RESPONSIVE ===== */
@media(max-width:1050px){
  .shell{grid-template-columns:1fr;grid-template-rows:auto 1fr;height:auto;}
  body{height:auto;overflow:auto;}
  .sidebar{display:none;position:fixed;top:0;left:0;bottom:0;width:64px;z-index:210;}
  .sidebar.open{display:flex;box-shadow:8px 0 24px rgba(0,0,0,0.5);}
  .hamburger{display:block;}
  .topbar{flex-wrap:wrap;height:auto;padding:10px 16px;}
  .tb-filters,.tb-search{display:none;}
  .client-panel{grid-template-columns:1fr;}
  .client-list{max-height:180px;border-right:none;border-bottom:1px solid var(--line);}
  .timeline-form{grid-template-columns:1fr;}
}
@media(max-width:768px){
  .tb-brand{font-size:15px;}
  .btn-new .txt{display:none;}
  .btn-new{padding:8px 12px;border-radius:12px;}
  .kpi-strip{padding:12px 14px;gap:10px;}
  .kpi-cell{padding:6px 14px 6px 10px;}
  .kpi-value{font-size:18px;}
  .detail-header{flex-wrap:wrap;}
  .detail-name{font-size:18px;}
  .status-row{flex-direction:column;}
  .quick-actions{justify-content:center;}
  .form-row{grid-template-columns:1fr;}
  .client-detail{padding:16px;}
  .em-modal{padding:20px;}
  .toast{left:16px;right:16px;bottom:80px;}
}
</style>
</head>
<body>

<div class="shell">
  <!-- TOPBAR -->
  <header class="topbar">
    <button class="hamburger" onclick="toggleSidenav()" aria-label="Menú"><i class="ti ti-menu-2"></i></button>
    <div class="tb-logo"><img src="assets/img/logoss.png" alt="Fortress8"></div>
    <div class="tb-brand">FORTRESS<span>8</span></div>
    <div class="tb-search"><i class="ti ti-search"></i><input type="text" id="live-search" placeholder="Buscar cliente..."></div>
    <div class="tb-filters">
      <?php $filtros = ['todas'=>'Todas']; ?>
      <?php foreach ($filtros as $fk => $fl): ?>
        <a href="?filtro=<?= $fk ?>" class="rf-chip <?= $filtroAct === $fk ? 'on' : '' ?>"><?= $fl ?></a>
      <?php endforeach; ?>
    </div>
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
      <a href="cotizaciones.php?action=nueva" class="btn-new"><i class="ti ti-plus"></i> <span class="txt">Nueva</span></a>
    </div>
  </header>

  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidenav">
    <a class="si" href="dashboard.php" title="Dashboard"><i class="ti ti-layout-dashboard"></i></a>
    <a class="si" href="lista_cotizaciones.php" title="Cotizaciones"><i class="ti ti-file-invoice"></i></a>
    <a class="si on" href="seguimiento.php" title="Seguimiento"><i class="ti ti-timeline"></i></a>
    <div class="si-sep"></div>
    <a class="si" href="lista_precios.php" title="Lista de precios"><i class="ti ti-list-details"></i></a>
    <a class="si" href="comparar_listas.php" title="Comparar listas"><i class="ti ti-copy"></i></a>
    <?php if ($auth->esAdmin()): ?>
    <div class="si-sep"></div>
    <a class="si" href="auditoria.php" title="Auditoría"><i class="ti ti-shield"></i></a>
    <?php endif; ?>
    <div class="si-spacer"></div>
    <a class="si" href="logout.php" title="Cerrar sesión"><i class="ti ti-logout"></i></a>
  </nav>

  <!-- CONTENIDO -->
  <div class="content-col">

    <!-- KPI -->
    <div class="kpi-strip">
      <div class="kpi-cell"><div class="kpi-icon"><i class="ti ti-currency-dollar"></i></div><div><div class="kpi-label">Valor seguimiento</div><div class="kpi-value">$<?= number_format($kpi_valor, 0) ?> <small>USD</small></div></div></div>
      <div class="kpi-cell"><div class="kpi-icon"><i class="ti ti-mail-check"></i></div><div><div class="kpi-label">Enviados</div><div class="kpi-value"><?= $kpi_enviadas ?> <small>/ <?= $totalCots ?></small></div></div></div>
      <div class="kpi-cell"><div class="kpi-icon"><i class="ti ti-eye"></i></div><div><div class="kpi-label">Abiertos</div><div class="kpi-value"><?= $kpi_abiertos ?> <small>/ <?= $kpi_enviadas ?></small></div></div></div>
      <div class="kpi-cell <?= $kpi_urgentes > 0 ? 'urgent' : '' ?>"><div class="kpi-icon"><i class="ti ti-alert-triangle"></i></div><div><div class="kpi-label">Atención urgente</div><div class="kpi-value"><?= $kpi_urgentes ?> <small>≥7d</small></div></div></div>
    </div>

    <!-- PANEL CLIENTES + DETALLE -->
    <div class="client-panel">

      <!-- LISTA VERTICAL -->
      <div class="client-list" id="client-list">
        <?php if (empty($cots)): ?>
          <div style="padding:20px;text-align:center;color:var(--ink3);">Sin cotizaciones</div>
        <?php else: ?>
          <?php foreach ($cots as $i => $c):
            $dias    = (int)($c['dias_sin_contacto'] ?? 0);
            $cli     = fc_cliente($c);
            $isActive= ($i === $idxInicial);
            $badge   = '';
            if (!empty($c['email_opened_at']))              $badge = 'eye';
            elseif (!empty($c['email_token']) || !empty($c['primer_email'])) $badge = 'mail';
            elseif ($dias >= 7)                             $badge = 'urgent';
          ?>
          <div class="client-item <?= $isActive ? 'active' : '' ?>"
               data-idx="<?= $i ?>" data-id="<?= $c['id'] ?>"
               onclick="seleccionarFila(this)">
            <div class="client-avatar"><?= htmlspecialchars(fc_iniciales($cli)) ?></div>
            <div class="client-info">
              <div class="client-name"><?= htmlspecialchars($cli) ?></div>
              <div class="client-meta">
                <span class="days"><i class="ti ti-clock"></i> <?= $dias ?>d</span>
                <span>#<?= htmlspecialchars($c['numero_cotizacion'] ?? '—') ?></span>
              </div>
            </div>
            <?php if ($badge === 'eye'): ?><span class="client-badge badge-ok"><i class="ti ti-eye"></i></span>
            <?php elseif ($badge === 'mail'): ?><span class="client-badge badge-mail"><i class="ti ti-mail-check"></i></span>
            <?php elseif ($badge === 'urgent'): ?><span class="client-badge badge-urgent"><i class="ti ti-alert-triangle"></i></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- DETALLE -->
      <div class="client-detail" id="detail-panel">
        <?php if (empty($cots)): ?>
          <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--ink3);gap:12px;">
            <i class="ti ti-circle-check" style="font-size:40px;opacity:0.2;"></i>
            <p>No hay cotizaciones para este filtro.</p>
          </div>
        <?php else: ?>
          <!-- Cabecera (se llena con JS) -->
          <div class="detail-header" id="detail-header">
            <div class="detail-avatar" id="det-avatar">—</div>
            <div class="detail-title">
              <div class="detail-num" id="det-num">—</div>
              <div class="detail-name" id="det-name">—</div>
              <div class="detail-meta" id="det-meta"></div>
            </div>
            <div class="detail-nav">
              <button class="nav-btn" id="btn-prev" onclick="navegarPrev()"><i class="ti ti-chevron-left"></i></button>
              <span class="nav-count" id="nav-count">—</span>
              <button class="nav-btn" id="btn-next" onclick="navegarNext()"><i class="ti ti-chevron-right"></i></button>
            </div>
          </div>

          <!-- Acciones -->
          <div class="quick-actions">
            <button class="q-btn primary" onclick="abrirEmail()"><i class="ti ti-mail-forward"></i> Enviar correo</button>
            <a id="btn-ver" href="#" class="q-btn"><i class="ti ti-file-invoice"></i> Ver cotización</a>
            <button class="q-btn" onclick="quickLog('llamada','📞 Llamada realizada — sin respuesta')"><i class="ti ti-phone-off"></i> Sin respuesta</button>
            <button class="q-btn" onclick="quickLog('whatsapp','💬 Mensaje WhatsApp enviado — pendiente')"><i class="ti ti-brand-whatsapp"></i> WA pendiente</button>
          </div>

          <!-- Estados -->
          <div class="status-row">
            <div class="status-chip pend" id="ts-enviado"><div class="sc-icon"><i class="ti ti-send"></i></div><div><div class="sc-label">Enviado</div><div class="sc-value">—</div></div></div>
            <div class="status-chip pend" id="ts-abierto"><div class="sc-icon"><i class="ti ti-eye"></i></div><div><div class="sc-label">Abierto</div><div class="sc-value">—</div></div></div>
          </div>

          <!-- Historial + Formulario (dos columnas) -->
          <div class="timeline-form">
            <!-- Historial -->
            <div class="timeline-box">
              <div class="tl-header">
                <span class="tl-title">Historial de contacto</span>
                <span class="tl-count" id="tl-count">0</span>
              </div>
              <div class="tl-scroll" id="tl-scroll">
                <div id="tl-loading" style="display:none;padding:20px;text-align:center;color:var(--ink3);">
                  <i class="ti ti-loader-2" style="animation:spin 1s linear infinite;display:block;font-size:20px;margin-bottom:6px;"></i> Cargando...
                </div>
                <div id="tl-list"></div>
              </div>
            </div>

            <!-- Formulario -->
            <div class="form-box">
              <div class="form-title">Registrar contacto</div>
              <form id="form-registrar" onsubmit="event.preventDefault(); guardarSeguimiento();">
                <div class="form-group">
                  <label>Tipo</label>
                  <select class="form-select" id="f-tipo">
                    <option value="llamada">📞 Llamada</option>
                    <option value="whatsapp">💬 WhatsApp</option>
                    <option value="email">📧 Email</option>
                    <option value="visita">📍 Visita</option>
                    <option value="nota">📝 Nota interna</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Descripción</label>
                  <textarea class="form-textarea" id="f-desc" rows="2" placeholder="Describe el contacto..."></textarea>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label>Fecha y hora</label>
                    <input type="datetime-local" class="form-input" id="f-fecha">
                  </div>
                  <div class="form-group">
                    <label>Próximo contacto</label>
                    <input type="date" class="form-input" id="f-proximo">
                  </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:4px;">
                  <button class="form-save" id="btn-save"><i class="ti ti-device-floppy"></i> Guardar</button>
                  <div class="form-msg" id="form-msg"></div>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL EMAIL -->
<div class="em-backdrop" id="em-backdrop" onclick="if(event.target===this)cerrarEmail()">
  <div class="em-modal">
    <h2><i class="ti ti-mail-forward"></i> Enviar cotización</h2>
    <div class="em-num" id="em-num-lbl">—</div>
    <div class="em-field"><label>Correo del cliente *</label><input type="email" id="em-destino" placeholder="cliente@empresa.com"></div>
    <div class="em-field"><label>CC</label><input type="email" id="em-cc" placeholder="opcional"></div>
    <div class="em-field"><label>Mensaje</label><textarea id="em-mensaje" rows="2" placeholder="Estimado cliente..."></textarea></div>
    <div class="em-foot">
      <div class="em-status" id="em-status"></div>
      <button class="em-cancel" onclick="cerrarEmail()">Cancelar</button>
      <button class="em-send" id="em-btn-send" onclick="enviarEmail()"><i class="ti ti-send"></i> Enviar</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="assets/js/notif-sound.js"></script>
<script>
// ===== MISMO JAVASCRIPT que el diseño original (solo cambia la selección de elementos) =====
const COTS = <?= json_encode(array_values(array_map(fn($c) => [
  'id'=>(int)$c['id'],'numero_cotizacion'=>$c['numero_cotizacion'] ?? '—','cliente_nombre'=>fc_cliente($c),
  'cliente_email'=>$c['cliente_email'] ?? '','total'=>$c['total'] ?? 0,'moneda'=>$c['moneda'] ?? 'USD',
  'dias_sin_contacto'=>(int)($c['dias_sin_contacto'] ?? 0),'email_token'=>$c['email_token'] ?? null,
  'email_opened_at'=>$c['email_opened_at'] ?? null,'primer_email'=>$c['primer_email'] ?? null,
  'vendedor_nombre'=>$c['vendedor_nombre'] ?? '',
], $cots))) ?>;

let cotIdx = <?= $idxInicial ?>;
let cotActualId = 0, cotActualEmail = '', cotActualNum = '';
const LABELS = {llamada:'Llamada',email:'Email',visita:'Visita',whatsapp:'WhatsApp',nota:'Nota',apertura_email:'Email abierto',link_visto:'Link visto'};
const AUTO_TIPOS = ['apertura_email','link_visto'];

function toggleSidenav(){ document.getElementById('sidenav').classList.toggle('open'); }
document.addEventListener('click', e => {
  const nav = document.getElementById('sidenav'), burger = document.querySelector('.hamburger');
  if (nav?.classList.contains('open') && !nav.contains(e.target) && !burger?.contains(e.target)) nav.classList.remove('open');
});
function toggleUserMenu(){ document.getElementById('user-menu').classList.toggle('open'); }
document.addEventListener('click', e => {
  const t = document.getElementById('user-trigger');
  if (t && !t.contains(e.target)) document.getElementById('user-menu')?.classList.remove('open');
});

let _tt; function showToast(msg,color){ const t=document.getElementById('toast'); t.textContent=msg; t.style.borderLeftColor=color||'var(--or)'; t.classList.add('show'); clearTimeout(_tt); _tt=setTimeout(()=>t.classList.remove('show'),3200); }
function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

document.getElementById('live-search')?.addEventListener('input', function(){
  const q=this.value.toLowerCase();
  document.querySelectorAll('.client-item').forEach(el => {
    el.style.display = (!q || el.textContent.toLowerCase().includes(q)) ? '' : 'none';
  });
});

function seleccionarFila(el){
  const idx=parseInt(el.dataset.idx);
  if(idx===cotIdx && cotActualId) return;
  cotIdx=idx;
  document.querySelectorAll('.client-item').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  cargarCot(COTS[idx]);
}

function navegarPrev(){
  const visible=[...document.querySelectorAll('.client-item')].filter(t=>t.style.display!=='none');
  const cur=visible.findIndex(t=>parseInt(t.dataset.idx)===cotIdx);
  if(cur>0) seleccionarFila(visible[cur-1]);
}
function navegarNext(){
  const visible=[...document.querySelectorAll('.client-item')].filter(t=>t.style.display!=='none');
  const cur=visible.findIndex(t=>parseInt(t.dataset.idx)===cotIdx);
  if(cur<visible.length-1) seleccionarFila(visible[cur+1]);
}

function cargarCot(cot){
  if(!cot) return;
  cotActualId=cot.id; cotActualEmail=cot.cliente_email||''; cotActualNum=cot.numero_cotizacion||'';
  const url=new URL(window.location); url.searchParams.set('id',cot.id); history.pushState({},'',url);

  document.getElementById('det-avatar').textContent = (cot.cliente_nombre||'—').trim().split(/\s+/).slice(0,2).map(p=>p[0]).join('').toUpperCase() || '·';
  document.getElementById('det-num').textContent = cot.numero_cotizacion||'—';
  document.getElementById('det-name').textContent = cot.cliente_nombre||'—';
  let meta='';
  if(cot.cliente_email) meta+=`<span><i class="ti ti-mail"></i> ${esc(cot.cliente_email)}</span>`;
  if(cot.total) meta+=`<span><i class="ti ti-currency-dollar"></i> $${Number(cot.total).toLocaleString()} ${esc(cot.moneda||'USD')}</span>`;
  const dias= cot.dias_sin_contacto||0;
  const color= dias>=10?'var(--re)':(dias>=5?'var(--or)':'var(--green)');
  meta+=`<span style="color:${color}"><i class="ti ti-clock"></i> ${dias}d sin contacto</span>`;
  if(cot.vendedor_nombre) meta+=`<span><i class="ti ti-user"></i> ${esc(cot.vendedor_nombre)}</span>`;
  document.getElementById('det-meta').innerHTML=meta;
  document.getElementById('btn-ver').href=`ver_cotizacion.php?id=${cot.id}`;

  const visible=[...document.querySelectorAll('.client-item')].filter(t=>t.style.display!=='none');
  const pos=visible.findIndex(t=>parseInt(t.dataset.idx)===cotIdx);
  document.getElementById('nav-count').textContent=`${pos+1} de ${visible.length}`;
  document.getElementById('btn-prev').disabled=pos<=0;
  document.getElementById('btn-next').disabled=pos>=visible.length-1;

  actualizarEstado(cot);
  document.getElementById('f-fecha').value=new Date().toISOString().slice(0,16);
  document.getElementById('f-proximo').value='';
  document.getElementById('f-desc').value='';
  document.getElementById('form-msg').textContent='';
  cargarHistorial(cot.id);
}

function actualizarEstado(cot){
  const enviado=!!(cot.email_token||cot.primer_email);
  const abierto=!!cot.email_opened_at;
  const set=(id,ok,txt)=>{ const el=document.getElementById(id); el.className='status-chip '+(ok?'done':'pend'); el.querySelector('.sc-value').textContent=ok?txt:'Pendiente'; };
  set('ts-enviado',enviado,'Confirmado');
  set('ts-abierto',abierto,'Confirmado');
}

async function cargarHistorial(id){
  const loading=document.getElementById('tl-loading'), lista=document.getElementById('tl-list');
  loading.style.display='block'; lista.innerHTML=''; document.getElementById('tl-count').textContent='…';
  try{
    const res=await fetch(`API/api_seguimiento_detalle.php?id=${id}`);
    const data=await res.json();
    if(!res.ok||data.error) throw new Error(data.error||'Error');
    renderTimeline(data.historial||[]);
  }catch(err){
    lista.innerHTML=`<div style="padding:20px;text-align:center;color:var(--re);">${esc(err.message)}</div>`;
    document.getElementById('tl-count').textContent='!';
  }finally{ loading.style.display='none'; }
}

function renderTimeline(historial){
  const lista=document.getElementById('tl-list');
  document.getElementById('tl-count').textContent=historial.length;
  if(!historial.length){ lista.innerHTML='<div style="padding:20px;text-align:center;color:var(--ink3);">Sin registros.</div>'; return; }
  lista.innerHTML=historial.map(h=>{
    const tipo=h.tipo||'nota', label=LABELS[tipo]||tipo;
    const esAuto=AUTO_TIPOS.includes(tipo)||/^[📧✅❌🔄▶🚫👁🔗]/.test(h.descripcion||'');
    const cls=esAuto?'auto-item':'user-item';
    const prox=h.proximo_contacto?`<div class="tl-proximo"><i class="ti ti-calendar-event"></i> Próximo: ${h.proximo_contacto}</div>`:'';
    const auto=esAuto?'<div class="tl-auto">Registro automático</div>':'';
    return `<div class="tl-item ${cls}">
      <div class="tl-meta"><span class="tl-tag">${label}</span><span>${esc(h.usuario_nombre||'')}</span><span style="margin-left:auto;">${esc(h.tiempo_relativo||h.fecha_contacto||'')}</span></div>
      <div class="tl-desc">${esc(h.descripcion||'').replace(/\n/g,'<br>')}</div>${prox}${auto}
    </div>`;
  }).join('');
  document.getElementById('tl-scroll').scrollTop=document.getElementById('tl-scroll').scrollHeight;
}

async function guardarSeguimiento(){
  if(!cotActualId){ showToast('Selecciona una cotización.','var(--re)'); return; }
  const btn=document.getElementById('btn-save'), msg=document.getElementById('form-msg');
  const tipo=document.getElementById('f-tipo').value, desc=document.getElementById('f-desc').value.trim();
  const fech=document.getElementById('f-fecha').value, prox=document.getElementById('f-proximo').value||null;
  if(desc.length<3){ msg.textContent='Escribe una descripción.'; msg.className='form-msg err'; return; }
  btn.disabled=true; btn.innerHTML='<i class="ti ti-loader-2" style="animation:spin 1s linear infinite"></i> Guardando...'; msg.textContent='';
  try{
    const res=await fetch(`API/api_seguimiento_detalle.php?id=${cotActualId}`,{
      method:'POST', headers:{'Content-Type':'application/json'},
      body:JSON.stringify({cotizacion_id:cotActualId,tipo,descripcion:desc,fecha_contacto:fech,proximo_contacto:prox})
    });
    const data=await res.json();
    if(data.ok){
      const lista=document.getElementById('tl-list');
      if(lista.querySelector('.tl-empty')) lista.innerHTML='';
      lista.insertAdjacentHTML('beforeend', buildTlItem({tipo,descripcion:desc,usuario_nombre:data.usuario_nombre,tiempo_relativo:'hace un momento',proximo_contacto:prox}));
      document.getElementById('tl-scroll').scrollTop=document.getElementById('tl-scroll').scrollHeight;
      const cnt=document.getElementById('tl-count'); cnt.textContent=parseInt(cnt.textContent||'0')+1;
      document.getElementById('f-desc').value=''; document.getElementById('f-proximo').value='';
      document.getElementById('f-fecha').value=new Date().toISOString().slice(0,16);
      msg.textContent='✓ Guardado'; msg.className='form-msg ok';
      showToast('✓ Contacto registrado','var(--green)');
      setTimeout(()=>{ msg.textContent=''; },2000);
    }else{ msg.textContent=data.error||'Error.'; msg.className='form-msg err'; }
  }catch{ msg.textContent='Error de conexión.'; msg.className='form-msg err'; }
  finally{ btn.disabled=false; btn.innerHTML='<i class="ti ti-device-floppy"></i> Guardar'; }
}

function buildTlItem(h){
  const tipo=h.tipo||'nota', label=LABELS[tipo]||tipo;
  const esAuto=AUTO_TIPOS.includes(tipo)||/^[📧✅❌🔄▶🚫👁🔗]/.test(h.descripcion||'');
  const cls=esAuto?'auto-item':'user-item';
  const prox=h.proximo_contacto?`<div class="tl-proximo"><i class="ti ti-calendar-event"></i> Próximo: ${h.proximo_contacto}</div>`:'';
  const auto=esAuto?'<div class="tl-auto">Registro automático</div>':'';
  return `<div class="tl-item ${cls}">
    <div class="tl-meta"><span class="tl-tag">${label}</span><span>${esc(h.usuario_nombre||'')}</span><span style="margin-left:auto;">${esc(h.tiempo_relativo||h.fecha_contacto||'')}</span></div>
    <div class="tl-desc">${esc(h.descripcion||'').replace(/\n/g,'<br>')}</div>${prox}${auto}
  </div>`;
}

async function quickLog(tipo,desc){ if(!cotActualId){ showToast('Selecciona primero.','var(--re)'); return; } document.getElementById('f-tipo').value=tipo; document.getElementById('f-desc').value=desc; await guardarSeguimiento(); }

function abrirEmail(){ if(!cotActualId){ showToast('Selecciona una cotización.','var(--re)'); return; } document.getElementById('em-num-lbl').textContent=cotActualNum||'—'; document.getElementById('em-destino').value=cotActualEmail; document.getElementById('em-cc').value=''; document.getElementById('em-mensaje').value=''; document.getElementById('em-status').textContent=''; document.getElementById('em-status').className='em-status'; document.getElementById('em-btn-send').disabled=false; document.getElementById('em-btn-send').innerHTML='<i class="ti ti-send"></i> Enviar'; document.getElementById('em-backdrop').classList.add('open'); setTimeout(()=>document.getElementById('em-destino').focus(),80); }
function cerrarEmail(){ document.getElementById('em-backdrop').classList.remove('open'); }

async function enviarEmail(){
  const destino=document.getElementById('em-destino').value.trim();
  const cc=document.getElementById('em-cc').value.trim();
  const mensaje=document.getElementById('em-mensaje').value.trim();
  const status=document.getElementById('em-status'), btn=document.getElementById('em-btn-send');
  if(!destino||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(destino)){ status.textContent='Correo inválido.'; status.className='em-status err'; return; }
  btn.disabled=true; btn.innerHTML='<i class="ti ti-loader-2" style="animation:spin 1s linear infinite"></i> Enviando...'; status.textContent='';
  try{
    const res=await fetch('API/api_enviar_correo.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({cotizacion_id:cotActualId,email_destino:destino,email_cc:cc,mensaje})});
    const data=await res.json();
    if(data.ok){
      status.textContent=`✓ Enviado a ${data.email_destino}`; status.className='em-status ok'; btn.innerHTML='✓ Listo';
      showToast('📧 Correo enviado','var(--link)');
      window.NotifSound?.playSend();
      const lista=document.getElementById('tl-list');
      if(lista.querySelector('.tl-empty')) lista.innerHTML='';
      lista.insertAdjacentHTML('beforeend', buildTlItem({tipo:'email',descripcion:`📧 Cotización ${cotActualNum} enviada a ${destino}.${mensaje?' Mensaje: '+mensaje:''}`,usuario_nombre:'',tiempo_relativo:'hace un momento'}));
      document.getElementById('tl-scroll').scrollTop=document.getElementById('tl-scroll').scrollHeight;
      const cnt=document.getElementById('tl-count'); cnt.textContent=parseInt(cnt.textContent||'0')+1;
      setTimeout(cerrarEmail,2200);
    }else{ status.textContent=data.error||'Error.'; status.className='em-status err'; btn.disabled=false; btn.innerHTML='<i class="ti ti-send"></i> Enviar'; }
  }catch{ status.textContent='Error de conexión.'; status.className='em-status err'; btn.disabled=false; btn.innerHTML='<i class="ti ti-send"></i> Enviar'; }
}

document.addEventListener('keydown',e=>{
  if(['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) return;
  if(e.key==='ArrowRight'){ e.preventDefault(); navegarNext(); }
  if(e.key==='ArrowLeft'){ e.preventDefault(); navegarPrev(); }
  if(e.key==='Escape') cerrarEmail();
  if(e.key==='l') quickLog('llamada','📞 Llamada realizada — ');
  if(e.key==='w') quickLog('whatsapp','💬 WhatsApp enviado — ');
  if(e.key==='e') abrirEmail();
});

if(COTS.length>0){
  const inicial=document.querySelector(`.client-item[data-idx="${cotIdx}"]`);
  if(inicial){ cargarCot(COTS[cotIdx]); inicial.classList.add('active'); }
  else cargarCot(COTS[0]);
}
</script>
</body>
</html>