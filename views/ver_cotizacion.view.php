<?php
/**
 * views/ver_cotizacion.view.php — Visualización de una cotización en modo solo lectura.
 *
 * Esta vista reutiliza EXACTAMENTE el mismo marcado, clases CSS y tipografía que
 * views/cotizacion.view.php (el editor), de modo que lo que se ve aquí y lo que
 * se exporta a PDF (window.print()) sea idéntico, carácter por carácter, a lo que
 * se ve y se exporta desde el editor. Solo cambian dos cosas respecto al editor:
 *   1. No hay <form> ni inputs editables: los valores se imprimen como texto.
 *   2. La toolbar superior tiene botones de navegación (Volver / Editar / Exportar PDF)
 *      en vez de Guardar / Lista de precios.
 *
 * Variables disponibles (inyectadas por VerCotizacionController):
 *   $ctrl->id                int     — ID de la cotización
 *   $ctrl->cotizacion        array   — fila completa de la BD
 *   $ctrl->items             array   — productos decodificados del JSON (ya con extendido/subtotal calculados)
 *   $ctrl->empresa           array   — datos de empresa con fallbacks
 *   $ctrl->condiciones       array   — t_entrega, v_servicios, cond_pago, l_entrega
 *   $ctrl->firma             array   — nombre, puesto, tel
 *   $ctrl->numeroCotizacion  string
 *   $ctrl->fechaTexto        string  — fecha formateada en español
 *   $ctrl->monedaCode        string  — 'USD', 'MXN', 'EUR'
 *   $ctrl->monedaSym         string  — '$' o '€'
 *   $ctrl->monedaLabel       string  — 'USD', 'MXN', 'EUR'
 *   $ctrl->tipoCambio        float
 *   $ctrl->aplicaIva         bool
 *   $ctrl->subtotal          float
 *   $ctrl->iva               float
 *   $ctrl->total             float
 *   $ctrl->vigenciaDias      string
 *   $ctrl->estadoActual      string
 *   $ctrl->esAdminViendo     bool    — admin viendo cotización ajena
 *   $auth->esAdmin()         bool
 */

/** Formatea un número como moneda, igual que el JS fmt() del editor. */
function fmt(float $n, string $sym, string $code): string
{
    return $sym . number_format($n, 2, '.', ',') . ' ' . $code;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cotización <?= htmlspecialchars($ctrl->numeroCotizacion) ?> — Fortress8</title>
<link rel="icon" href="assets/img/favicon.png" type="image/png">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --or:        #D95A00;
    --or-h:      #b84d00;
    --or-dim:    rgba(217,90,0,.12);
    --or-ring:   rgba(217,90,0,.25);
    --bg:        #0c0c0c;
    --s1:        #111111;
    --s2:        #161616;
    --s3:        #1c1c1c;
    --s4:        #222222;
    --b0:        #1e1e1e;
    --b1:        #2a2a2a;
    --b2:        #363636;
    --t0:        #f0f0f0;
    --t1:        #a0a0a0;
    --t2:        #555555;
    --t-or:      #f97316;
    --green:     #16a34a;
    --green-h:   #15803d;
    --green-dim: rgba(22,163,74,.12);
    --r:  6px;
    --r2: 10px;
    --r3: 14px;
}
html { font-size: 14px; }
body {
    font-family: 'Inter', system-ui, sans-serif;
    background: var(--bg);
    color: var(--t0);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    -webkit-font-smoothing: antialiased;
}

/* ═══════════════════════════════════════════════════
   TOOLBAR
═══════════════════════════════════════════════════ */
#toolbar {
    position: fixed;
    inset: 0 0 auto 0;
    height: 48px;
    z-index: 300;
    background: var(--s1);
    border-bottom: 1px solid var(--b0);
    display: flex;
    align-items: center;
    padding: 0 14px;
    gap: 10px;
}
.tb-brand { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.tb-mark {
    width: 26px; height: 26px; background: var(--or);
    border-radius: 6px; display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 600; color: #fff;
}
.tb-name { font-size: 12px; font-weight: 500; color: var(--t1); white-space: nowrap; }
.tb-name b { color: var(--t0); font-weight: 600; }
.tb-sep { width: 1px; height: 20px; background: var(--b1); flex-shrink: 0; }
.tb-center { flex: 1; display: flex; align-items: center; justify-content: center; }
.cot-pill {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--s2); border: 1px solid var(--b0); border-radius: 20px;
    padding: 4px 12px; font-size: 11px; color: var(--t1);
    font-family: 'JetBrains Mono', monospace;
}
.cot-pill .num { color: var(--or); font-weight: 500; }
.cot-pill .dot { color: var(--b2); font-size: 9px; }
.tb-actions { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }

/* Button system */
.btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 11px; border-radius: var(--r); border: 1px solid transparent;
    cursor: pointer; font-size: 12px; font-weight: 500;
    font-family: 'Inter', sans-serif; letter-spacing: .01em;
    transition: background .12s, border-color .12s, color .12s, opacity .12s;
    white-space: nowrap; line-height: 1;
}
.btn:active { opacity: .8; }
.btn svg { width: 13px; height: 13px; flex-shrink: 0; }
.btn-ghost  { background: var(--s2); border-color: var(--b0); color: var(--t1); }
.btn-ghost:hover { background: var(--s3); border-color: var(--b1); color: var(--t0); }
.btn-save   { background: var(--green-dim); border-color: rgba(22,163,74,.3); color: #4ade80; }
.btn-save:hover  { background: var(--green); border-color: var(--green); color: #fff; }
.btn-export { background: var(--or-dim); border-color: var(--or-ring); color: var(--t-or); }
.btn-export:hover { background: var(--or); border-color: var(--or); color: #fff; }
.btn-admin  { background: transparent; border-color: var(--b1); color: var(--t2); }
.btn-admin:hover { border-color: var(--b2); color: var(--t1); }
.estado-tag {
    display: inline-block; padding: 2px 6px; border-radius: 4px;
    font-size: 10px; font-weight: 500;
    background: var(--or-dim); border: 1px solid var(--or-ring); color: var(--t-or);
}

/* Hint bar */
#hint {
    position: fixed; top: 48px; left: 0; right: 0; z-index: 299;
    background: var(--s1); border-bottom: 1px solid var(--b0);
    display: flex; align-items: center; gap: 7px;
    padding: 4px 16px; font-size: 10px; color: var(--t2);
    pointer-events: none; user-select: none;
}
#hint::before {
    content: ''; width: 5px; height: 5px; background: var(--or);
    border-radius: 50%; flex-shrink: 0;
}

/* ═══════════════════════════════════════════════════
   LAYOUT
═══════════════════════════════════════════════════ */
#layout {
    display: flex; flex: 1;
    padding-top: 74px;
    min-height: 100vh;
    position: relative;
}

