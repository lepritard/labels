<?php
// Lake Forest Industries — Packing Slip Review
// review.php v1.41
function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Packing Slip Review — LF Label Generator</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; background: #f0f0f0; color: #222; min-height: 100vh; }
  .app-header { background: #1a1a1a; color: #fff; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
  .app-header h1 { font-size: 18px; font-weight: bold; letter-spacing: 0.02em; }
  .app-header span { font-size: 12px; color: #999; }
  .tabs { display: flex; gap: 0; margin-bottom: 0; border-bottom: 2px solid #000; }
  .tab-btn { padding: 8px 20px; font-size: 13px; font-weight: bold; cursor: pointer; background: #e8e8e8; border: 1px solid #ccc; border-bottom: none; color: #555; transition: background 0.15s; font-family: Arial, Helvetica, sans-serif; text-decoration: none; display: inline-block; }
  .tab-btn.active { background: #fff; color: #000; border-color: #000; border-bottom: 2px solid #fff; margin-bottom: -2px; }
  .tab-btn:hover:not(.active) { background: #d8d8d8; }
  .container { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
  .card { background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 20px 24px; margin-bottom: 20px; }
  .card h2 { font-size: 15px; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #000; padding-bottom: 6px; }
  label { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #444; display: block; margin-bottom: 6px; }
  .upload-zone {
    border: 2px dashed #aaa; border-radius: 4px; padding: 32px 24px; text-align: center;
    cursor: pointer; transition: border-color 0.15s, background 0.15s; background: #fafafa;
  }
  .upload-zone:hover, .upload-zone.dragover { border-color: #000; background: #f0f0f0; }
  .upload-zone input[type=file] { display: none; }
  .upload-zone .zone-icon { font-size: 36px; margin-bottom: 8px; }
  .upload-zone .zone-text { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
  .upload-zone .zone-sub  { font-size: 12px; color: #666; }
  .file-list { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; }
  .file-chip { background: #e8f0fe; border: 1px solid #aac4ff; border-radius: 20px; padding: 4px 12px; font-size: 12px; display: flex; align-items: center; gap: 6px; }
  .file-chip .remove { cursor: pointer; color: #a00; font-weight: bold; line-height: 1; font-size: 14px; }
  .file-chip .remove:hover { color: #f00; }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; font-size: 13px; font-weight: bold; font-family: Arial, Helvetica, sans-serif; border: 2px solid #000; border-radius: 3px; cursor: pointer; transition: background 0.15s, color 0.15s; text-decoration: none; }
  .btn-primary { background: #000; color: #fff; border-color: #000; }
  .btn-primary:hover { background: #333; }
  .btn-primary:disabled { background: #999; border-color: #999; cursor: not-allowed; }
  .btn-secondary { background: #fff; color: #000; }
  .btn-secondary:hover { background: #f0f0f0; }
  .btn-sm { padding: 5px 12px; font-size: 12px; }
  .btn-print { background: #1a6e2a; color: #fff; border-color: #1a6e2a; }
  .btn-print:hover { background: #145520; }
  .btn-row { display: flex; gap: 10px; margin-top: 16px; align-items: center; flex-wrap: wrap; }
  .note { font-size: 11px; color: #666; font-style: italic; line-height: 1.5; }
  .serial-range { font-size: 10px; color: #888; background: #f0f0f0; border: 1px solid #ddd; border-radius: 3px; padding: 1px 5px; font-family: monospace; white-space: nowrap; }

  /* Spinner */
  .spinner-wrap { display: none; align-items: center; gap: 12px; padding: 20px 0; }
  .spinner { width: 24px; height: 24px; border: 3px solid #ddd; border-top-color: #000; border-radius: 50%; animation: spin 0.7s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Error banner */
  .error-banner { background: #fff0f0; border: 1px solid #f00; border-radius: 3px; padding: 12px 16px; color: #900; font-size: 13px; margin-bottom: 16px; display: none; }

  /* Results section */
  #results { display: none; }
  .slip-block { margin-bottom: 28px; }
  .slip-heading {
    background: #1a1a1a; color: #fff; padding: 8px 14px;
    display: flex; align-items: center; justify-content: space-between;
    border-radius: 4px 4px 0 0; gap: 12px; flex-wrap: wrap;
  }
  .slip-heading .slip-title { font-weight: bold; font-size: 14px; }
  .slip-heading .slip-meta { font-size: 12px; color: #bbb; }
  .slip-heading .slip-meta span { margin-right: 16px; }
  .slip-heading .slip-meta strong { color: #fff; }

  /* Review table */
  table.review-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border: 1px solid #ccc; border-top: none; border-radius: 0 0 4px 4px; overflow: hidden; }
  table.review-table thead tr { background: #f5f5f5; }
  table.review-table th { padding: 8px 10px; text-align: left; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #555; border-bottom: 2px solid #ccc; white-space: nowrap; }
  table.review-table td { padding: 7px 10px; border-bottom: 1px solid #eee; vertical-align: middle; }
  table.review-table tbody tr:last-child td { border-bottom: none; }
  table.review-table tbody tr:hover { background: #fafafa; }
  table.review-table tbody tr.row-pallet { background: #f0f6ff; }
  table.review-table tbody tr.row-pallet:hover { background: #e8f0fc; }
  table.review-table tbody tr.row-wooden-case { background: #fff8ec; }
  table.review-table tbody tr.row-wooden-case:hover { background: #fff2de; }
  table.review-table tbody tr.row-secondary { opacity: 0.6; }
  table.review-table tbody tr.row-secondary td:first-child::before { content: '↳ '; color: #999; }

  .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
  .badge-carton  { background: #e8f0fe; color: #1a56db; }
  .badge-pallet  { background: #dbeafe; color: #1e40af; }
  .badge-case    { background: #fef3c7; color: #92400e; }
  .badge-copacked { background: #fde8ff; color: #7e22ce; margin-left: 4px; }
  .badge-t013    { background: #f0f0f0; color: #222; margin-left: 4px; border: 1px solid #ccc; }
  .badge-flag    { background: #fce8e8; color: #b91c1c; margin-left: 4px; }

  .inline-edit { width: 70px; padding: 4px 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; font-family: Arial, sans-serif; text-align: right; }
  .inline-edit:focus { outline: none; border-color: #000; box-shadow: 0 0 0 2px rgba(0,0,0,0.1); }
  .inline-edit.changed { border-color: #e69900; background: #fffbe6; }

  .total-labels { font-weight: bold; font-size: 14px; }
  .total-labels.pallet-count { color: #1e40af; }
  .total-labels.case-count   { color: #92400e; }

  .na-input { width: 130px; padding: 4px 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; font-family: Arial, sans-serif; }
  .na-input:focus { outline: none; border-color: #000; box-shadow: 0 0 0 2px rgba(0,0,0,0.1); }

  .slip-actions { padding: 10px 14px; background: #f9f9f9; border: 1px solid #ccc; border-top: none; border-radius: 0 0 4px 4px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

  @media (max-width: 800px) {
    table.review-table { font-size: 12px; }
    table.review-table th, table.review-table td { padding: 6px 7px; }
    .inline-edit { width: 55px; }
  }
  /* version footer */
  .version-footer {
    position: fixed; bottom: 0; right: 0;
    font-size: 10px; color: #bbb;
    background: rgba(255,255,255,0.75); backdrop-filter: blur(2px);
    padding: 2px 8px; border-top-left-radius: 4px;
    pointer-events: none; z-index: 9999; font-family: monospace;
    line-height: 1.8;
  }
</style>
</head>
<body>

<header class="app-header">
  <div>
    <h1>LF Label Generator</h1>
    <span>Lake Forest Industries — Warehouse Receiving</span>
  </div>
</header>
<div class="container" style="margin-top:16px;margin-bottom:0;padding-bottom:0;">
  <div class="tabs">
    <a class="tab-btn" href="index.php#boxes">📦 Box Labels</a>
    <a class="tab-btn" href="index.php#pallet">🏗️ Pallet Labels</a>
    <span class="tab-btn active">📋 Upload Packing Slip</span>
  </div>
</div>

<div class="container">

  <!-- Upload card -->
  <div class="card">
    <h2>Upload Packing Slips</h2>
    <div class="upload-zone" id="drop-zone">
      <div class="zone-icon">📂</div>
      <div class="zone-text">Click to select files, or drag &amp; drop here</div>
      <div class="zone-sub">Accepts .xlsx and .xls packing slip files — multiple files allowed</div>
    </div>
    <div class="file-list" id="file-list"></div>
    <div class="error-banner" id="error-banner"></div>
    <div class="btn-row">
      <button class="btn btn-primary" id="parse-btn" disabled onclick="parseSlips()">🔍 Parse &amp; Review</button>
      <div class="spinner-wrap" id="spinner"><div class="spinner"></div><span style="font-size:13px;color:#555;">Parsing packing slips…</span></div>
      <p class="note" id="upload-hint">Select at least one packing slip to continue.</p>
    </div>
  <input type="file" id="file-input" accept=".xlsx,.xls" multiple style="display:none">
  </div>

  <!-- Results -->
  <div id="results"></div>

</div>

<script>
// ── File selection ──────────────────────────────────────────────────────────
const fileInput = document.getElementById('file-input');
const dropZone  = document.getElementById('drop-zone');
const fileList  = document.getElementById('file-list');
const parseBtn  = document.getElementById('parse-btn');
const uploadHint = document.getElementById('upload-hint');
let selectedFiles = [];

fileInput.addEventListener('change', () => addFiles(Array.from(fileInput.files)));
dropZone.addEventListener('click', e => { e.stopPropagation(); fileInput.click(); });
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('dragover');
  addFiles(Array.from(e.dataTransfer.files));
});

function addFiles(files) {
  files.forEach(f => {
    if (!selectedFiles.find(x => x.name === f.name)) selectedFiles.push(f);
  });
  renderFileList();
  fileInput.value = '';
}

function removeFile(name) {
  selectedFiles = selectedFiles.filter(f => f.name !== name);
  renderFileList();
}

function renderFileList() {
  fileList.innerHTML = selectedFiles.map(f =>
    `<div class="file-chip">
      <span>📄 ${esc(f.name)}</span>
      <span class="remove" onclick="removeFile(${JSON.stringify(f.name)})" title="Remove">×</span>
    </div>`
  ).join('');
  parseBtn.disabled = selectedFiles.length === 0;
  uploadHint.style.display = selectedFiles.length === 0 ? '' : 'none';
}

// ── Parse ───────────────────────────────────────────────────────────────────
function parseSlips() {
  if (!selectedFiles.length) return;
  showError('');
  document.getElementById('results').style.display = 'none';
  document.getElementById('spinner').style.display = 'flex';
  parseBtn.disabled = true;

  const fd = new FormData();
  selectedFiles.forEach(f => fd.append('slips[]', f));

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'parse_slip.php');
  xhr.onload = function() {
    document.getElementById('spinner').style.display = 'none';
    parseBtn.disabled = false;
    let data;
    try {
      data = JSON.parse(xhr.responseText);
    } catch(e) {
      showError('JSON parse failed. Raw response: ' + xhr.responseText.substring(0, 500));
      return;
    }
    if (data.error) { showError(data.error); return; }
    try {
      renderResults(data);
    } catch(e) {
      showError('Render error: ' + e.message + ' | ' + (e.stack || '').substring(0, 300));
    }
  };
  xhr.onerror = function() {
    document.getElementById('spinner').style.display = 'none';
    parseBtn.disabled = false;
    showError('Request failed (XHR error). Status: ' + xhr.status);
  };
  xhr.ontimeout = function() {
    document.getElementById('spinner').style.display = 'none';
    parseBtn.disabled = false;
    showError('Request timed out.');
  };
  xhr.onloadend = function() {
    // Fallback: always hide spinner even if onload/onerror didn't fire
    document.getElementById('spinner').style.display = 'none';
    parseBtn.disabled = false;
  };
  xhr.timeout = 90000;
  xhr.send(fd);
}

// ── Render results ───────────────────────────────────────────────────────────
function renderResultsCore(data) {
  const wrap = document.getElementById('results');
  wrap.style.display = 'block';
  wrap.innerHTML = '';

  const warnings = data._warnings || [];
  delete data._warnings;

  if (warnings.length) {
    const wb = document.createElement('div');
    wb.className = 'error-banner';
    wb.style.display = 'block';
    wb.style.background = '#fffbe6';
    wb.style.borderColor = '#e69900';
    wb.style.color = '#7a5000';
    wb.innerHTML = '⚠️ ' + warnings.map(esc).join('<br>⚠️ ');
    wrap.appendChild(wb);
  }

  const slipKeys = Object.keys(data);
  if (!slipKeys.length) {
    wrap.innerHTML += '<div class="card"><p style="color:#666;">No data returned from parser.</p></div>';
    return;
  }

  slipKeys.forEach(fname => {
    const slip = data[fname];
    if (slip.error) {
      const eb = document.createElement('div');
      eb.className = 'card';
      eb.innerHTML = `<h2>⚠️ ${esc(fname)}</h2><p style="color:#a00;">${esc(slip.error)}</p>`;
      wrap.appendChild(eb); return;
    }
    wrap.appendChild(buildSlipBlock(fname, slip));
  });
}

function buildSlipBlock(fname, slip) {
  const meta = slip.meta || {};
  const groups = slip.label_groups || [];

  // Compute totals
  let totalBoxLabels = 0, totalPalletLabels = 0, totalCaseLabels = 0;
  groups.forEach(g => {
    if (g.container_type === 'carton') totalBoxLabels += g.total_labels;
    else if (g.container_type === 'pallet') totalPalletLabels += g.total_labels;
    else if (g.container_type === 'wooden_case') totalCaseLabels += g.total_labels;
  });

  const wrap = document.createElement('div');
  wrap.className = 'slip-block';

  // Heading bar
  const heading = document.createElement('div');
  heading.className = 'slip-heading';
  heading.innerHTML = `
    <span class="slip-title">📄 ${esc(fname)}</span>
    <span class="slip-meta">
      <span>PO: <strong>${esc(meta.po || '—')}</strong></span>
      <span>Container: <strong>${esc(meta.container_no || '—')}</strong></span>
      <span>Seal: <strong>${esc(meta.seal || '—')}</strong></span>
      <span>Date: <strong>${esc(meta.date || '—')}</strong></span>
    </span>`;
  wrap.appendChild(heading);

  // Shipment ID input (shared per slip)
  const naRow = document.createElement('div');
  naRow.style.cssText = 'padding:10px 14px;background:#f0f6ff;border:1px solid #ccc;border-top:none;border-bottom:none;display:flex;align-items:center;gap:12px;flex-wrap:wrap;';
  // Auto-derive Shipment ID: PO22643 → NA-22643 (from meta.po or filename)
  const rawPo = meta.po || fname;
  const poNum = (rawPo.match(/PO[_-]?(\d+)/i) || [])[1] || '';
  const autoNa = poNum ? ('NA-' + poNum) : '';
  naRow.innerHTML = `
    <label style="margin:0;font-size:12px;font-weight:bold;">Shipment ID:</label>
    <input class="na-input" type="text" id="na-${sanitize(fname)}"
           value="${autoNa}" placeholder="e.g. NA-22643" style="margin:0;">
    <span class="note" style="font-style:normal;">This will appear on every label for this packing slip.</span>
    <label style="margin:0 0 0 24px;font-size:12px;font-weight:bold;">Received Date:</label>
    <input type="date" id="date-${sanitize(fname)}" style="font-size:13px;padding:3px 6px;border:1px solid #ccc;border-radius:3px;" value="${new Date().toLocaleDateString('en-CA')}">
    <label style="margin:0 0 0 24px;font-size:12px;font-weight:bold;">Starting Serial #:</label>
    <input type="number" min="1" id="serial-start-${sanitize(fname)}" value="1"
           style="width:70px;font-size:13px;padding:3px 6px;border:1px solid #ccc;border-radius:3px;"
           oninput="refreshSerialRanges('${sanitize(fname)}')">`;
  wrap.appendChild(naRow);

  // Table
  const tableWrap = document.createElement('div');
  tableWrap.style.overflowX = 'auto';
  const table = document.createElement('table');
  table.className = 'review-table';
  table.innerHTML = `
    <thead><tr>
      <th>#</th>
      <th>Customer</th>
      <th>Part Number</th>
      <th>Type</th>
      <th style="text-align:right;">Units</th>
      <th style="text-align:right;">Pcs / Unit</th>
      <th style="text-align:right;">Labels × Each</th>
      <th style="text-align:right;">Total Labels</th>
      <th style="text-align:right;font-size:11px;color:#888;">Serials</th>
      <th>Actions</th>
    </tr></thead>
    <tbody id="tbody-${sanitize(fname)}"></tbody>`;
  tableWrap.appendChild(table);
  wrap.appendChild(tableWrap);

  // Actions bar
  const actBar = document.createElement('div');
  actBar.className = 'slip-actions';
  actBar.id = 'actions-' + sanitize(fname);
  wrap.appendChild(actBar);

  // Populate rows — sorted by customer asc, then base_part asc.
  // nonstd_remainder rows travel with their parent (not sorted independently).
  setTimeout(() => {
    const tbody = document.getElementById('tbody-' + sanitize(fname));

    // Separate true primary rows (not nonstd_remainder, not co_packed_secondary)
    const primaryGroups = groups.filter(g =>
      !g.flags?.includes('nonstd_remainder') &&
      !g.flags?.includes('co_packed_secondary')
    );

    // Sort primaries: customer asc, then base_part asc
    primaryGroups.sort((a, b) => {
      const ca = (a.customer || '').toLowerCase();
      const cb = (b.customer || '').toLowerCase();
      if (ca !== cb) return ca < cb ? -1 : 1;
      const pa = (a.base_part || a.part_number || '').toLowerCase();
      const pb = (b.base_part || b.part_number || '').toLowerCase();
      return pa < pb ? -1 : pa > pb ? 1 : 0;
    });

    // Re-attach dependents (nonstd_remainder + co_packed_secondary) right after
    // their parent in sorted order.
    // nonstd_remainder: matched by base_part on the immediately following group.
    // co_packed_secondary: matched by co_packed_parent field set by the parser.
    const sortedGroups = [];
    primaryGroups.forEach(g => {
      sortedGroups.push(g);
      const origIdx = groups.indexOf(g);
      const ns = groups[origIdx + 1];
      if (ns?.flags?.includes('nonstd_remainder') && ns.base_part === g.base_part) {
        sortedGroups.push(ns);
      }
      // Attach any co_packed_secondary rows whose parent is this group
      groups.forEach(sec => {
        if (sec.flags?.includes('co_packed_secondary') &&
            sec.co_packed_parent === g.base_part) {
          sortedGroups.push(sec);
        }
      });
    });

    // Update slipData so printRow index lookups stay correct
    slipData[fname].label_groups = sortedGroups;

    sortedGroups.forEach((g, idx) => {
      if (g.flags && g.flags.includes('nonstd_remainder')) return;
      const row = buildRow(g, idx + 1, fname);
      tbody.appendChild(row);
    });
    rebuildActions(fname, slip, sortedGroups, totalBoxLabels, totalPalletLabels, totalCaseLabels);
    refreshSerialRanges(sanitize(fname));
  }, 0);

  return wrap;
}

function buildRow(g, rowNum, fname) {
  const tr = document.createElement('tr');
  const ct = g.container_type;
  if (ct === 'pallet') tr.classList.add('row-pallet');
  else if (ct === 'wooden_case') tr.classList.add('row-wooden-case');
  if (g.flags && g.flags.includes('co_packed_secondary')) tr.classList.add('row-secondary');

  const typeBadge = ct === 'carton'
    ? '<span class="badge badge-carton">Carton</span>'
    : ct === 'pallet'
      ? '<span class="badge badge-pallet">Pallet</span>'
      : '<span class="badge badge-case">Wooden Case</span>';

  let flags = '';
  if (g.flags) {
    if (g.flags.includes('co_packed_secondary')) flags += '<span class="badge badge-copacked">Co-pack</span>';
    if (g.flags.includes('t013_prepped'))        flags += '<span class="badge badge-t013">T013</span>';
  }

  const revStr = g.revision ? `<span style="font-size:10px;color:#888;margin-left:4px;">rev ${esc(g.revision)}</span>` : '';
  const lpu    = g.labels_per_unit;
  const rowId  = `row-${sanitize(fname)}-${rowNum}`;

  tr.dataset.fname  = fname;
  tr.dataset.rownum = rowNum;
  tr.id = rowId;

  // Look for a nonstd_remainder sibling with the same base_part immediately after
  const allGroups = (slipData[fname] || {}).label_groups || [];
  const myIdx     = rowNum - 1; // 0-based index of this group
  const nsSibling = (allGroups[myIdx + 1]?.flags?.includes('nonstd_remainder')
                     && allGroups[myIdx + 1]?.base_part === g.base_part)
                    ? allGroups[myIdx + 1] : null;

  // Total shown for main row — if nonstd sibling exists, show full merged total
  const stdBoxes   = g.num_labels;
  const displayTotal = nsSibling
    ? (nsSibling.nonstd_box_num || (stdBoxes + nsSibling.num_labels)) * lpu
    : g.total_labels;

  tr.innerHTML = `
    <td style="color:#999;font-size:12px;">${rowNum}</td>
    <td>${esc(g.customer)}</td>
    <td style="font-family:monospace;font-size:12px;white-space:nowrap;">
      ${esc(g.base_part || g.part_number)}${revStr}${flags}
    </td>
    <td>${typeBadge}</td>
    <td style="text-align:right;">
      <input class="inline-edit" type="number" min="1"
             id="${rowId}-units"
             value="${g.num_labels}"
             onchange="recalcRow('${sanitize(fname)}', ${rowNum - 1})"
             oninput="markChanged(this)">
    </td>
    <td style="text-align:right;">
      <input class="inline-edit" type="number" min="1"
             id="${rowId}-pcs"
             value="${g.pcs_per_box || ''}"
             placeholder="—"
             onchange="recalcRow('${sanitize(fname)}', ${rowNum - 1})"
             oninput="markChanged(this)">
    </td>
    <td style="text-align:right;font-size:12px;color:#666;">
      × <strong>${lpu}</strong>
    </td>
    <td style="text-align:right;">
      <span class="total-labels${ct === 'pallet' ? ' pallet-count' : ct === 'wooden_case' ? ' case-count' : ''}"
            id="${rowId}-total">${displayTotal}</span>
    </td>
    <td style="text-align:right;">
      <span class="serial-range" id="${rowId}-serial"></span>
    </td>
    <td>
      <button class="btn btn-print btn-sm"
              onclick="printRow('${sanitize(fname)}', ${rowNum - 1})">🖨️ Print</button>
    </td>`;

  if (nsSibling) {
    const nsId = `${rowId}-ns`;
    const frag = document.createDocumentFragment();
    frag.appendChild(tr);
    const tempDiv = document.createElement('tbody');
    tempDiv.innerHTML = `
      <tr class="nonstd-subrow" id="${nsId}-tr">
        <td style="color:#bbb;font-size:11px;padding-left:24px;">↳</td>
        <td style="color:#888;font-size:11px;">non-std box</td>
        <td style="font-family:monospace;font-size:11px;color:#555;white-space:nowrap;">
          ${esc(nsSibling.base_part || nsSibling.part_number)}${nsSibling.revision ? `<span style="font-size:9px;color:#aaa;margin-left:3px;">rev ${esc(nsSibling.revision)}</span>` : ''}
          <span class="badge" style="background:#e8f0ff;color:#1a4a9e;margin-left:4px;font-size:9px;">Non-std box</span>
        </td>
        <td><span class="badge badge-carton" style="opacity:.6;">Carton</span></td>
        <td style="text-align:right;">
          box <strong>${nsSibling.nonstd_box_num || (stdBoxes + nsSibling.num_labels)}</strong> of <strong>${nsSibling.nonstd_box_num || (stdBoxes + nsSibling.num_labels)}</strong>
        </td>
        <td style="text-align:right;">
          <input class="inline-edit" type="number" min="1"
                 id="${nsId}-pcs"
                 value="${nsSibling.pcs_per_box || ''}"
                 placeholder="—"
                 oninput="markChanged(this)">
          <span style="font-size:10px;color:#999;margin-left:2px;">pcs</span>
        </td>
        <td style="text-align:right;font-size:11px;color:#aaa;">× 5 copies</td>
        <td style="text-align:right;font-size:12px;color:#888;">5</td>
        <td></td>
      </tr>`;
    frag.appendChild(tempDiv.firstElementChild);
    return frag;
  }

  return tr;
}

// ── Per-row data store (mirrors parsed JSON, updated on edit) ───────────────
const slipData = {};

function sanitize(s) { return s.replace(/[^a-zA-Z0-9_]/g, '_'); }

function renderResultsData(data) {
  Object.keys(data).forEach(fname => {
    if (fname === '_warnings') return;
    slipData[fname] = data[fname];
  });
}

function markChanged(el) { el.classList.add('changed'); }

function recalcRow(safeFname, idx) {
  // Find the original fname from slipData
  const fname = Object.keys(slipData).find(k => sanitize(k) === safeFname);
  if (!fname) return;
  const g = slipData[fname].label_groups[idx];
  const rowNum = idx + 1;
  const rowId = `row-${safeFname}-${rowNum}`;
  const units = parseInt(document.getElementById(`${rowId}-units`).value) || 0;
  const total = units * g.labels_per_unit;
  document.getElementById(`${rowId}-total`).textContent = total;
  refreshSerialRanges(safeFname);
}

function recalcNonstd(safeFname, parentIdx) {
  const fname = Object.keys(slipData).find(k => sanitize(k) === safeFname);
  if (!fname) return;
  const ns = (slipData[fname].label_groups || [])[parentIdx + 1];
  if (!ns?.flags?.includes('nonstd_remainder')) return;
  const nsId = `row-${safeFname}-${parentIdx + 1}-ns`;
  const total = (ns.nonstd_copies || 5);
  const el = document.getElementById(`${nsId}-total`);
  if (el) el.textContent = total;
}

function rebuildActions(fname, slip, groups, totalBox, totalPallet, totalCase) {
  const bar = document.getElementById('actions-' + sanitize(fname));
  if (!bar) return;

  slipData[fname] = slip; // store for later

  let parts = [];
  if (totalBox > 0)     parts.push(`<strong>${totalBox}</strong> box label${totalBox !== 1 ? 's' : ''}`);
  if (totalPallet > 0)  parts.push(`<strong>${totalPallet}</strong> pallet label${totalPallet !== 1 ? 's' : ''}`);
  if (totalCase > 0)    parts.push(`<strong>${totalCase}</strong> wooden case label${totalCase !== 1 ? 's' : ''}`);

  bar.innerHTML = `
    <span style="font-size:13px;color:#444;">
      <strong>Totals:</strong> ${parts.join(' &nbsp;+&nbsp; ')}
    </span>
    <p class="note" style="font-style:normal;margin:0;">Review and edit counts above, then use the Print button on each row.</p>`;
}

// ── Print helpers ────────────────────────────────────────────────────────────
// ── Serial range helpers ─────────────────────────────────────────────────
// boxCountForGroup: number of PHYSICAL BOXES a group represents.
//   carton primary         -> num_labels (or live input if edited)
//   co_packed_secondary    -> num_labels (palletized independently after receiving)
//   nonstd_remainder       -> 0  (counted by the parent row)
//   pallet / wooden_case   -> 0  (not box serials)
function boxCountForGroup(g, allGrps, safeFname, rowNum) {
  if (g.container_type !== 'carton') return 0;
  if (g.flags?.includes('nonstd_remainder')) return 0;
  const myIdx = allGrps.indexOf(g);
  const ns = (allGrps[myIdx + 1]?.flags?.includes('nonstd_remainder')
              && allGrps[myIdx + 1]?.base_part === g.base_part)
             ? allGrps[myIdx + 1] : null;
  const liveUnits = parseInt(document.getElementById(`row-${safeFname}-${rowNum}-units`)?.value);
  const units = (!isNaN(liveUnits) && liveUnits > 0) ? liveUnits : g.num_labels;
  return ns ? (ns.nonstd_box_num || (units + (ns.num_labels || 1))) : units;
}

// refreshSerialRanges: assigns sequential serial ranges to every row,
// starting from the per-slip "Starting Serial #" input.
function refreshSerialRanges(safeFname) {
  const fname = Object.keys(slipData).find(k => sanitize(k) === safeFname);
  if (!fname) return;
  const startEl = document.getElementById('serial-start-' + safeFname);
  const _startRaw = parseInt(startEl?.value);
  const _hasSerial = (_startRaw > 0);
  let serial = _hasSerial ? _startRaw : 1;
  const groups = (slipData[fname] || {}).label_groups || [];
  groups.forEach((g, idx) => {
    if (g.flags?.includes('nonstd_remainder')) return;
    const rowNum = idx + 1;
    const el = document.getElementById(`row-${safeFname}-${rowNum}-serial`);
    if (!el) return;
    const count = boxCountForGroup(g, groups, safeFname, rowNum);
    if (count === 0) {
      el.textContent = '—'; el.style.opacity = '0.35';
    } else {
      const first = serial, last = serial + count - 1;
      if (_hasSerial) {
        el.style.opacity = '1';
        el.textContent = first === last ? `#${first}` : `#${first}\u2013${last}`;
      } else {
        el.style.opacity = '0.35';
        el.textContent = '\u2014';
      }
      serial = last + 1;
    }
  });
}

function printRow(safeFname, idx) {
  const fname = Object.keys(slipData).find(k => sanitize(k) === safeFname);
  if (!fname) return;
  const g     = slipData[fname].label_groups[idx];
  const meta  = slipData[fname].meta || {};
  const naVal = document.getElementById('na-' + safeFname)?.value?.trim() || '';
  const rowNum = idx + 1;
  const rowId  = `row-${safeFname}-${rowNum}`;

  const units = parseInt(document.getElementById(`${rowId}-units`)?.value) || g.num_labels;
  const pcs   = parseInt(document.getElementById(`${rowId}-pcs`)?.value)   || g.pcs_per_box || 0;

  // Read this row's own serial span; fall back to 0 (omit_serial) if blank.
  const _srEl   = document.getElementById(`row-${safeFname}-${rowNum}-serial`);
  const _srNums = (_srEl ? _srEl.textContent : '').replace(/[^0-9]/g, ' ').trim().split(/\s+/);
  const _startInput = document.getElementById('serial-start-' + safeFname);
  const _startVal   = parseInt(_startInput?.value);
  const seqStart = (_srNums[0] && /^\d+$/.test(_srNums[0]))
                     ? parseInt(_srNums[0])
                     : (_startVal > 0 ? _startVal : 0);
  const params = buildPreviewParams(g, meta, naVal, units, pcs, seqStart, safeFname);
  window.open('preview.php?' + params, '_blank');
}

// printAll removed in v1.28

function buildPreviewParams(g, meta, naVal, units, pcs, seqStart, safeFnameCtx) {
  // Resolve real fname key for slipData lookup
  safeFnameCtx = safeFnameCtx || '';
  const _rFname = Object.keys(slipData).find(k => sanitize(k) === safeFnameCtx) || safeFnameCtx;
  // Patch allGrps to use resolved key
  const _allGrpsResolved = (slipData[_rFname] || {}).label_groups || [];
  const ct = g.container_type;
  // Read received_date from the per-slip date picker; fall back to today
  const _dateEl = document.getElementById('date-' + (safeFnameCtx || ''));
  const today = (_dateEl && _dateEl.value) ? _dateEl.value : new Date().toLocaleDateString('en-CA');
  const p = new URLSearchParams();
  // Use base_part for the barcode (strips customer prefix + revision)
  const barcodePart = g.base_part || g.part_number;

  if (ct === 'carton') {
    p.set('type',          'box');
    p.set('customer',      g.customer);
    p.set('na_number',     naVal || '');
    p.set('part_number',   barcodePart);
    p.set('received_date', today);
    p.set('std_qty',       pcs);
    p.set('copies',        '1');
    p.set('seq_start',     (seqStart > 0 ? seqStart : 0).toString());
    if (g.revision) p.set('revision', g.revision);
    if (g.flags && g.flags.includes('t013_prepped')) p.set('t013_prepped', '1');

    // Check for nonstd_remainder sibling (same base_part, next group)
    const allGrps = _allGrpsResolved;
    const gIdx    = allGrps.indexOf(g);
    const nsSib   = (gIdx >= 0
                     && allGrps[gIdx + 1]?.flags?.includes('nonstd_remainder')
                     && allGrps[gIdx + 1]?.base_part === g.base_part)
                    ? allGrps[gIdx + 1] : null;

    if (nsSib) {
      const nsBoxNum = nsSib.nonstd_box_num || (units + nsSib.num_labels);
      const rowId    = `row-${safeFnameCtx}-${gIdx + 1}`;
      const nsPcs    = parseInt(document.getElementById(`${rowId}-ns-pcs`)?.value) || nsSib.pcs_per_box || 0;
      const nsCopies = nsSib.nonstd_copies || 5;
      p.set('total_boxes', nsBoxNum.toString());
      p.set('nonstd_json', JSON.stringify([{ box: String(nsBoxNum), qty: String(nsPcs), copies: String(nsCopies) }]));
    } else {
      p.set('total_boxes', units.toString());
    }
  } else {
    // pallet or wooden_case
    p.set('type',          'pallet');
    p.set('customer',      g.customer);
    p.set('na_number',     naVal || '');
    p.set('part_number',   barcodePart);
    p.set('received_date', today);
    p.set('copies',        g.labels_per_unit.toString());
    p.set('pallet_num',    '1');
    p.set('total_pallets', units);
    p.set('pallet_qty',    pcs ? (pcs * units).toString() : '');
    p.set('pallet_boxes',  '');
    p.set('pallet_box_qty', pcs ? pcs.toString() : '');
    if (ct === 'wooden_case') p.set('unit_label', 'WOODEN CASE');
    if (g.revision) p.set('revision', g.revision);
    if (g.flags && g.flags.includes('t013_prepped')) p.set('t013_prepped', '1');
  }
  return p.toString();
}

// ── Utility ──────────────────────────────────────────────────────────────────
function esc(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showError(msg) {
  const el = document.getElementById('error-banner');
  if (!msg) { el.style.display = 'none'; el.textContent = ''; return; }
  el.textContent = '⚠️ ' + msg;
  el.style.display = 'block';
}

// Single entry point — stores data then renders
function renderResults(data) {
  renderResultsData(data);
  renderResultsCore(data);
}
</script>
  <div class="version-footer">LF Label Generator&nbsp;v1.41 &middot; review</div>
</body>
</html>
