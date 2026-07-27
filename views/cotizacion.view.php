<?php

$esNueva = ($ctrl->action !== 'editar');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $esNueva ? 'Nueva' : 'Editar' ?> Cotización — Fortress8</title>
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
.field-row.field-invalid { border: 1px solid #dc2626; border-radius: 3px; }
.field-row.field-invalid .field-label { color: #dc2626; }
.field-row.field-invalid .field-input::placeholder { color: #fca5a5; }

/* Items table */
.items-table { width: 100%; border-collapse: collapse; font-size: 12px; table-layout: fixed; }
.items-table col:nth-child(1) { width: 42px; }
.items-table col:nth-child(2) { width: 118px; } /* Ancho de SKU aumentado */
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
.cell-input.sku-area {
    resize: none; min-height: 22px; height: auto;
    overflow: hidden; line-height: 1.35;
    white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere;
    font-family: 'JetBrains Mono', monospace;
}
/* Espejo de solo lectura: sustituye al textarea SOLO al imprimir, porque
   los textarea son controles nativos que conservan su propio scroll
   interno (con flechitas) incluso con overflow:visible, y por eso el
   texto se veía cortado en el PDF. */
.print-mirror {
    display: none;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    padding: 5px 4px;
    line-height: 1.45;
    font-size: 11px;
    color: #111827;
    font-family: inherit;
}
.print-mirror.sku-mirror {
    line-height: 1.35;
    font-size: 10px;
    font-family: 'JetBrains Mono', monospace;
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

    /* ── Fix: texto de SKU/Descripción cortado al imprimir ──
       Los <textarea> son controles nativos: aunque se les ponga
       overflow:visible, el navegador puede seguir mostrando su propio
       scroll interno (flechitas) y cortando el texto. La solución
       robusta es ocultarlos al imprimir y mostrar en su lugar un <div>
       de solo texto (.print-mirror) con el mismo contenido, que sí
       fluye libremente sin ningún límite de alto. */
    .cell-input.desc-area,
    .cell-input.sku-area {
        display: none !important;
    }
    .print-mirror {
        display: block !important;
    }
    /* Evita que una fila de producto quede partida entre dos páginas */
    .items-table { border-collapse: separate !important; border-spacing: 0; }
    .items-table tbody tr,
    .totals-block,
    .firma-section { break-inside: avoid; page-break-inside: avoid !important; }
    .items-table thead { display: table-header-group; }
    /* Fix: el Total/Subtotal se repetía en cada hoja porque los
       navegadores tratan <tfoot> como un pie que se repite en cada
       página impresa (igual que el <thead> se repite arriba). Forzamos
       que se comporte como una fila normal: aparece una sola vez, al
       final de la tabla, en la última hoja que le corresponda. */
    .items-table tfoot { display: table-row-group !important; }

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

    @page { size: A4 portrait; margin: 14mm 0; }
    /* La primera hoja no lleva margen: el propio encabezado (.a4-header)
       ya controla su espaciado con su padding interno. A partir de la
       segunda hoja sí queremos el margen de arriba, para que la tabla
       continuada no quede pegada al borde superior del papel. */
    @page :first { margin-top: 0; }
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
</style>
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
</head>
<body>

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
    </div>

    <div class="tb-actions">
        <?php if ($auth->esAdmin() && !$esNueva && $ctrl->cotizacionId): ?>
        <button class="btn btn-admin" onclick="openAdminPanel()" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>Admin</span>
            <span class="estado-tag" id="estado-pill-toolbar"><?= htmlspecialchars(estado_label($ctrl->estadoActual)) ?></span>
        </button>
        <div class="tb-sep" aria-hidden="true"></div>
        <?php endif; ?>

        <button class="btn btn-ghost" onclick="togglePanel()" type="button" id="btn-panel">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
            <span>Lista</span>
        </button>

        <button class="btn btn-save" onclick="guardarCotizacion()" type="button" id="btn-guardar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span>Guardar</span>
        </button>

        <button class="btn btn-export" onclick="exportarPdf()" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            <span>Exportar PDF</span>
        </button>
    </div>
</div>

<?php if ($auth->esAdmin() && !$esNueva && $ctrl->cotizacionId): ?>
<!-- ═══ ADMIN MODAL ═══ -->
<div class="modal-backdrop" id="admin-modal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title-txt">
        <div class="modal-title" id="modal-title-txt">Panel de administrador</div>
        <div class="modal-sub">Cotización <?= htmlspecialchars($ctrl->numeroCotizacion) ?></div>

        <label class="modal-label" for="admin-estado">Estado</label>
        <select id="admin-estado" class="modal-select">
            <?php foreach (['pendiente'=>'Pendiente','aprobada'=>'Aprobada','rechazada'=>'Rechazada'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $ctrl->estadoActual === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>

        <?php if ($ctrl->cotOwnerId && $ctrl->cotOwnerId !== $auth->usuarioId()): ?>
            <label class="modal-label" style="margin-top:14px">
                Mensaje para <?= htmlspecialchars($ctrl->cotOwnerNombre) ?>
                <span style="text-transform:none;font-weight:400;color:var(--t2)">(opcional)</span>
            </label>
            <textarea id="admin-mensaje" class="modal-textarea" placeholder="Ej: Por favor corrige el precio del ítem 2."></textarea>
            <p class="modal-hint">
                Con mensaje: se notifica a <strong><?= htmlspecialchars($ctrl->cotOwnerNombre) ?></strong> y la cotización pasa a <strong>Pendiente</strong>.<br>
                Sin mensaje: se aplica el estado seleccionado sin notificación.
            </p>
        <?php else: ?>
            <p class="modal-hint">Esta cotización es tuya — no se enviará ninguna notificación.</p>
        <?php endif; ?>

        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeAdminPanel()">Cancelar</button>
            <button type="button" class="btn-modal-ok" onclick="closeAdminPanel()">Aplicar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ MODAL: GUARDADO EXITOSO ═══ -->
<div class="modal-backdrop" id="saved-modal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="saved-modal-title">
        <div class="modal-saved-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="modal-saved-title" id="saved-modal-title">Cotización guardada</div>
        <div class="modal-saved-sub" id="saved-modal-sub">Los cambios se guardaron correctamente.</div>
        <div class="modal-saved-actions">
            <button type="button" class="btn-pdf-now" onclick="exportarPdfDesdeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <span>Exportar PDF ahora</span>
            </button>
            <button type="button" class="btn-saved-close" onclick="closeSavedModal()">Cerrar</button>
        </div>
    </div>
</div>

<!-- ═══ TOASTS ═══ -->
<div id="toast-container"></div>


<!-- Overlay para cerrar el drawer en tablet/móvil -->
<div id="panel-overlay" onclick="closePanel()"></div>

<!-- ═══ HINT ═══ -->
<div id="hint">Haz clic en cualquier texto para editarlo &nbsp;·&nbsp; Busca y agrega productos desde el panel izquierdo &nbsp;·&nbsp; Los precios se convierten automáticamente según la moneda seleccionada</div>

<!-- ═══ LAYOUT ═══ -->
<div id="layout">

    <!-- ═══ PRICE PANEL ═══ -->
    <div id="price-panel">
        <div class="pp-head">
            <div class="pp-title-row">
                <span class="pp-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </span>
                <span class="pp-title">Lista de precios</span>
                <span class="pp-count" id="price-count"><?= count($ctrl->priceList) ?></span>
                <button class="btn-refresh" onclick="refreshPriceList()" id="btn-refresh-prices" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Actualizar
                </button>
            </div>
            <div class="ver-bar">
                <div class="ver-dot"></div>
                <span class="ver-name" id="version-info">
                    <?= $ctrl->versionActiva ? htmlspecialchars($ctrl->versionActiva['nombre']) : 'Sin versión activa' ?>
                </span>
                <span class="ver-date" id="panel-footer-txt">
                    <?= $ctrl->versionActiva ? htmlspecialchars(date('d/m/Y H:i', strtotime($ctrl->versionActiva['created_at']))) : '—' ?>
                </span>
            </div>
        </div>

        <div class="pp-search">
            <div class="search-wrap">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" id="search-input" placeholder="Buscar SKU o descripción…" oninput="filterProducts()" autocomplete="off">
            </div>
        </div>

        <div class="pp-cats" id="cat-tabs">
            <button class="cat-btn active" data-cat="Todos" onclick="filterByCategory(this)">Todos</button>
            <?php foreach (array_keys($ctrl->porCategoria) as $cat): ?>
                <button class="cat-btn" data-cat="<?= htmlspecialchars($cat) ?>" onclick="filterByCategory(this)">
                    <?= htmlspecialchars(str_replace('Forti', 'F.', $cat)) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="pp-results" id="results-count"><?= count($ctrl->priceList) ?> resultado<?= count($ctrl->priceList) !== 1 ? 's' : '' ?></div>

        <div class="products-list" id="products-list">
            <?php foreach ($ctrl->priceList as $p): ?>
            <div class="product-item"
                 data-sku="<?= htmlspecialchars(strtolower($p['sku'])) ?>"
                 data-desc="<?= htmlspecialchars(strtolower($p['desc'])) ?>"
                 data-cat="<?= htmlspecialchars($p['sheet']) ?>">
                <div class="pi-info">
                    <div class="pi-sku"><?= htmlspecialchars($p['sku']) ?></div>
                    <div class="pi-desc"><?= htmlspecialchars($p['desc']) ?></div>
                    <div class="pi-price">$<?= number_format($p['price'], 2) ?> USD</div>
                </div>
                <button class="btn-add-item" title="Agregar a cotización"
                    onclick='addFromList(<?= json_encode($p['sku']) ?>, <?= json_encode($p['desc']) ?>, <?= (float)$p['price'] ?>)'>+</button>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="pp-foot">
            <span id="pp-foot-date">
                <?= $ctrl->versionActiva ? htmlspecialchars(date('d/m/Y', strtotime($ctrl->versionActiva['created_at']))) : '—' ?>
            </span>
            <a href="lista_precios.php">
                Gestionar
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div><!-- /price-panel -->

    <!-- ═══ A4 SHEET ═══ -->
    <div id="sheet-wrap">
        <form id="cot-form" method="POST" action="guardar_cotizacion.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= htmlspecialchars($ctrl->action) ?>">
        <?php if ($ctrl->cotizacionId): ?>
            <input type="hidden" name="cotizacion_id" value="<?= (int)$ctrl->cotizacionId ?>">
        <?php endif; ?>

        <div id="a4">

            <!-- ── Header A4 ── -->
            <div class="a4-header">
                <div class="header-left">
                    <img src="assets/img/logo.png" alt="Fortress8"
                         style="height:60px;object-fit:contain;display:block"
                         onerror="this.style.display='none'">
                    <div class="cot-label">Cotización</div>
                    <div class="cot-num"><?= htmlspecialchars($ctrl->numeroCotizacion) ?></div>
                    <input type="hidden" name="numero_cotizacion" value="<?= htmlspecialchars($ctrl->numeroCotizacion) ?>">
                    <div class="cot-date"><?= htmlspecialchars($ctrl->fechaTexto) ?></div>
                    <input type="hidden" name="fecha" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="header-right">
                    <input class="editable sm bold w72 right" name="empresa_nombre"
                           value="<?= htmlspecialchars($ctrl->cd['nombre']) ?>"
                           placeholder="Nombre de la empresa">
                    <input class="editable xs w40 right" name="empresa_rfc"
                           value="<?= htmlspecialchars($ctrl->cd['rfc']) ?>"
                           placeholder="RFC" style="color:#6b7280">
                    <input class="editable xs w72 right" name="empresa_direccion"
                           value="<?= htmlspecialchars($ctrl->cd['direccion']) ?>"
                           placeholder="Dirección" style="color:#6b7280">
                    <input class="editable xs w56 right" name="empresa_web"
                           value="<?= htmlspecialchars($ctrl->cd['web']) ?>"
                           placeholder="www.sitio.com" style="color:#6b7280">
                    <input class="editable xs w56 right" name="empresa_email"
                           value="<?= htmlspecialchars($ctrl->cd['email']) ?>"
                           placeholder="correo@empresa.com" style="color:#6b7280">
                    <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#6b7280;flex-wrap:wrap">
                        <span>O:</span>
                        <input class="editable xs w28" name="tel_o"
                               value="<?= htmlspecialchars($ctrl->cd['tel_o']) ?>" placeholder="Tel oficina">
                        <span style="color:#e5e7eb">|</span>
                        <span>M:</span>
                        <input class="editable xs w28" name="tel_m"
                               value="<?= htmlspecialchars($ctrl->cd['tel_m']) ?>" placeholder="Tel móvil">
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
                            <input class="field-input" name="atencion"
                                   value="<?= htmlspecialchars($ctrl->cd['atencion']) ?>"
                                   placeholder="Nombre del contacto">
                        </div>
                        <div class="field-row">
                            <span class="field-label">Puesto</span>
                            <input class="field-input" name="puesto_c"
                                   value="<?= htmlspecialchars($ctrl->cd['puesto_c']) ?>"
                                   placeholder="Cargo / Posición">
                        </div>
                        <div class="field-row">
                            <span class="field-label">Empresa</span>
                            <input class="field-input" name="empresa_c"
                                   value="<?= htmlspecialchars($ctrl->cd['empresa_c']) ?>"
                                   placeholder="Razón social o nombre comercial">
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
                        <tbody id="items-body">
                        <?php if ($ctrl->itemsData): ?>
                            <?php foreach ($ctrl->itemsData as $item): ?>
                            <tr>
                                <td><input class="cell-input center" name="items[][cant]" value="<?= htmlspecialchars($item['cant'] ?? $item['cantidad'] ?? '') ?>" placeholder="0" oninput="recalcRow(this)"></td>
                                <td><textarea class="cell-input xs sku-area" name="items[][sku]" rows="1" placeholder="—" oninput="autoResizeTA(this)" onkeydown="if(event.key==='Enter'){event.preventDefault();}"><?= htmlspecialchars($item['sku'] ?? $item['codigo'] ?? '') ?></textarea><div class="print-mirror sku-mirror"></div></td>
                                <td><input class="cell-input center" name="items[][unidad]" value="<?= htmlspecialchars($item['unidad'] ?? 'PZA') ?>" placeholder="PZA"></td>
                                <td><textarea class="cell-input desc-area" name="items[][descripcion]" rows="2" placeholder="Descripción"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea><div class="print-mirror"></div></td>
                                <td><input class="cell-input right" name="items[][precio]" value="<?= htmlspecialchars($item['precio'] ?? $item['precioUnitario'] ?? '') ?>" placeholder="0.00" oninput="recalcRow(this)"></td>
                                <td><span class="cell-display" data-extendido></span><input type="hidden" name="items[][extendido]"></td>
                                <td><input class="cell-input right" name="items[][descuento]" value="<?= htmlspecialchars($item['descuento'] ?? '0') ?>" placeholder="0" oninput="recalcRow(this)"></td>
                                <td><span class="cell-display" data-subtotal></span><input type="hidden" name="items[][subtotal]"></td>
                                <td><button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Eliminar">×</button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td><input class="cell-input center" name="items[][cant]" placeholder="0" oninput="recalcRow(this)"></td>
                                <td><textarea class="cell-input xs sku-area" name="items[][sku]" rows="1" placeholder="—" oninput="autoResizeTA(this)" onkeydown="if(event.key==='Enter'){event.preventDefault();}"></textarea><div class="print-mirror sku-mirror"></div></td>
                                <td><input class="cell-input center" name="items[][unidad]" value="PZA" placeholder="PZA"></td>
                                <td><textarea class="cell-input desc-area" name="items[][descripcion]" rows="2" placeholder="Descripción del producto o servicio"></textarea><div class="print-mirror"></div></td>
                                <td><input class="cell-input right" name="items[][precio]" placeholder="0.00" oninput="recalcRow(this)"></td>
                                <td><span class="cell-display" data-extendido></span><input type="hidden" name="items[][extendido]"></td>
                                <td><input class="cell-input right" name="items[][descuento]" placeholder="0" value="0" oninput="recalcRow(this)"></td>
                                <td><span class="cell-display" data-subtotal></span><input type="hidden" name="items[][subtotal]"></td>
                                <td><button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Eliminar">×</button></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="tfoot-add">
                                <td colspan="9">
                                    <button type="button" class="btn-add-row" onclick="addRow()">+ Agregar fila</button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="9" style="border:none;padding:8px 0 0 0">
                                    <div class="footer-flex" style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px">

                                        <!-- Moneda + Vigencia -->
                                        <div class="info-block" style="flex:1;max-width:390px;margin-top:8px">
                                            <div class="info-line">
                                                <strong style="color:#374151;font-size:10px;white-space:nowrap">Moneda</strong>
                                                <select id="currency-select" name="moneda_code"
                                                        class="currency-select"
                                                        onchange="onCurrencyChange()">
                                                    <option value="USD" <?= $ctrl->cd['moneda_code']==='USD'?'selected':'' ?>>USD — Dólar americano</option>
                                                    <option value="MXN" <?= $ctrl->cd['moneda_code']==='MXN'?'selected':'' ?>>MXN — Peso mexicano</option>
                                                </select>
                                            </div>
                                            <div id="rate-row" class="rate-row" style="display:none">
                                                <span style="font-size:10px;color:#4b5563;white-space:nowrap">1 USD =</span>
                                                <div class="rate-wrap">
                                                    <input type="number" id="exchange-rate" name="tipo_cambio"
                                                           class="rate-input" step="0.0001" min="0.0001"
                                                           value="<?= htmlspecialchars($ctrl->cd['tipo_cambio']) ?>"
                                                           oninput="onRateManualEdit()">
                                                    <span id="rate-cur-label" class="rate-cur-lbl">MXN</span>
                                                </div>
                                                <span id="rate-badge-live"   class="rate-badge live"   style="display:none">● En vivo</span>
                                                <span id="rate-badge-custom" class="rate-badge custom" style="display:none">✏ Manual</span>
                                                <button type="button" id="rate-reset-btn" class="rate-reset" style="display:none" onclick="resetExchangeRate()">↺ Restablecer</button>
                                            </div>
                                            <div class="info-line">
                                                <strong style="color:#374151;font-size:10px;white-space:nowrap">Vigencia</strong>
                                                <input class="editable center w10 bold xs" name="vigencia_dias"
                                                       value="<?= htmlspecialchars($ctrl->cd['vigencia']) ?>" placeholder="30">
                                                <span style="font-size:11px;font-weight:600;color:#374151">días naturales</span>
                                            </div>
                                        </div>

                                        <!-- Totales -->
                                        <div class="totals-block">
                                            <div class="totals-row">
                                                <div class="lbl">Subtotal</div>
                                                <div class="val" id="display-subtotal">$0.00</div>
                                            </div>
                                            <div id="no-iva-row" style="text-align:right;padding:3px 0">
                                                <button type="button" class="btn-add-iva" onclick="showIva()">+ Agregar IVA</button>
                                            </div>
                                            <div id="iva-row" style="display:none">
                                                <div class="totals-row">
                                                    <div class="lbl" style="display:flex;align-items:center;justify-content:flex-end;gap:5px">
                                                        <span>IVA</span>
                                                        <input class="iva-pct-input" id="iva-pct" type="number" min="0" max="100" value="16" oninput="recalcAll()">
                                                        <span>%</span>
                                                        <button type="button" class="btn-iva-remove" onclick="hideIva()">quitar</button>
                                                    </div>
                                                    <div class="val" id="display-iva">$0.00</div>
                                                </div>
                                            </div>
                                            <div class="totals-row big">
                                                <div class="lbl">Total</div>
                                                <div class="val" id="display-total">$0.00</div>
                                            </div>
                                        </div>

                                    </div>
                                    <input type="hidden" name="subtotal" id="hidden-subtotal">
                                    <input type="hidden" name="iva"      id="hidden-iva">
                                    <input type="hidden" name="total"    id="hidden-total">
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
                            <input class="cond-input" name="<?= $key ?>"
                                   value="<?= htmlspecialchars($ctrl->cd[$key]) ?>" placeholder="—">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Firma y Sello -->
                <div class="firma-section">
                    <div class="firma-block left-block">
                        <?php $tieneFirma = !empty($ctrl->cd['firma_path']); ?>
                        <label class="upload-area firma-upload<?= $tieneFirma ? ' has-image' : '' ?>" id="firma-area">
                            <div class="upload-hint" id="firma-hint" style="<?= $tieneFirma ? 'display:none' : '' ?>">
                                <span class="icon">↑</span>
                                <span class="label">Subir firma</span>
                                <span class="sub">PNG · JPG · WEBP</span>
                            </div>
                            <img id="firma-img" src="<?= $tieneFirma ? htmlspecialchars($ctrl->cd['firma_path']) . '?v=' . time() : '' ?>" alt="Firma"
                                 style="<?= $tieneFirma ? '' : 'display:none;' ?>max-height:62px;max-width:192px;object-fit:contain">
                            <span class="upload-change" id="firma-change" style="<?= $tieneFirma ? '' : 'display:none' ?>">cambiar</span>
                            <input type="file" name="firma" accept="image/png,image/jpeg,image/webp" class="upload-input" onchange="previewImage(this,'firma')">
                        </label>
                        <div class="firma-line"></div>
                        <div>
                            <input class="editable sm bold w56" name="f_nombre"
                                   value="<?= htmlspecialchars($ctrl->cd['f_nombre']) ?>" placeholder="Nombre del firmante">
                            <input class="editable xs w56" name="f_puesto"
                                   value="<?= htmlspecialchars($ctrl->cd['f_puesto']) ?>"
                                   placeholder="Puesto" style="color:#374151">
                            <input class="editable xs w40" name="f_tel"
                                   value="<?= htmlspecialchars($ctrl->cd['f_tel']) ?>"
                                   placeholder="Teléfono" style="color:#374151">
                        </div>
                    </div>

                    <div class="firma-block" style="align-items:center">
                        <?php $tieneSello = !empty($ctrl->cd['sello_path']); ?>
                        <label class="upload-area sello-upload<?= $tieneSello ? ' has-image' : '' ?>" id="sello-area">
                            <div class="upload-hint" id="sello-hint" style="<?= $tieneSello ? 'display:none' : '' ?>">
                                <span class="icon" style="font-size:20px">↑</span>
                                <span class="label">Subir sello</span>
                                <span class="sub">PNG · JPG · WEBP</span>
                            </div>
                            <img id="sello-img" src="<?= $tieneSello ? htmlspecialchars($ctrl->cd['sello_path']) . '?v=' . time() : '' ?>" alt="Sello"
                                 style="<?= $tieneSello ? '' : 'display:none;' ?>max-height:92px;max-width:152px;object-fit:contain">
                            <span class="upload-change" id="sello-change" style="<?= $tieneSello ? '' : 'display:none' ?>">cambiar</span>
                            <input type="file" name="sello" accept="image/png,image/jpeg,image/webp" class="upload-input" onchange="previewImage(this,'sello')">
                        </label>
                        <div class="sello-line"></div>
                        <div class="sello-sublabel">Sello de la empresa</div>
                    </div>
                </div>


            </div><!-- /a4-body -->

        </div><!-- /a4 -->
        </form>
    </div><!-- /sheet-wrap -->
</div><!-- /layout -->

<script src="assets/js/notif-sound.js"></script>
<script>
// ═══════════════════════════════════════════════
// PANEL: drawer en tablet/móvil, sidebar en desktop
// ═══════════════════════════════════════════════
const IS_MOBILE = () => window.innerWidth <= 1024;

// ═══════════════════════════════════════════════
// CAMBIOS SIN GUARDAR
// ═══════════════════════════════════════════════
let cotizacionDirty = false;
document.getElementById('cot-form')?.addEventListener('input',  () => cotizacionDirty = true);
document.getElementById('cot-form')?.addEventListener('change', () => cotizacionDirty = true);
window.addEventListener('beforeunload', (e) => {
    if (!cotizacionDirty) return;
    e.preventDefault();
    e.returnValue = '';
});

function togglePanel() {
    const panel   = document.getElementById('price-panel');
    const overlay = document.getElementById('panel-overlay');
    if (IS_MOBILE()) {
        const open = panel.classList.toggle('panel-open');
        overlay.classList.toggle('visible', open);
        panel.classList.remove('hidden');
    } else {
        panel.classList.toggle('hidden');
    }
}
function closePanel() {
    const panel   = document.getElementById('price-panel');
    const overlay = document.getElementById('panel-overlay');
    panel.classList.remove('panel-open');
    overlay.classList.remove('visible');
}
window.addEventListener('resize', () => {
    if (!IS_MOBILE()) {
        document.getElementById('panel-overlay').classList.remove('visible');
    }
});

// ═══════════════════════════════════════════════
// TIPO DE CAMBIO
// ═══════════════════════════════════════════════
let todayRates   = { MXN: 18.00, EUR: 0.92 };
let rateIsCustom = false;

async function fetchTodayRate() {
    try {
        const res  = await fetch('API/api_tipo_cambio.php');
        const data = await res.json();
        if (data.ok && data.MXN) {
            todayRates = { MXN: data.MXN, EUR: data.EUR };
            if (!rateIsCustom) applyTodayRate();
        }
    } catch(e) {}
}
function applyTodayRate() {
    const cur = document.getElementById('currency-select').value;
    if (cur === 'USD') return;
    const rate = todayRates[cur] ?? 1;
    document.getElementById('exchange-rate').value = rate.toFixed(4);
    document.getElementById('rate-badge-live').style.display    = '';
    document.getElementById('rate-badge-custom').style.display  = 'none';
    document.getElementById('rate-reset-btn').style.display     = 'none';
    rateIsCustom = false;
}
function onCurrencyChange() {
    const cur      = document.getElementById('currency-select').value;
    const rateRow  = document.getElementById('rate-row');
    const rateLabel = document.getElementById('rate-cur-label');
    rateRow.style.display = cur === 'USD' ? 'none' : 'flex';
    if (cur !== 'USD') {
        rateLabel.textContent = cur;
        if (!rateIsCustom) applyTodayRate();
    }
}
function onRateManualEdit() {
    rateIsCustom = true;
    document.getElementById('rate-badge-live').style.display    = 'none';
    document.getElementById('rate-badge-custom').style.display  = '';
    document.getElementById('rate-reset-btn').style.display     = '';
}
function resetExchangeRate() { rateIsCustom = false; applyTodayRate(); }
function getExchangeRate()   { const c = document.getElementById('currency-select')?.value ?? 'USD'; return c === 'USD' ? 1 : parseFloat(document.getElementById('exchange-rate')?.value) || 1; }
function getCurrencySymbol() { return { USD:'$', MXN:'$', EUR:'€' }[document.getElementById('currency-select')?.value ?? 'USD'] ?? '$'; }
function getCurrencyCode()   { return document.getElementById('currency-select')?.value ?? 'USD'; }

// ═══════════════════════════════════════════════
// PRICE PANEL
// ═══════════════════════════════════════════════
let activeCategory = 'Todos';

async function refreshPriceList() {
    const btn  = document.getElementById('btn-refresh-prices');
    const vinf = document.getElementById('version-info');
    const cnt  = document.getElementById('price-count');
    btn.disabled = true; btn.style.opacity = '.5';
    try {
        const res  = await fetch('API/api_precios.php');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (!data.ok) throw new Error(data.error ?? 'Error');
        if (!data.version) { vinf.textContent = 'Sin versión activa'; return; }
        renderPanelProducts(data.productos, data.version);
        cnt.textContent  = data.total;
        vinf.textContent = data.version.nombre;
        document.getElementById('panel-footer-txt').textContent = data.version.fecha;
        document.getElementById('pp-foot-date').textContent     = data.version.fecha.split(' ')[0];
    } catch(err) {
        vinf.textContent = '⚠ ' + err.message;
    } finally {
        btn.disabled = false; btn.style.opacity = '';
    }
}
function renderPanelProducts(products) {
    const listEl = document.getElementById('products-list');
    const tabsEl = document.getElementById('cat-tabs');
    const si     = document.getElementById('search-input');

    // Conserva lo que el usuario ya tenía escrito/filtrado antes del refresh
    const searchPrevio   = si ? si.value : '';
    const categoriaPrevia = activeCategory;

    const cats   = [...new Set(products.map(p => p.sheet).filter(Boolean))].sort();
    tabsEl.innerHTML = `<button class="cat-btn active" data-cat="Todos" onclick="filterByCategory(this)">Todos</button>`;
    cats.forEach(cat => {
        const b = document.createElement('button');
        b.className = 'cat-btn'; b.dataset.cat = cat;
        b.textContent = cat.replace('Forti','F.');
        b.onclick = function() { filterByCategory(this); };
        tabsEl.appendChild(b);
    });
    listEl.innerHTML = products.map(p => `
        <div class="product-item"
             data-sku="${escH((p.sku||'').toLowerCase())}"
             data-desc="${escH((p.desc||'').toLowerCase())}"
             data-cat="${escH(p.sheet||'')}">
            <div class="pi-info">
                <div class="pi-sku">${escH(p.sku||'')}</div>
                <div class="pi-desc">${escH(p.desc||'')}</div>
                <div class="pi-price">$${parseFloat(p.price||0).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2})} USD</div>
            </div>
            <button class="btn-add-item" title="Agregar"
                onclick='addFromList(${JSON.stringify(p.sku)},${JSON.stringify(p.desc)},${parseFloat(p.price)||0})'>+</button>
        </div>
    `).join('');

    // Restaura la categoría activa, solo si sigue existiendo en la nueva lista
    const categoriaSigueExistiendo = categoriaPrevia === 'Todos' || cats.includes(categoriaPrevia);
    activeCategory = categoriaSigueExistiendo ? categoriaPrevia : 'Todos';
    const tabActivo = tabsEl.querySelector(`.cat-btn[data-cat="${CSS.escape(activeCategory)}"]`) || tabsEl.querySelector('.cat-btn[data-cat="Todos"]');
    tabsEl.querySelectorAll('.cat-btn').forEach(b => b.classList.toggle('active', b === tabActivo));

    // Restaura el texto de búsqueda que el usuario ya tenía
    if (si) si.value = searchPrevio;
    filterProducts();
}
function filterByCategory(btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeCategory = btn.dataset.cat;
    filterProducts();
}
function filterProducts() {
    const q = document.getElementById('search-input').value.toLowerCase();
    let visible = 0;
    document.querySelectorAll('#products-list .product-item').forEach(el => {
        const ok = (activeCategory === 'Todos' || el.dataset.cat === activeCategory)
                && (!q || el.dataset.sku.includes(q) || el.dataset.desc.includes(q));
        el.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.getElementById('results-count').textContent =
        `${visible} resultado${visible !== 1 ? 's' : ''}`;
}

// ═══════════════════════════════════════════════
// ITEMS TABLE
// ═══════════════════════════════════════════════
function addFromList(sku, desc, priceUSD) {
    cotizacionDirty = true;
    const rate      = getExchangeRate();
    const converted = parseFloat((priceUSD * rate).toFixed(2));
    const tbody = document.getElementById('items-body');
    const rows  = tbody.querySelectorAll('tr');
    const last  = rows[rows.length - 1];
    const lCant  = last.querySelector('[name="items[][cant]"]').value;
    const lSku   = last.querySelector('[name="items[][sku]"]').value;
    const lPrice = last.querySelector('[name="items[][precio]"]').value;
    if (!lCant && !lSku && !lPrice) {
        last.querySelector('[name="items[][cant]"]').value         = '1';
        last.querySelector('[name="items[][sku]"]').value          = sku;
        last.querySelector('[name="items[][precio]"]').value       = converted;
        last.querySelector('textarea[name="items[][descripcion]"]').value = desc;
        last.querySelector('[name="items[][descuento]"]').value    = '0';
        autoResizeTA(last.querySelector('textarea[name="items[][sku]"]'));
        autoResizeTA(last.querySelector('textarea[name="items[][descripcion]"]'));
        recalcRow(last.querySelector('[name="items[][cant]"]'));
    } else {
        addRow(sku, desc, converted);
    }
    // En móvil/tablet, cerrar el drawer al agregar
    if (IS_MOBILE()) closePanel();
}
function autoResizeTA(el) {
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = Math.max(40, el.scrollHeight) + 'px';
}

// ═══════════════════════════════════════════════
// FIX: texto de SKU/Descripción cortado al imprimir.
// Los <textarea> son controles nativos del navegador: aunque se les
// ponga overflow:visible, algunos navegadores igual muestran su propio
// scroll interno (flechitas ▲▼) y cortan el texto al imprimir. La
// solución robusta es, justo antes de imprimir, copiar el texto de cada
// textarea a un <div class="print-mirror"> de solo lectura (que no
// tiene ningún límite de alto) y mostrar ese div en vez del textarea.
// ═══════════════════════════════════════════════
function prepararEspejosParaImprimir() {
    document.querySelectorAll('textarea.desc-area, textarea.sku-area').forEach(ta => {
        const mirror = ta.nextElementSibling;
        if (mirror && mirror.classList.contains('print-mirror')) {
            mirror.textContent = ta.value;
        }
    });
}
window.addEventListener('beforeprint', prepararEspejosParaImprimir);
// Safari no siempre dispara 'beforeprint' de forma confiable con window.print();
// como respaldo, también se prepara justo antes de llamar a print().
const _origPrint = window.print.bind(window);
window.print = function() {
    prepararEspejosParaImprimir();
    _origPrint();
};

function addRow(sku='', desc='', price='') {
    cotizacionDirty = true;
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input class="cell-input center" name="items[][cant]" placeholder="0" value="${sku?'1':''}" oninput="recalcRow(this)"></td>
        <td><textarea class="cell-input xs sku-area" name="items[][sku]" rows="1" placeholder="—" oninput="autoResizeTA(this)" onkeydown="if(event.key==='Enter'){event.preventDefault();}">${escH(sku)}</textarea><div class="print-mirror sku-mirror"></div></td>
        <td><input class="cell-input center" name="items[][unidad]" value="PZA" placeholder="PZA"></td>
        <td><textarea class="cell-input desc-area" name="items[][descripcion]" rows="2" placeholder="Descripción del producto o servicio">${escH(desc)}</textarea><div class="print-mirror"></div></td>
        <td><input class="cell-input right" name="items[][precio]" placeholder="0.00" value="${price||''}" oninput="recalcRow(this)"></td>
        <td><span class="cell-display" data-extendido></span><input type="hidden" name="items[][extendido]"></td>
        <td><input class="cell-input right" name="items[][descuento]" placeholder="0" value="0" oninput="recalcRow(this)"></td>
        <td><span class="cell-display" data-subtotal></span><input type="hidden" name="items[][subtotal]"></td>
        <td><button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Eliminar">×</button></td>
    `;
    tbody.appendChild(tr);
    // Ajustar altura del textarea de SKU y descripción
    const skuTA = tr.querySelector('textarea[name="items[][sku]"]');
    const descTA = tr.querySelector('textarea[name="items[][descripcion]"]');
    if (skuTA) { skuTA.addEventListener('input', () => autoResizeTA(skuTA)); autoResizeTA(skuTA); }
    if (descTA) { descTA.addEventListener('input', () => autoResizeTA(descTA)); autoResizeTA(descTA); }
    if (sku || price) recalcRow(tr.querySelector('[name="items[][cant]"]'));
    tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function removeRow(btn) {
    const tbody = document.getElementById('items-body');
    if (tbody.querySelectorAll('tr').length <= 1) return;
    const tr = btn.closest('tr');
    const tieneDatos = ['cant', 'sku', 'descripcion', 'precio'].some(campo => {
        const el = tr.querySelector(`[name="items[][${campo}]"]`);
        return (el?.value ?? '').trim() !== '';
    });
    if (tieneDatos && !confirm('¿Eliminar este ítem de la cotización? Esta acción no se puede deshacer.')) {
        return;
    }
    tr.remove();
    cotizacionDirty = true;
    recalcAll();
}
function parseMXN(v) { return parseFloat(String(v).replace(/[^0-9.]/g,'')) || 0; }
function recalcRow(input) {
    const tr      = input.closest('tr');
    const cant    = parseMXN(tr.querySelector('[name="items[][cant]"]').value);
    const precio  = parseMXN(tr.querySelector('[name="items[][precio]"]').value);
    const descPct = parseMXN(tr.querySelector('[name="items[][descuento]"]').value);
    const ext = cant * precio;
    const sub = ext * (1 - descPct / 100);
    const sym = getCurrencySymbol();
    const fmt = n => sym + n.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
    const extSpan = tr.querySelector('[data-extendido]');
    const subSpan = tr.querySelector('[data-subtotal]');
    tr.querySelector('[name="items[][extendido]"]').value = ext.toFixed(2);
    tr.querySelector('[name="items[][subtotal]"]').value  = sub.toFixed(2);
    if (cant > 0 && precio > 0) {
        extSpan.textContent = fmt(ext); extSpan.className = 'cell-display';
        const descMonto = ext - sub;
        subSpan.innerHTML = fmt(sub) + (descPct > 0 ? `<span style="display:block;font-size:9px;color:#dc2626;font-weight:500">−${fmt(descMonto)}</span>` : '');
        subSpan.className = 'cell-display';
    } else {
        extSpan.textContent = '—'; extSpan.className = 'cell-display muted';
        subSpan.textContent = '—'; subSpan.className = 'cell-display muted';
    }
    recalcAll();
}
function recalcAll() {
    let sub = 0;
    document.querySelectorAll('#items-body tr').forEach(tr => {
        sub += parseMXN(tr.querySelector('[name="items[][subtotal]"]')?.value);
    });
    const ivaActive = document.getElementById('iva-row').style.display !== 'none';
    const ivaPct    = ivaActive ? (parseFloat(document.getElementById('iva-pct').value) || 0) : 0;
    const iva   = sub * (ivaPct / 100);
    const total = sub + iva;
    const sym  = getCurrencySymbol();
    const code = getCurrencyCode();
    const fmt  = n => sym + n.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ' + code;
    document.getElementById('display-subtotal').textContent = fmt(sub);
    const dispIva = document.getElementById('display-iva');
    if (dispIva) dispIva.textContent = fmt(iva);
    document.getElementById('display-total').textContent = fmt(total);
    document.getElementById('hidden-subtotal').value = sub.toFixed(2);
    document.getElementById('hidden-iva').value      = iva.toFixed(2);
    document.getElementById('hidden-total').value    = total.toFixed(2);
}
function showIva() { cotizacionDirty = true; document.getElementById('iva-row').style.display = 'block'; document.getElementById('no-iva-row').style.display = 'none'; recalcAll(); }
function hideIva() { cotizacionDirty = true; document.getElementById('iva-row').style.display = 'none'; document.getElementById('no-iva-row').style.display = 'block'; recalcAll(); }
function previewImage(input, type) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const MAX_BYTES = 2 * 1024 * 1024; // 2MB
    if (file.size > MAX_BYTES) {
        mostrarToast('Imagen demasiado grande', 'El archivo pesa más de 2MB. Sube una imagen más ligera.', 'info', 5000);
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        const img    = document.getElementById(type + '-img');
        const hint   = document.getElementById(type + '-hint');
        const change = document.getElementById(type + '-change');
        const area   = document.getElementById(type + '-area');
        img.src = e.target.result;
        img.style.display = ''; hint.style.display = 'none';
        change.style.display = ''; area.classList.add('has-image');
    };
    reader.readAsDataURL(file);
}

// ═══════════════════════════════════════════════
// ADMIN PANEL
// ═══════════════════════════════════════════════
function openAdminPanel()  { document.getElementById('admin-modal')?.classList.add('open'); }
function closeAdminPanel() { document.getElementById('admin-modal')?.classList.remove('open'); }
document.getElementById('admin-modal')?.addEventListener('click', e => {
    if (e.target === document.getElementById('admin-modal')) closeAdminPanel();
});
const ESTADO_LABELS = { pendiente:'Pendiente', aprobada:'Aprobada', rechazada:'Rechazada' };
document.getElementById('admin-estado')?.addEventListener('change', function() {
    const pill = document.getElementById('estado-pill-toolbar');
    if (pill) pill.textContent = ESTADO_LABELS[this.value] || this.value;
});

function escH(s) { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; }

// ═══════════════════════════════════════════════
// TOASTS
// ═══════════════════════════════════════════════
const TOAST_ICONS = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
};
function mostrarToast(titulo, mensaje, tipo = 'info', duracionMs = 5000) {
    const cont = document.getElementById('toast-container');
    if (!cont) return;
    const el = document.createElement('div');
    el.className = `toast toast-${tipo}`;
    el.innerHTML = `
        <span class="toast-icon">${TOAST_ICONS[tipo] || TOAST_ICONS.info}</span>
        <div class="toast-body">
            <div class="toast-title">${escH(titulo)}</div>
            <div class="toast-msg">${escH(mensaje)}</div>
        </div>
        <button type="button" class="toast-close" aria-label="Cerrar">&times;</button>
    `;
    cont.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    const cerrar = () => {
        el.classList.remove('show');
        setTimeout(() => el.remove(), 220);
    };
    el.querySelector('.toast-close').addEventListener('click', cerrar);
    if (duracionMs > 0) setTimeout(cerrar, duracionMs);
}

// ═══════════════════════════════════════════════
// MODAL: GUARDADO EXITOSO
// ═══════════════════════════════════════════════
// ── Exportar PDF: usa el PDF generado en el servidor (Dompdf) para que
// salga limpio, sin la URL ni el título que el navegador agrega con
// window.print(). Si la cotización aún no se ha guardado (no tiene id),
// se usa window.print() como respaldo porque todavía no existe en la BD.
let cotizacionIdActual = <?= (int)$ctrl->cotizacionId ?>;
function exportarPdf() {
    if (cotizacionIdActual > 0) {
        window.open(`descargar_pdf.php?id=${cotizacionIdActual}`, '_blank');
    } else {
        prepararEspejosParaImprimir();
        window.print();
    }
}

function openSavedModal(subtexto) {
    const sub = document.getElementById('saved-modal-sub');
    if (sub && subtexto) sub.innerHTML = subtexto;
    document.getElementById('saved-modal')?.classList.add('open');
}
function closeSavedModal() { document.getElementById('saved-modal')?.classList.remove('open'); }
function exportarPdfDesdeModal() { closeSavedModal(); exportarPdf(); }
document.getElementById('saved-modal')?.addEventListener('click', e => {
    if (e.target === document.getElementById('saved-modal')) closeSavedModal();
});

// ═══════════════════════════════════════════════
// VALIDACIÓN
// ═══════════════════════════════════════════════
function limpiarErroresCampos() {
    document.querySelectorAll('.field-row.field-invalid').forEach(el => el.classList.remove('field-invalid'));
}
function marcarCampoInvalido(name) {
    const input = document.querySelector(`[name="${name}"]`);
    const row = input?.closest('.field-row');
    if (row) row.classList.add('field-invalid');
    return input;
}
function esEmailValido(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
function validarCotizacion(form) {
    limpiarErroresCampos();
    const v = name => (form.querySelector(`[name="${name}"]`)?.value ?? '').trim();
    const errores = [];
    let primerCampoInvalido = null;

    if (!v('atencion')) {
        errores.push('Falta el nombre de contacto del cliente (Atención).');
        primerCampoInvalido = primerCampoInvalido ?? marcarCampoInvalido('atencion');
    }
    if (!v('empresa_c')) {
        errores.push('Falta la empresa del cliente.');
        primerCampoInvalido = primerCampoInvalido ?? marcarCampoInvalido('empresa_c');
    }
    const email = v('empresa_email');
    if (email && !esEmailValido(email)) {
        errores.push('El correo de la empresa no parece válido.');
    }

    if (errores.length > 0) {
        mostrarToast('Faltan datos por completar', errores.join(' '), 'info', 6000);
        primerCampoInvalido?.focus();
        return false;
    }
    return true;
}

// ═══════════════════════════════════════════════
// GUARDAR
// ═══════════════════════════════════════════════
async function guardarCotizacion() {
    const form = document.getElementById('cot-form');
    if (!validarCotizacion(form)) return;
    const v = name => (form.querySelector(`[name="${name}"]`)?.value ?? '').trim();
    const items = [];
    document.querySelectorAll('#items-body tr').forEach(tr => {
        const cant = tr.querySelector('[name="items[][cant]"]')?.value ?? '';
        const sku  = tr.querySelector('[name="items[][sku]"]')?.value ?? '';
        const desc = tr.querySelector('[name="items[][descripcion]"]')?.value ?? '';
        const precio = tr.querySelector('[name="items[][precio]"]')?.value ?? '';
        if (!cant && !sku && !desc && !precio) return;
        items.push({
            cant, sku,
            unidad:    tr.querySelector('[name="items[][unidad]"]')?.value ?? 'PZA',
            descripcion: desc, precio,
            descuento: tr.querySelector('[name="items[][descuento]"]')?.value ?? '0',
            extendido: tr.querySelector('[name="items[][extendido]"]')?.value ?? '0',
            subtotal:  tr.querySelector('[name="items[][subtotal]"]')?.value  ?? '0',
        });
    });
    if (items.length === 0) {
        mostrarToast('Faltan datos por completar', 'Agrega al menos un ítem (producto o servicio) antes de guardar.', 'info', 5000);
        return;
    }
    const ivaActivo = document.getElementById('iva-row').style.display !== 'none';
    const payload = {
        id:                 parseInt(v('cotizacion_id') || '0'),
        fecha:              v('fecha'),
        cliente_nombre:     v('atencion'),
        cliente_telefono:   v('tel_o'),
        cliente_email:      v('empresa_email'),
        atencion:           v('atencion'),
        puesto:             v('puesto_c'),
        empresa:            v('empresa_c'),
        telefono_o:         v('tel_o'),
        telefono_m:         v('tel_m'),
        vigencia_dias:      v('vigencia_dias'),
        moneda_code:        document.getElementById('currency-select').value,
        tipo_cambio:        parseFloat(document.getElementById('exchange-rate')?.value || '1'),
        tiempo_entrega:     v('t_entrega'),
        condiciones_pago:   v('cond_pago'),
        vigencia_servicios: v('v_servicios'),
        lugar_entrega:      v('l_entrega'),
        aplica_iva:         ivaActivo,
        firma_nombre:       v('f_nombre'),
        firma_puesto:       v('f_puesto'),
        firma_telefono:     v('f_tel'),
        items,
    };
    // Firma y sello: solo se envían si el usuario subió un archivo nuevo en esta sesión
    // (input file con archivo seleccionado → la imagen ya quedó como DataURL en el <img> de preview)
    const firmaInput = form.querySelector('input[name="firma"]');
    const firmaImg    = document.getElementById('firma-img');
    if (firmaInput?.files?.[0] && firmaImg?.src?.startsWith('data:')) {
        payload.firma_base64 = firmaImg.src;
    }
    const selloInput = form.querySelector('input[name="sello"]');
    const selloImg    = document.getElementById('sello-img');
    if (selloInput?.files?.[0] && selloImg?.src?.startsWith('data:')) {
        payload.sello_base64 = selloImg.src;
    }
    const adminEstado  = document.getElementById('admin-estado');
    const adminMensaje = document.getElementById('admin-mensaje');
    const estadoPrevio = '<?= htmlspecialchars($ctrl->estadoActual ?? 'pendiente') ?>';
    if (adminEstado)  payload.estado = adminEstado.value;
    if (adminMensaje) {
        const msg = adminMensaje.value.trim();
        if (msg) { payload.mensaje_notificacion = msg; payload.marcar_pendiente = true; }
    }
    const btnG = document.getElementById('btn-guardar');
    const originalHtml = btnG.innerHTML;
    btnG.disabled = true;
    btnG.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> <span>Guardando…</span>`;
    try {
        const resp = await fetch('guardar_cotizacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await resp.json();
        if (data.ok) {
            cotizacionDirty = false;
            if (!payload.id && data.id) {
                history.replaceState(null, '', `cotizaciones.php?action=editar&id=${data.id}`);
                const ex = form.querySelector('[name="cotizacion_id"]');
                if (ex) ex.remove();
                const hid = document.createElement('input');
                hid.type = 'hidden'; hid.name = 'cotizacion_id'; hid.value = data.id;
                form.appendChild(hid);
                cotizacionIdActual = data.id;
            }
            btnG.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><polyline points="20 6 9 17 4 12"/></svg> <span>Guardado</span>`;
            setTimeout(() => { btnG.disabled = false; btnG.innerHTML = originalHtml; }, 2500);

            // ── Toast inmediato según si hubo cambio de estado (vista de admin) ──
            const huboEstado   = !!data.estado;
            const cambioEstado = huboEstado && data.estado !== estadoPrevio;
            const huboNotif    = !!data.notificacion_enviada;

            if (cambioEstado && data.estado === 'aprobada') {
                window.NotifSound?.playApprove();
            } else if (cambioEstado && data.estado === 'rechazada') {
                window.NotifSound?.playReject();
            } else {
                window.NotifSound?.playCreate();
            }

            if (huboNotif) {
                mostrarToast(
                    'Cambio aplicado y notificado',
                    `Estado actualizado a «${escH(ESTADO_LABELS[data.estado] || data.estado)}». Se avisó al vendedor.`,
                    'success', 6000
                );
            } else if (cambioEstado) {
                mostrarToast(
                    'Cambio de estado aplicado',
                    `La cotización ahora está en «${escH(ESTADO_LABELS[data.estado] || data.estado)}».`,
                    'success', 5000
                );
            } else {
                mostrarToast('Cotización guardada', 'Los cambios se guardaron correctamente.', 'info', 3500);
            }

            // Sincroniza la pastilla de estado en la toolbar por si vino de otro flujo
            if (huboEstado) {
                const pill = document.getElementById('estado-pill-toolbar');
                if (pill) pill.textContent = ESTADO_LABELS[data.estado] || data.estado;
            }

            // ── Modal: ¿deseas exportar el PDF ahora? ──
            let subtexto = 'Los cambios se guardaron correctamente.';
            if (cambioEstado) {
                subtexto = `Estado actualizado a <strong>${escH(ESTADO_LABELS[data.estado] || data.estado)}</strong>.` +
                           (huboNotif ? ' Se notificó al vendedor.' : '');
            }
            subtexto += '<br>¿Quieres descargar o imprimir el PDF de esta cotización?';
            openSavedModal(subtexto);
        } else {
            mostrarToast('Error al guardar', data.error ?? 'Error desconocido', 'info', 6000);
            btnG.disabled = false; btnG.innerHTML = originalHtml;
        }
    } catch(err) {
        mostrarToast('Error de conexión', err.message, 'info', 6000);
        btnG.disabled = false; btnG.innerHTML = originalHtml;
    }
}

// ═══════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    ['atencion', 'empresa_c'].forEach(name => {
        const input = document.querySelector(`[name="${name}"]`);
        input?.addEventListener('input', () => input.closest('.field-row')?.classList.remove('field-invalid'));
    });
    document.querySelectorAll('#items-body tr').forEach(tr => {
        const inp = tr.querySelector('[name="items[][cant]"]');
        if (inp) recalcRow(inp);
        // Ajustar altura de todos los textareas SKU y descripción
        const skuTA = tr.querySelector('textarea[name="items[][sku]"]');
        if (skuTA) { skuTA.addEventListener('input', () => autoResizeTA(skuTA)); autoResizeTA(skuTA); }
        const descTA = tr.querySelector('textarea[name="items[][descripcion]"]');
        if (descTA) { descTA.addEventListener('input', () => autoResizeTA(descTA)); autoResizeTA(descTA); }
    });
    const savedCode = '<?= htmlspecialchars($ctrl->cd['moneda_code']) ?>';
    const sel = document.getElementById('currency-select');
    if (sel && savedCode) {
        sel.value = savedCode;
        onCurrencyChange();
        const savedRate = parseFloat('<?= htmlspecialchars($ctrl->cd['tipo_cambio']) ?>');
        if (savedCode !== 'USD' && savedRate && savedRate !== 1) {
            document.getElementById('exchange-rate').value = savedRate.toFixed(4);
        }
    }
    fetchTodayRate();
});
</script>
</body>
</html>