/* ═══════════════════════════════════════════════════
   PRICE PANEL
═══════════════════════════════════════════════════ */
#price-panel {
    width: 272px; min-width: 272px;
    flex-shrink: 0; background: var(--s1);
    border-right: 1px solid var(--b0);
    display: flex; flex-direction: column;
    position: sticky; top: 74px;
    height: calc(100vh - 74px);
    overflow: hidden;
    transition: width .22s ease, min-width .22s ease, opacity .18s;
    z-index: 100;
}
#price-panel.hidden { width: 0; min-width: 0; opacity: 0; pointer-events: none; border: none; }
.pp-head {
    padding: 11px 12px 9px; background: var(--s2);
    border-bottom: 1px solid var(--b0); flex-shrink: 0;
    display: flex; flex-direction: column; gap: 8px;
}
.pp-title-row { display: flex; align-items: center; gap: 7px; }
.pp-icon { color: var(--t2); display: flex; align-items: center; }
.pp-icon svg { width: 14px; height: 14px; }
.pp-title { font-size: 11px; font-weight: 600; color: var(--t0); flex: 1; letter-spacing: .02em; }
.pp-count {
    background: var(--or); color: #fff; font-size: 10px; font-weight: 600;
    padding: 1px 7px; border-radius: 10px;
    font-family: 'JetBrains Mono', monospace;
}
.btn-refresh {
    display: inline-flex; align-items: center; gap: 4px;
    background: transparent; border: 1px solid var(--b1); border-radius: var(--r);
    padding: 3px 7px; font-size: 10px; font-weight: 500; color: var(--t2);
    cursor: pointer; font-family: 'Inter', sans-serif;
    transition: border-color .12s, color .12s; white-space: nowrap; flex-shrink: 0;
}
.btn-refresh:hover { border-color: var(--or); color: var(--or); }
.btn-refresh svg { width: 10px; height: 10px; }
.ver-bar {
    display: flex; align-items: center; gap: 7px;
    background: var(--s3); border: 1px solid var(--b0); border-radius: var(--r);
    padding: 5px 9px;
}
.ver-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--or); flex-shrink: 0; }
.ver-name { flex: 1; font-size: 10px; color: var(--t1); font-weight: 500; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.ver-date { font-size: 10px; color: var(--t2); font-family: 'JetBrains Mono', monospace; white-space: nowrap; flex-shrink: 0; }
.pp-search { padding: 8px 10px 5px; flex-shrink: 0; }
.search-wrap { position: relative; }
.search-icon {
    position: absolute; left: 8px; top: 50%;
    transform: translateY(-50%); color: var(--t2);
    display: flex; align-items: center; pointer-events: none;
}
.search-icon svg { width: 12px; height: 12px; }
#search-input {
    width: 100%; background: var(--s3); border: 1px solid var(--b0);
    border-radius: var(--r); padding: 6px 8px 6px 27px;
    font-size: 12px; color: var(--t0); outline: none; font-family: 'Inter', sans-serif;
    transition: border-color .12s;
}
#search-input::placeholder { color: var(--t2); }
#search-input:focus { border-color: var(--or-ring); }
.pp-cats {
    padding: 0 10px 7px; display: flex; flex-wrap: wrap; gap: 3px;
    flex-shrink: 0; border-bottom: 1px solid var(--b0);
    max-height: 68px; overflow-y: auto;
    scrollbar-width: thin; scrollbar-color: var(--b1) transparent;
}
.cat-btn {
    font-size: 10px; font-weight: 500; padding: 3px 8px; border-radius: 20px;
    border: 1px solid var(--b0); background: transparent; color: var(--t2);
    cursor: pointer; white-space: nowrap;
    transition: border-color .12s, color .12s, background .12s;
    font-family: 'Inter', sans-serif;
}
.cat-btn:hover { border-color: var(--b2); color: var(--t1); }
.cat-btn.active { border-color: var(--or-ring); background: var(--or-dim); color: var(--t-or); font-weight: 600; }
.pp-results { padding: 5px 12px 3px; font-size: 10px; color: var(--t2); flex-shrink: 0; font-family: 'JetBrains Mono', monospace; }
.products-list { flex: 1; overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--b1) transparent; }
.product-item {
    padding: 8px 11px; border-bottom: 1px solid var(--b0);
    display: flex; gap: 8px; align-items: flex-start;
    transition: background .1s; cursor: default;
}
.product-item:hover { background: var(--s2); }
.pi-info { flex: 1; min-width: 0; }
.pi-sku { font-size: 11px; font-weight: 500; color: var(--t0); font-family: 'JetBrains Mono', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pi-desc { font-size: 10px; color: var(--t2); line-height: 1.4; margin-top: 2px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.pi-price { font-size: 10px; color: var(--t-or); font-weight: 500; margin-top: 3px; font-family: 'JetBrains Mono', monospace; }
.btn-add-item {
    width: 22px; height: 22px; border-radius: 50%;
    background: transparent; border: 1px solid var(--b2); color: var(--t2);
    cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px; line-height: 1;
    transition: background .1s, color .1s, border-color .1s;
}
.btn-add-item:hover { background: var(--or); border-color: var(--or); color: #fff; }
.pp-foot {
    padding: 7px 12px; font-size: 10px; color: var(--t2);
    border-top: 1px solid var(--b0); background: var(--s2);
    display: flex; justify-content: space-between; align-items: center;
    flex-shrink: 0; font-family: 'JetBrains Mono', monospace;
}
.pp-foot a {
    color: var(--t1); text-decoration: none; font-weight: 500;
    font-family: 'Inter', sans-serif; font-size: 10px;
    display: flex; align-items: center; gap: 3px; transition: color .12s;
}
.pp-foot a svg { width: 9px; height: 9px; }
.pp-foot a:hover { color: var(--or); }

/* ═══════════════════════════════════════════════════
   SHEET WRAP & A4
═══════════════════════════════════════════════════ */
#sheet-wrap {
    flex: 1; display: flex; justify-content: center;
    overflow-x: auto; padding: 28px 36px 48px;
    background-image: radial-gradient(circle, var(--b1) 1px, transparent 1px);
    background-size: 26px 26px; background-color: var(--bg);
}
#a4 {
    background: #fff; width: 794px; min-width: 794px;
    box-shadow: 0 0 0 1px rgba(0,0,0,.4), 0 2px 4px rgba(0,0,0,.3),
                0 12px 40px rgba(0,0,0,.55), 0 32px 64px rgba(0,0,0,.25);
    display: flex; flex-direction: column; flex-shrink: 0;
}

/* A4 Header */
.a4-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 30px 30px 22px; border-bottom: 2.5px solid #D95A00; background: #fff;
}
.cot-label { font-size: 10px; font-weight: 700; color: #D95A00; text-transform: uppercase; letter-spacing: .12em; margin-top: 7px; }
.cot-num   { font-size: 17px; font-weight: 700; color: #111; }
.cot-date  { font-size: 11px; color: #6b7280; }
.header-right { display: flex; flex-direction: column; align-items: flex-end; gap: 1px; line-height: 1.6; }

/* Editable fields */
.editable {
    background: transparent; border: none; border-bottom: 1px solid transparent;
    outline: none; padding: 1px 2px; color: #111827; font-family: inherit;
    transition: border-color .12s, background .12s; border-radius: 2px;
}
.editable:hover { border-bottom-color: #e5e7eb; }
.editable:focus { border-bottom-color: #D95A00; background: rgba(217,90,0,.04); }
.editable::placeholder { color: #e5e7eb; }
.editable.sm   { font-size: 12px; } .editable.xs  { font-size: 11px; }
.editable.bold { font-weight: 700; } .editable.right { text-align: right; }
.editable.center { text-align: center; }
.w72 { width: 342px; } .w56 { width: 220px; } .w48 { width: 192px; }
.w40 { width: 160px; } .w32 { width: 128px; } .w28 { width: 72px; }
.w20 { width: 80px;  } .w16 { width: 64px;  } .w10 { width: 42px; }

/* A4 Body */
.a4-body { padding: 22px 30px 30px; flex: 1; display: flex; flex-direction: column; gap: 20px; }
.sec-title {
    font-size: 9px; font-weight: 700; color: #D95A00; text-transform: uppercase;
    letter-spacing: .1em; margin-bottom: 6px; display: flex; align-items: center; gap: 7px;
}
.sec-title::after { content: ''; flex: 1; height: 1px; background: #fce8d8; }

/* Client fields */
.client-fields { border: 1px solid #e5e7eb; border-radius: 3px; overflow: hidden; }
.field-row {
    display: grid; grid-template-columns: 110px 1fr;
    align-items: center; border-bottom: 1px solid #f3f3f3;
}
.field-row:last-child { border-bottom: none; }
.field-label {
    background: #f9fafb; border-right: 1px solid #e5e7eb;
    padding: 6px 10px; font-size: 9px; font-weight: 700;
    color: #374151; text-transform: uppercase; letter-spacing: .06em;
}
.field-input {
    padding: 6px 10px; font-size: 13px; color: #111827;
    background: transparent; border: none; outline: none; width: 100%;
    font-family: inherit; transition: background .1s;
}
.field-input:focus { background: rgba(217,90,0,.03); }
.field-input::placeholder { color: #e5e7eb; }

/* Items table */
.items-table { width: 100%; border-collapse: collapse; font-size: 12px; table-layout: fixed; }
.items-table col:nth-child(1) { width: 42px; }
.items-table col:nth-child(2) { width: 88px; }
.items-table col:nth-child(3) { width: 46px; }
.items-table col:nth-child(5) { width: 78px; }
.items-table col:nth-child(6) { width: 78px; }
.items-table col:nth-child(7) { width: 70px; }
.items-table col:nth-child(8) { width: 86px; }
.items-table col:nth-child(9) { width: 22px; }
.items-table th {
    background: #D95A00; color: #fff; font-size: 9px; font-weight: 600;
    padding: 7px 5px; text-align: center; border: 1px solid #b84d00;
    letter-spacing: .04em; text-transform: uppercase; white-space: nowrap;
}
.items-table td { border: 1px solid #e8e8e8; padding: 0; vertical-align: top; }
.items-table tbody tr:nth-child(even) td { background: #fafafa; }
.items-table tbody tr:nth-child(odd)  td { background: #fff; }
.items-table tbody tr:hover td { background: #fff9f6 !important; }
.cell-input {
    width: 100%; background: transparent; border: none; outline: none;
    padding: 5px 4px; font-size: 11px; font-family: inherit;
    color: #111827; transition: background .1s;
}
.cell-input:focus { background: rgba(217,90,0,.05); }
.cell-input::placeholder { color: #e8e8e8; }
.cell-input.center { text-align: center; }
.cell-input.right  { text-align: right; font-variant-numeric: tabular-nums; }
.cell-input.xs     { font-size: 10px; }
.cell-input.desc-area {
    resize: none; min-height: 40px; height: auto;
    overflow: hidden; line-height: 1.45;
}
.cell-display {
    display: block; padding: 6px 5px; text-align: right;
    font-size: 11px; font-weight: 500; color: #111827;
    font-variant-numeric: tabular-nums; white-space: nowrap;
}
.cell-display.muted { color: #d1d5db; }
.btn-remove-row {
    background: transparent; border: none; cursor: pointer;
    color: #fca5a5; font-size: 15px; font-weight: 600;
    padding: 2px 4px; line-height: 1;
    display: flex; align-items: center; justify-content: center;
    width: 100%; height: 100%; min-height: 40px; transition: color .1s;
}
.btn-remove-row:hover { color: #dc2626; }
.tfoot-add td { padding: 5px; text-align: center; border: 1px solid #e8e8e8; background: #fafafa; }
.btn-add-row {
    background: transparent; border: 1px dashed #D95A00; border-radius: 3px;
    cursor: pointer; color: #D95A00; font-size: 10px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .06em; padding: 3px 12px;
    transition: background .1s, color .1s;
}
.btn-add-row:hover { background: #D95A00; color: #fff; }

/* Totales */
.totals-block { display: flex; flex-direction: column; align-items: flex-end; gap: 1px; margin-top: 10px; }
.totals-row { display: flex; align-items: stretch; min-width: 260px; }
.totals-row .lbl {
    background: #D95A00; color: #fff; font-size: 10px; font-weight: 600;
    padding: 5px 12px; text-align: right; flex: 1;
    border: 1px solid #b84d00; white-space: nowrap;
}
.totals-row .val {
    font-size: 11px; font-weight: 600; color: #111827;
    padding: 5px 10px; text-align: right; min-width: 125px;
    border: 1px solid #e0e0e0; background: #fff; font-variant-numeric: tabular-nums;
}
.totals-row.big .lbl { background: #b84d00; font-size: 12px; font-weight: 700; border: 2px solid #D95A00; padding: 8px 12px; }
.totals-row.big .val { font-size: 14px; font-weight: 700; color: #D95A00; border: 2px solid #D95A00; background: #fff9f6; padding: 8px 10px; }
.btn-add-iva {
    background: transparent; border: none; cursor: pointer; color: #D95A00;
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .05em; transition: color .1s; padding: 3px 0;
}
.btn-add-iva:hover { color: #b84d00; }
.iva-pct-input {
    width: 34px; text-align: center; background: rgba(255,255,255,.18);
    border: none; border-bottom: 1px solid rgba(255,255,255,.65);
    color: #fff; font-size: 10px; font-weight: 600; outline: none;
    border-radius: 2px; padding: 1px 2px;
}
.btn-iva-remove {
    background: transparent; border: none; color: rgba(255,255,255,.65);
    font-size: 9px; text-decoration: underline; cursor: pointer; padding: 0; margin-left: 4px;
}
.btn-iva-remove:hover { color: #fff; }

/* Info block (moneda + vigencia) */
.info-block {
    font-size: 11px; color: #4b5563;
    display: flex; flex-direction: column; gap: 6px;
    background: #f9fafb; border: 1px solid #f0f0f0; border-radius: 3px; padding: 9px 12px;
}
.info-line { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
.currency-select {
    border: 1px solid #d1d5db; border-radius: 4px; padding: 3px 6px;
    font-size: 11px; background: #fff; color: #111827; cursor: pointer;
    font-family: 'Inter', sans-serif; outline: none; transition: border-color .12s;
}
.currency-select:focus { border-color: #D95A00; }
.rate-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.rate-wrap {
    display: flex; align-items: center; gap: 4px;
    background: #fff; border: 1px solid #d1d5db; border-radius: 4px;
    padding: 3px 7px; transition: border-color .12s;
}
.rate-wrap:focus-within { border-color: #D95A00; }
.rate-input {
    width: 68px; border: none; background: transparent;
    font-size: 12px; font-weight: 600; color: #111827; outline: none;
    text-align: right; font-family: 'JetBrains Mono', monospace;
}
.rate-cur-lbl { font-size: 11px; color: #374151; font-weight: 600; }
.rate-badge { font-size: 9px; padding: 2px 6px; border-radius: 8px; font-weight: 600; }
.rate-badge.live   { background: #d1fae5; color: #065f46; }
.rate-badge.custom { background: #fef3c7; color: #92400e; }
.rate-reset {
    font-size: 9px; border: 1px solid #d1d5db; background: transparent;
    border-radius: 4px; padding: 2px 6px; color: #6b7280; cursor: pointer;
    font-family: 'Inter', sans-serif; transition: border-color .12s, color .12s;
}
.rate-reset:hover { border-color: #D95A00; color: #D95A00; }

/* Condiciones */
.cond-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 9px 22px; }
.cond-row { display: flex; align-items: center; gap: 7px; padding-bottom: 5px; border-bottom: 1px solid #f5f5f5; }
.cond-label { font-size: 10px; font-weight: 700; color: #374151; white-space: nowrap; min-width: 128px; }
.cond-input {
    flex: 1; border: none; border-bottom: 1px solid #e5e7eb;
    background: transparent; font-size: 11px; color: #111827; outline: none;
    padding: 2px 0; font-family: inherit; transition: border-color .12s;
}
.cond-input:focus { border-bottom-color: #D95A00; }
.cond-input::placeholder { color: #e5e7eb; }

/* Firma */
.firma-section {
    display: flex; justify-content: space-between; align-items: flex-end;
    padding-top: 18px; border-top: 1px solid #f0f0f0; gap: 14px;
}
.firma-block { display: flex; flex-direction: column; align-items: center; }
.firma-block.left-block { align-items: flex-start; }
.upload-area {
    position: relative; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    cursor: pointer; border-radius: 3px; transition: background .1s; overflow: hidden;
}
.upload-area.firma-upload { width: 196px; height: 66px; border: 1px dashed #D95A00; background: #fff9f6; }
.upload-area.sello-upload { width: 156px; height: 96px; border: 1px dashed #D95A00; background: #fff9f6; }
.upload-area.has-image { border-color: #e5e7eb; background: transparent; }
.upload-area img { max-width: 100%; max-height: 100%; object-fit: contain; }
.upload-hint { display: flex; flex-direction: column; align-items: center; gap: 2px; text-align: center; padding: 7px; }
.upload-hint .icon  { color: #D95A00; font-size: 16px; }
.upload-hint .label { font-size: 9px; font-weight: 700; color: #D95A00; text-transform: uppercase; letter-spacing: .05em; }
.upload-hint .sub   { font-size: 9px; color: #9ca3af; }
.upload-change { position: absolute; bottom: 2px; right: 4px; font-size: 8px; color: #9ca3af; background: rgba(255,255,255,.85); padding: 1px 4px; border-radius: 2px; }
.upload-input { display: none; }
.firma-line  { width: 196px; border-bottom: 1px solid #374151; margin: 5px 0 3px; }
.sello-line  { width: 156px; border-bottom: 1px solid #374151; margin: 5px 0 3px; }
.firma-sublabel { font-size: 9px; color: #9ca3af; text-align: center; font-style: italic; }
.sello-sublabel { font-size: 9px; color: #9ca3af; text-align: center; font-style: italic; width: 156px; }

/* Modal admin */
.modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(3px); z-index: 1000; align-items: center; justify-content: center; }
.modal-backdrop.open { display: flex; }
.modal { background: var(--s1); border: 1px solid var(--b1); border-radius: var(--r3); width: 360px; max-width: 92vw; padding: 20px; box-shadow: 0 24px 48px rgba(0,0,0,.5); }
.modal-title { font-size: 13px; font-weight: 600; color: var(--t0); margin-bottom: 3px; }
.modal-sub   { font-size: 11px; color: var(--t1); margin-bottom: 14px; }
.modal-label { display: block; font-size: 10px; font-weight: 600; color: var(--t1); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }
.modal-select, .modal-textarea {
    width: 100%; background: var(--s3); border: 1px solid var(--b1); border-radius: var(--r);
    color: var(--t0); font-family: 'Inter', sans-serif; font-size: 13px;
    padding: 8px 10px; outline: none; transition: border-color .12s;
}
.modal-select:focus, .modal-textarea:focus { border-color: var(--or-ring); }
.modal-textarea { resize: vertical; min-height: 66px; margin-top: 12px; }
.modal-hint { font-size: 10px; color: var(--t1); margin-top: 8px; line-height: 1.55; }
.modal-hint strong { color: var(--t-or); }
.modal-actions { display: flex; justify-content: flex-end; gap: 7px; margin-top: 16px; }
.btn-modal-ok {
    background: var(--or); color: #fff; border: none; border-radius: 20px;
    padding: 7px 18px; font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: 'Inter', sans-serif; transition: background .12s;
}
.btn-modal-ok:hover { background: var(--or-h); }

/* Modal de guardado exitoso */
.modal-saved-icon {
    width: 44px; height: 44px; border-radius: 50%; background: var(--green-dim);
    display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;
}
.modal-saved-icon svg { width: 22px; height: 22px; color: #4ade80; }
.modal-saved-title { font-size: 14px; font-weight: 600; color: var(--t0); text-align: center; margin-bottom: 4px; }
.modal-saved-sub   { font-size: 11px; color: var(--t1); text-align: center; margin-bottom: 18px; line-height: 1.5; }
.modal-saved-sub strong { color: var(--t-or); }
.modal-saved-actions { display: flex; flex-direction: column; gap: 8px; }
.btn-pdf-now {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    background: var(--or); color: #fff; border: none; border-radius: var(--r2);
    padding: 11px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
    font-family: 'Inter', sans-serif; transition: background .12s;
}
.btn-pdf-now:hover { background: var(--or-h); }
.btn-pdf-now svg { width: 15px; height: 15px; }
.btn-saved-close {
    background: transparent; color: var(--t1); border: 1px solid var(--b1); border-radius: var(--r2);
    padding: 9px 16px; font-size: 12px; font-weight: 500; cursor: pointer;
    font-family: 'Inter', sans-serif; transition: all .12s;
}
.btn-saved-close:hover { background: var(--s3); color: var(--t0); }

/* Toast de notificación */
#toast-container {
    position: fixed; top: 60px; right: 16px; z-index: 2000;
    display: flex; flex-direction: column; gap: 8px; pointer-events: none;
}
.toast {
    display: flex; align-items: flex-start; gap: 10px;
    background: var(--s1); border: 1px solid var(--b1); border-left: 3px solid var(--or);
    border-radius: var(--r2); padding: 11px 14px; width: 300px; max-width: 88vw;
    box-shadow: 0 12px 28px rgba(0,0,0,.45);
    opacity: 0; transform: translateX(16px); transition: opacity .22s ease, transform .22s ease;
    pointer-events: auto;
}
.toast.show { opacity: 1; transform: translateX(0); }
.toast.toast-success { border-left-color: #4ade80; }
.toast.toast-info    { border-left-color: var(--or); }
.toast-icon { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
.toast.toast-success .toast-icon { color: #4ade80; }
.toast.toast-info .toast-icon { color: var(--t-or); }
.toast-body { flex: 1; min-width: 0; }
.toast-title { font-size: 12px; font-weight: 600; color: var(--t0); margin-bottom: 2px; }
.toast-msg   { font-size: 11px; color: var(--t1); line-height: 1.4; }
.toast-close { background: none; border: none; color: var(--t2); cursor: pointer; padding: 0; line-height: 1; flex-shrink: 0; }
.toast-close:hover { color: var(--t1); }

/* ═══════════════════════════════════════════════════
   PRINT
═══════════════════════════════════════════════════ */
@media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { background: #fff; }
    #toolbar, #hint, #price-panel, .btn-remove-row,
    .tfoot-add, #no-iva-row, .btn-iva-remove,
    .rate-reset, #rate-row, .modal-backdrop, #toast-container { display: none !important; }
    #layout { padding-top: 0; }
    #sheet-wrap { padding: 0; background: #fff; background-image: none; }
    #a4 { box-shadow: none; width: 210mm; min-width: 0; }
    .a4-header { border-bottom: 2px solid #D95A00 !important; }
    .items-table th   { background: #D95A00 !important; color: #fff !important; }
    .totals-row .lbl  { background: #D95A00 !important; color: #fff !important; }
    .totals-row.big .lbl { background: #b84d00 !important; border: 2px solid #D95A00 !important; }
    .totals-row.big .val { color: #D95A00 !important; border: 2px solid #D95A00 !important; background: #fff9f6 !important; }
    .field-label  { background: #f9fafb !important; }
    .info-block   { background: #f9fafb !important; border: 1px solid #f0f0f0 !important; }
    .currency-select { border: none !important; background: transparent !important; appearance: none; }
    .editable, .field-input, .cell-input, .cond-input { border: none !important; background: transparent !important; }
    .editable::placeholder, .field-input::placeholder, .cell-input::placeholder, .cond-input::placeholder { color: transparent !important; }
    .upload-area { border: none !important; background: transparent !important; }
    .upload-hint { display: none !important; }

    /* ── Header A4: siempre en fila horizontal al imprimir ── */
    .a4-header {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        padding: 30px 30px 22px !important;
    }
    .header-right {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-end !important;
        text-align: right !important;
        gap: 1px !important;
    }
    .header-right input.editable {
        text-align: right !important;
        width: auto !important;
        max-width: 320px !important;
    }

    /* ── Condiciones comerciales: siempre en 2 columnas al imprimir ── */
    .cond-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 9px 22px !important;
    }
    .cond-row { display: flex !important; align-items: center !important; }
    .cond-label { white-space: nowrap !important; min-width: 128px !important; }

    /* ── Firma y sello: siempre en fila horizontal al imprimir ── */
    .firma-section {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: flex-end !important;
        padding-top: 18px !important;
        border-top: 1px solid #f0f0f0 !important;
        gap: 14px !important;
    }
    .firma-block { display: flex !important; flex-direction: column !important; align-items: center !important; }
    .firma-block.left-block { align-items: flex-start !important; }
    .firma-line  { width: 196px !important; }
    .sello-line  { width: 156px !important; }
    .upload-area.firma-upload { width: 196px !important; height: 66px !important; }
    .upload-area.sello-upload { width: 156px !important; height: 96px !important; }

    @page { size: A4 portrait; margin: 0; }
}

/* ═══════════════════════════════════════════════════
   RESPONSIVE — TABLET (≤ 1024px)
═══════════════════════════════════════════════════ */
@media (max-width: 1024px) {
    /* Panel ocultado por defecto; solo aparece como drawer */
    #price-panel {
        position: fixed;
        left: 0; top: 0; bottom: 0;
        width: 280px !important;
        min-width: 280px !important;
        z-index: 400;
        box-shadow: 4px 0 24px rgba(0,0,0,.5);
        transform: translateX(-100%);
        transition: transform .25s ease, opacity .2s;
        opacity: 1;
    }
    #price-panel.panel-open {
        transform: translateX(0);
    }
    #price-panel.hidden {
        width: 280px !important;
        min-width: 280px !important;
        opacity: 1;
        pointer-events: none;
        border-right: 1px solid var(--b0);
        transform: translateX(-100%);
    }

    /* Overlay para cerrar el drawer */
    #panel-overlay {
        display: none;
        position: fixed; inset: 0; z-index: 399;
        background: rgba(0,0,0,.5);
        backdrop-filter: blur(2px);
    }
    #panel-overlay.visible { display: block; }

    /* El A4 ocupa todo el ancho */
    #sheet-wrap { padding: 16px 12px 48px; }
    #a4 { width: 100%; min-width: 0; }

    /* Header A4: columna en tablet */
    .a4-header { flex-direction: column; gap: 16px; padding: 20px 20px 16px; }
    .header-right { align-items: flex-start; }
    .w72, .w56, .w48, .w40 { width: 100%; max-width: 320px; }

    /* Condiciones en 1 columna */
    .cond-grid { grid-template-columns: 1fr; gap: 8px; }

    /* Firma centrada */
    .firma-section { flex-direction: column; align-items: center; gap: 24px; }
    .firma-block.left-block { align-items: center; }

    /* Toolbar: ocultar texto largo */
    .tb-name { display: none; }
    .cot-pill .dot, .cot-pill span:last-child { display: none; }

    /* Hint bar: texto reducido */
    #hint { font-size: 9px; }
}

/* ═══════════════════════════════════════════════════
   RESPONSIVE — MÓVIL (≤ 640px)
═══════════════════════════════════════════════════ */
@media (max-width: 640px) {
    /* Toolbar compacto */
    #toolbar { height: 52px; padding: 0 10px; gap: 6px; }
    .tb-sep  { display: none; }
    .tb-center { display: none; } /* número de cotización se mueve al A4 */
    .btn { padding: 5px 8px; font-size: 11px; }
    .btn svg { width: 14px; height: 14px; }
    /* Solo icono en botones secundarios */
    .btn-ghost span, .btn-admin span { display: none; }

    /* Layout sin padding superior extra */
    #layout { padding-top: 60px; }
    #hint   { display: none; }  /* hint sobra en móvil */

    /* A4: fondo plano, sin sombra, scroll horizontal libre */
    #sheet-wrap {
        padding: 8px 0 60px;
        background-image: none;
        background-color: var(--bg);
        overflow-x: auto;
    }
    #a4 {
        width: 100%;
        min-width: 360px;
        box-shadow: none;
        border-radius: 0;
    }

    /* Header A4: stack vertical limpio */
    .a4-header { flex-direction: column; padding: 16px; gap: 14px; }
    .header-right { align-items: flex-start; }

    /* Campos editables: ancho completo */
    .w72, .w56, .w48, .w40, .w32, .w28, .w20, .w16 { width: 100% !important; }

    /* Body A4 */
    .a4-body { padding: 14px 16px 24px; gap: 16px; }

    /* Tabla de items: scroll horizontal propio */
    .items-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .items-table { min-width: 560px; }

    /* Bloque moneda + totales: stack vertical */
    .footer-flex { flex-direction: column !important; gap: 14px !important; }
    .info-block  { max-width: 100% !important; }
    .totals-block { align-items: stretch; }
    .totals-row { min-width: 0; }

    /* Condiciones: 1 columna */
    .cond-grid { grid-template-columns: 1fr; }
    .cond-label { min-width: 0; white-space: normal; }

    /* Firma: centrada y reducida */
    .firma-section { flex-direction: column; align-items: center; gap: 20px; padding-top: 14px; }
    .firma-block.left-block { align-items: center; }
    .upload-area.firma-upload { width: 160px; height: 56px; }
    .upload-area.sello-upload { width: 120px; height: 76px; }
    .firma-line { width: 160px; }
    .sello-line { width: 120px; }

    /* Modal a pantalla completa en móvil */
    .modal { width: 100%; max-width: 100%; border-radius: var(--r2) var(--r2) 0 0; margin-top: auto; }
    .modal-backdrop.open { align-items: flex-end; }
}

/* ═══════════════════════════════════════════════════
   EXTRA — solo para el modo de visualización (no existen en el editor)
═══════════════════════════════════════════════════ */
.estado-tag-view {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 20px; padding: 3px 10px 3px 8px;
    font-size: 10px; font-weight: 600; letter-spacing: .02em;
    border: 1px solid transparent; white-space: nowrap;
}
.estado-tag-view .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.estado-tag-view.estado-pendiente { background: rgba(245,158,11,.12); color: #f59e0b; border-color: rgba(245,158,11,.3); }
.estado-tag-view.estado-pendiente .dot { background: #f59e0b; }
.estado-tag-view.estado-aprobada  { background: rgba(5,150,105,.14); color: #10b981; border-color: rgba(5,150,105,.32); }
.estado-tag-view.estado-aprobada .dot { background: #10b981; }
.estado-tag-view.estado-rechazada { background: rgba(239,68,68,.12); color: #ef4444; border-color: rgba(239,68,68,.3); }
.estado-tag-view.estado-rechazada .dot { background: #ef4444; }
@media print { .estado-tag-view { display: none !important; } }

/* Aviso admin viendo cotización ajena (mismo patrón visual que el resto del sistema) */
.admin-warning-view {
    background-color: #fef3c7; color: #92400e;
    text-align: center; padding: 6px;
    font-size: 12px; font-weight: 500;
    border-bottom: 1px solid #fde68a;
    position: fixed; top: 48px; left: 0; right: 0; z-index: 299;
}
.admin-warning-view a { color: #92400e; text-decoration: underline; }
@media print { .admin-warning-view { display: none !important; } }
body.has-admin-warning #layout { padding-top: 106px; }
@media (max-width: 640px) {
    .admin-warning-view { top: 52px; font-size: 11px; padding: 5px; }
    body.has-admin-warning #layout { padding-top: 92px; }
}

/* Campos de solo lectura: mismas clases visuales que el editor (.editable, .field-input,
   .cell-input, .cond-input ya son transparentes/sin borde), solo se anula el cursor de texto
   y el subrayado de hover/focus para que no parezcan campos interactivos. */
.ro { cursor: default; }
.editable.ro:hover, .editable.ro:focus { border-bottom-color: transparent; background: transparent; }
.field-input.ro:focus { background: transparent; }
.cell-input.ro:focus  { background: transparent; }
.cond-input.ro:focus  { border-bottom-color: #e5e7eb; }
.desc-area.ro { resize: none; }
</style>

<!-- ═══════════════════ PWA ═══════════════════ -->
<link rel="manifest" href="/cotizador/manifest.json">
<meta name="theme-color" content="#e63946">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Cotizador">
<link rel="apple-touch-icon" href="/cotizador/assets/icons/icon-192.png">
<!-- ════════════════════════════════════════════ -->
</head>
<body class="<?= $ctrl->esAdminViendo ? 'has-admin-warning' : '' ?>">

<?php if ($ctrl->esAdminViendo): ?>
<div class="admin-warning-view">
    Estás viendo una cotización de otro usuario. <a href="cotizaciones.php?action=editar&id=<?= $ctrl->id ?>">Ir al editor</a>
</div>
<?php endif; ?>

<!-- ═══ TOOLBAR ═══ -->
<div id="toolbar">
    <div class="tb-brand">
        <div class="tb-mark">F8</div>
        <div class="tb-name"><b>Cotizador</b> Fortress8</div>
    </div>
    <div class="tb-sep" aria-hidden="true"></div>

    <div class="tb-center">
        <div class="cot-pill">
            <span class="num"><?= htmlspecialchars($ctrl->numeroCotizacion) ?></span>
            <span class="dot">·</span>
            <span><?= htmlspecialchars($ctrl->fechaTexto) ?></span>
        </div>
        <span class="estado-tag-view estado-<?= htmlspecialchars($ctrl->estadoActual) ?>" style="margin-left:8px">
            <span class="dot"></span>
            <?= htmlspecialchars(estado_label($ctrl->estadoActual)) ?>
        </span>
    </div>

    <div class="tb-actions">
        <a class="btn btn-ghost" href="dashboard.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            <span>Volver</span>
        </a>

        <a class="btn btn-save" href="cotizaciones.php?action=editar&id=<?= $ctrl->id ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span>Editar</span>
        </a>

        <a class="btn btn-export" href="descargar_pdf.php?id=<?= $ctrl->id ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            <span>Exportar PDF</span>
        </a>
    </div>
</div>

<div id="layout">
    <div id="sheet-wrap">
        <div id="a4">

            <!-- ── Header A4 ── -->
            <div class="a4-header">
                <div class="header-left">
                    <img src="assets/img/logo.png" alt="Fortress8"
                         style="height:60px;object-fit:contain;display:block"
                         onerror="this.style.display='none'">
                    <div class="cot-label">Cotización</div>
                    <div class="cot-num"><?= htmlspecialchars($ctrl->numeroCotizacion) ?></div>
                    <div class="cot-date"><?= htmlspecialchars($ctrl->fechaTexto) ?></div>
                </div>
                <div class="header-right">
                    <span class="editable sm bold w72 right ro"><?= htmlspecialchars($ctrl->empresa['nombre']) ?></span>
                    <span class="editable xs w40 right ro" style="color:#6b7280"><?= htmlspecialchars($ctrl->empresa['rfc']) ?></span>
                    <span class="editable xs w72 right ro" style="color:#6b7280"><?= htmlspecialchars($ctrl->empresa['direccion']) ?></span>
                    <span class="editable xs w56 right ro" style="color:#6b7280"><?= htmlspecialchars($ctrl->empresa['web']) ?></span>
                    <span class="editable xs w56 right ro" style="color:#6b7280"><?= htmlspecialchars($ctrl->empresa['email']) ?></span>
                    <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#6b7280;flex-wrap:wrap">
                        <span>O:</span>
                        <span class="editable xs w28 ro"><?= htmlspecialchars($ctrl->empresa['tel_o']) ?></span>
                        <span style="color:#e5e7eb">|</span>
                        <span>M:</span>
                        <span class="editable xs w28 ro"><?= htmlspecialchars($ctrl->empresa['tel_m']) ?></span>
                    </div>
                </div>
            </div>

            <!-- ── Body A4 ── -->
            <div class="a4-body">

                <!-- Cliente -->
                <div>
                    <div class="sec-title">Datos del cliente</div>
                    <div class="client-fields">
                        <div class="field-row">
                            <span class="field-label">Atención</span>
                            <div class="field-input ro"><?= htmlspecialchars($ctrl->cotizacion['atencion'] ?? '') ?: '&nbsp;' ?></div>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Puesto</span>
                            <div class="field-input ro"><?= htmlspecialchars($ctrl->cotizacion['puesto'] ?? '') ?: '&nbsp;' ?></div>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Empresa</span>
                            <div class="field-input ro"><?= htmlspecialchars($ctrl->cotizacion['empresa'] ?? '') ?: '&nbsp;' ?></div>
                        </div>
                    </div>
                </div>

                <!-- Tabla productos -->
                <div>
                    <div class="sec-title">Productos / Servicios</div>
                    <div class="items-table-wrap">
                    <table class="items-table">
                        <colgroup><col><col><col><col><col><col><col><col><col></colgroup>
                        <thead>
                            <tr>
                                <th>Cant.</th><th>SKU</th><th>Unidad</th><th>Descripción</th>
                                <th>P. Unit.</th><th>P. Ext.</th><th>Desc. %</th><th>Subtotal</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $sym  = $ctrl->monedaSym;
                            $code = $ctrl->monedaLabel;
                            $fmtRow = fn($n) => $sym . number_format((float)$n, 2, '.', ',');
                        ?>
                        <?php if ($ctrl->items): ?>
                            <?php foreach ($ctrl->items as $item):
                                $cant   = $item['cant']        ?? $item['cantidad']      ?? '';
                                $sku    = $item['sku']         ?? $item['codigo']        ?? '';
                                $unidad = $item['unidad']      ?? 'PZA';
                                $desc   = $item['descripcion'] ?? '';
                                $precio = $item['precio']      ?? $item['precioUnitario'] ?? '';
                                $descP  = (float)($item['descuento'] ?? 0);
                                $ext    = $item['extendido']   ?? ((float)$cant * (float)$precio);
                                $sub    = $item['subtotal']    ?? ((float)$ext * (1 - $descP / 100));
                                $tieneValores = ((float)$cant > 0 && (float)$precio > 0);
                                $descMonto = (float)$ext - (float)$sub;
                            ?>
                            <tr>
                                <td><div class="cell-input center ro"><?= htmlspecialchars($cant) ?></div></td>
                                <td><div class="cell-input xs ro"><?= htmlspecialchars($sku ?: '—') ?></div></td>
                                <td><div class="cell-input center ro"><?= htmlspecialchars($unidad) ?></div></td>
                                <td><div class="cell-input desc-area ro" style="white-space:pre-wrap"><?= htmlspecialchars($desc) ?></div></td>
                                <td><div class="cell-input right ro"><?= $precio !== '' ? htmlspecialchars(number_format((float)$precio, 2, '.', ',')) : '' ?></div></td>
                                <td>
                                    <?php if ($tieneValores): ?>
                                    <span class="cell-display"><?= $fmtRow($ext) ?></span>
                                    <?php else: ?>
                                    <span class="cell-display muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><div class="cell-input right ro"><?= htmlspecialchars(rtrim(rtrim(number_format($descP, 2, '.', ''), '0'), '.') ?: '0') ?></div></td>
                                <td>
                                    <?php if ($tieneValores): ?>
                                    <span class="cell-display">
                                        <?= $fmtRow($sub) ?>
                                        <?php if ($descP > 0): ?>
                                        <span style="display:block;font-size:9px;color:#dc2626;font-weight:500">−<?= $fmtRow($descMonto) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="cell-display muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="padding:14px;text-align:center;color:#9ca3af;font-size:11px">Sin ítems registrados.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" style="border:none;padding:8px 0 0 0">
                                    <div class="footer-flex" style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px">

                                        <!-- Moneda + Vigencia -->
                                        <div class="info-block" style="flex:1;max-width:390px;margin-top:8px">
                                            <div class="info-line">
                                                <strong style="color:#374151;font-size:10px;white-space:nowrap">Moneda</strong>
                                                <span style="font-size:11px;font-weight:600;color:#111827"><?= htmlspecialchars($ctrl->monedaCode) ?> — <?= $ctrl->monedaCode === 'USD' ? 'Dólar americano' : ($ctrl->monedaCode === 'MXN' ? 'Peso mexicano' : 'Euro') ?></span>
                                            </div>
                                            <?php if ($ctrl->monedaCode !== 'USD'): ?>
                                            <div class="info-line">
                                                <span style="font-size:10px;color:#4b5563;white-space:nowrap">1 USD =</span>
                                                <span style="font-size:12px;font-weight:600;color:#111827;font-family:'JetBrains Mono',monospace"><?= number_format($ctrl->tipoCambio, 4) ?></span>
                                                <span style="font-size:11px;color:#374151;font-weight:600"><?= htmlspecialchars($ctrl->monedaCode) ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="info-line">
                                                <strong style="color:#374151;font-size:10px;white-space:nowrap">Vigencia</strong>
                                                <span class="editable center w10 bold xs ro"><?= htmlspecialchars($ctrl->vigenciaDias ?: '30') ?></span>
                                                <span style="font-size:11px;font-weight:600;color:#374151">días naturales</span>
                                            </div>
                                        </div>

                                        <!-- Totales -->
                                        <div class="totals-block">
                                            <div class="totals-row">
                                                <div class="lbl">Subtotal</div>
                                                <div class="val"><?= fmt($ctrl->subtotal, $sym, $code) ?></div>
                                            </div>
                                            <?php if ($ctrl->aplicaIva): ?>
                                            <div class="totals-row">
                                                <div class="lbl">IVA</div>
                                                <div class="val"><?= fmt($ctrl->iva, $sym, $code) ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="totals-row big">
                                                <div class="lbl">Total</div>
                                                <div class="val"><?= fmt($ctrl->total, $sym, $code) ?></div>
                                            </div>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    </div><!-- /items-table-wrap -->
                </div>

                <!-- Condiciones comerciales -->
                <div>
                    <div class="sec-title">Condiciones comerciales</div>
                    <div class="cond-grid">
                        <?php
                        $condFields = [
                            ['t_entrega',   'Tiempo de entrega:'],
                            ['v_servicios', 'Vigencia de servicios:'],
                            ['cond_pago',   'Condiciones de pago:'],
                            ['l_entrega',   'Lugar de entrega:'],
                        ];
                        foreach ($condFields as [$key, $label]):
                        ?>
                        <div class="cond-row">
                            <span class="cond-label"><?= $label ?></span>
                            <span class="cond-input ro"><?= htmlspecialchars($ctrl->condiciones[$key] ?? '') ?: '—' ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Firma y Sello -->
                <div class="firma-section">
                    <div class="firma-block left-block">
                        <?php $tieneFirmaImg = !empty($ctrl->firma['firma_path']); ?>
                        <div class="upload-area firma-upload" style="border-style:solid;border-color:#e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center">
                            <?php if ($tieneFirmaImg): ?>
                            <img src="<?= htmlspecialchars($ctrl->firma['firma_path']) ?>" alt="Firma" style="max-height:62px;max-width:192px;object-fit:contain">
                            <?php endif; ?>
                        </div>
                        <div class="firma-line"></div>
                        <div>
                            <div class="editable sm bold w56 ro"><?= htmlspecialchars($ctrl->firma['nombre'] ?: '') ?: '&nbsp;' ?></div>
                            <div class="editable xs w56 ro" style="color:#374151"><?= htmlspecialchars($ctrl->firma['puesto'] ?: '') ?: '&nbsp;' ?></div>
                            <div class="editable xs w40 ro" style="color:#374151"><?= htmlspecialchars($ctrl->firma['tel'] ?: '') ?: '&nbsp;' ?></div>
                        </div>
                    </div>

                    <div class="firma-block" style="align-items:center">
                        <?php $tieneSelloImg = !empty($ctrl->firma['sello_path']); ?>
                        <div class="upload-area sello-upload" style="border-style:solid;border-color:#e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center">
                            <?php if ($tieneSelloImg): ?>
                            <img src="<?= htmlspecialchars($ctrl->firma['sello_path']) ?>" alt="Sello" style="max-height:92px;max-width:152px;object-fit:contain">
                            <?php endif; ?>
                        </div>
                        <div class="sello-line"></div>
                        <div class="sello-sublabel">Sello de la empresa</div>
                    </div>
                </div>

            </div><!-- /a4-body -->

        </div><!-- /a4 -->
    </div><!-- /sheet-wrap -->
</div><!-- /layout -->

</body>
</html>
