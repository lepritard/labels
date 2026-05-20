<?php
// Lake Forest Industries — Warehouse Label Generator
// index.php v1.49

// ── known_parts.json loader ──────────────────────────────────────────────
// Same directory as index.php on server. Embedded as inline JS const KP.
// Zero AJAX — autocomplete works with no extra server round-trips.
$kp_path     = __DIR__ . '/known_parts.json';
$known_parts = [];
if (file_exists($kp_path)) {
    $raw = file_get_contents($kp_path);
    if ($raw !== false) $known_parts = json_decode($raw, true) ?? [];
}
$kp_json   = json_encode($known_parts, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
$customers = [];
foreach ($known_parts as $e) {
    $c = $e['customer'] ?? '';
    if ($c !== '' && !in_array($c, $customers, true)) $customers[] = $c;
}
sort($customers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LF Label Generator</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; background: #f0f0f0; color: #222; min-height: 100vh; }
  .app-header { background: #1a1a1a; color: #fff; padding: 12px 24px; display: flex; align-items: center; gap: 16px; }
  .app-header h1 { font-size: 18px; font-weight: bold; letter-spacing: 0.02em; }
  .app-header span { font-size: 12px; color: #999; }
  .container { max-width: 880px; margin: 24px auto; padding: 0 16px; }
  .card { background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 20px 24px; margin-bottom: 20px; }
  .card h2 { font-size: 15px; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #000; padding-bottom: 6px; }
  .tabs { display: flex; gap: 0; margin-bottom: 0; border-bottom: 2px solid #000; }
  .tab-btn { padding: 8px 20px; font-size: 13px; font-weight: bold; cursor: pointer; background: #e8e8e8; border: 1px solid #ccc; border-bottom: none; color: #555; transition: background 0.15s; font-family: Arial, Helvetica, sans-serif; }
  .tab-btn.active { background: #fff; color: #000; border-color: #000; border-bottom: 2px solid #fff; margin-bottom: -2px; }
  .tab-btn:hover:not(.active) { background: #d8d8d8; }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; }
  .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
  .form-group { display: flex; flex-direction: column; gap: 4px; }
  .form-group.full { grid-column: 1 / -1; }
  label { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #444; }
  input, select { padding: 7px 10px; border: 1px solid #aaa; border-radius: 3px; font-size: 13px; font-family: Arial, Helvetica, sans-serif; background: #fff; color: #000; transition: border-color 0.15s, box-shadow 0.15s; }
  input:focus, select:focus { outline: none; border-color: #000; box-shadow: 0 0 0 2px rgba(0,0,0,0.12); }
  .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; color: #666; margin: 16px 0 8px; border-top: 1px solid #eee; padding-top: 12px; }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; font-size: 13px; font-weight: bold; font-family: Arial, Helvetica, sans-serif; border: 2px solid #000; border-radius: 3px; cursor: pointer; transition: background 0.15s, color 0.15s; text-decoration: none; }
  .btn-primary { background: #000; color: #fff; }
  .btn-primary:hover { background: #333; }
  .btn-secondary { background: #fff; color: #000; }
  .btn-secondary:hover { background: #f0f0f0; }
  .btn-row { display: flex; gap: 10px; margin-top: 20px; align-items: center; flex-wrap: wrap; }
  .note { font-size: 11px; color: #666; font-style: italic; line-height: 1.5; }
  .note strong { color: #333; font-style: normal; }
  .non-std-section { background: #fffbe6; border: 1px solid #e0c000; border-radius: 3px; padding: 12px 14px; margin-top: 12px; }
  .non-std-section .section-title { border-top: none; padding-top: 0; margin-top: 0; color: #7a6000; }
  .nonstd-row { background: #fff8d0; border: 1px solid #d4b800; border-radius: 3px; padding: 10px 12px; margin-top: 8px; position: relative; }
  .nonstd-row .remove-btn { position: absolute; top: 8px; right: 10px; background: none; border: none; font-size: 16px; cursor: pointer; color: #a00; line-height: 1; padding: 0; font-family: Arial, sans-serif; }
  .nonstd-row .remove-btn:hover { color: #f00; }
  .mixed-pallet-section { background: #f0f6ff; border: 1px solid #99bbdd; border-radius: 3px; padding: 12px 14px; margin-top: 12px; }
  .mixed-pallet-section .section-title { border-top: none; padding-top: 0; margin-top: 0; color: #1a4a7a; }
  .hidden { display: none !important; }
  @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } .form-grid.cols-3 { grid-template-columns: 1fr 1fr; } }
  /* v1.49 autofill indicator */
  .kp-filled { border-color: #28a745 !important; background: #f0fff4 !important; }
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

<div class="container">

  <div class="tabs">
    <button class="tab-btn active" id="tab-btn-boxes" onclick="switchTab('boxes', this)">📦 Box Labels</button>
    <button class="tab-btn" id="tab-btn-pallet" onclick="switchTab('pallet', this)">🏗️ Pallet Labels</button>
    <a class="tab-btn" href="review.php" style="text-decoration:none;">📋 Upload Packing Slip</a>
  </div>

  <!-- ===================== BOX LABELS TAB ===================== -->
  <div id="tab-boxes" class="tab-panel active card" style="border-top:none;border-radius:0 0 4px 4px;">
    <form id="form-boxes" method="GET" action="preview.php" target="_blank" onsubmit="return buildNonstdParams()">
      <input type="hidden" name="type" value="box">

      <div class="form-grid">
        <div class="form-group">
          <label>Customer Name</label>
          <input type="text" id="kp-customer" name="customer" placeholder="e.g. TurboChef"
                 autocomplete="off" list="kp-customer-list">
          <datalist id="kp-customer-list">
            <?php foreach ($customers as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="form-group">
          <label>Shipment ID <span style="font-weight:normal;text-transform:none;letter-spacing:0;">(optional)</span></label>
          <input type="text" name="na_number" placeholder="e.g. NA-222610">
        </div>
        <div class="form-group">
          <label>Part Number</label>
          <input type="text" id="kp-part" name="part_number" placeholder="e.g. ENC-1724"
                 autocomplete="off" list="kp-part-list" required>
          <datalist id="kp-part-list">
            <?php foreach (array_keys($known_parts) as $pn): ?>
            <option value="<?= htmlspecialchars($pn) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="form-group" id="kp-date-row" style="overflow:hidden;max-height:0;opacity:0;transition:max-height .28s ease,opacity .22s ease;">
          <label>Received Date</label>
          <input type="date" id="kp-received-date" name="received_date" value="<?php echo date('Y-m-d'); ?>">
        </div>
      </div>

      <div class="section-title">Box Count &amp; Quantity</div>
      <div class="form-grid cols-3">
        <div class="form-group">
          <label>Total Boxes</label>
          <input type="number" name="total_boxes" min="1" placeholder="e.g. 180" required>
        </div>
        <div class="form-group">
          <label>Standard Qty Per Box</label>
          <div id="kp-qty-wrap">
            <input type="number" id="kp-std-qty" name="std_qty" min="1" placeholder="e.g. 6" required>
          </div>
          <div id="kp-nonstd-banner" style="display:none;background:#fff8e1;border:1px solid #f9a825;border-radius:3px;padding:8px 12px;margin-top:5px;font-size:11px;line-height:1.5;">
            <span id="kp-banner-txt"></span>
            <div style="margin-top:5px;display:flex;gap:6px;flex-wrap:wrap;">
              <button type="button" id="kp-btn-yes" style="padding:2px 8px;border-radius:3px;border:1px solid #bbb;font-size:11px;cursor:pointer;background:#fff;">Yes &mdash; add as NON-STD box</button>
              <button type="button" id="kp-btn-no"  style="padding:2px 8px;border-radius:3px;border:1px solid #bbb;font-size:11px;cursor:pointer;background:#fff;">No &mdash; use as standard</button>
              <button type="button" id="kp-btn-dim" style="padding:2px 8px;border-radius:3px;border:1px solid #bbb;font-size:11px;cursor:pointer;background:#fff;">Dismiss</button>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Label Copies Per Box</label>
          <input type="number" name="copies" min="1" max="10" value="1">
        </div>
        <div class="form-group">
          <label>Starting Sequence #</label>
          <input type="number" id="kp-seq-start" name="seq_start" min="0" max="9999" value="0">
          <span class="note" style="margin-top:3px;">Enter a starting number to include serial tags &amp; received date on labels.</span>
        </div>
      </div>

      <!-- Non-standard boxes — dynamic rows -->
      <div class="non-std-section">
        <div class="section-title">⚠️ Non-Standard Boxes (Optional)</div>
        <p class="note" style="margin-bottom:10px;">
          Use this when one or more boxes have a different quantity than the rest (e.g., partial boxes).
          The ◄qty► arrow style and mirrored layout will be applied automatically.
          5 copies are printed per non-standard box. A new entry row appears automatically as you fill in each one.
        </p>
        <div id="nonstd-rows"></div>
      </div>

      <!-- Hidden container for serialized nonstd data submitted with form -->
      <div id="nonstd-hidden-inputs"></div>

      <div class="btn-row">
        <button type="submit" class="btn btn-primary">🖨️ Generate &amp; Preview Labels</button>
        <p class="note"><strong>Tip:</strong> Labels open in a new tab. Use Ctrl+P (or Cmd+P) to print.</p>
      </div>
    </form>
  </div>

  <!-- ===================== PALLET LABELS TAB ===================== -->
  <div id="tab-pallet" class="tab-panel card" style="border-top:none;border-radius:0 0 4px 4px;">
    <form method="GET" action="preview.php" target="_blank" id="form-pallet">
      <input type="hidden" name="type" value="pallet">

      <div class="form-grid">
        <div class="form-group">
          <label>Customer Name</label>
          <input type="text" name="customer" placeholder="e.g. Avtron Power Solutions" required>
        </div>
        <div class="form-group">
          <label>Shipment ID <span style="font-weight:normal;text-transform:none;letter-spacing:0;">(optional)</span></label>
          <input type="text" name="na_number" placeholder="e.g. NA-222610">
        </div>
        <div class="form-group">
          <label>Received Date</label>
          <input type="date" name="received_date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="form-group">
          <label>Label Copies (sides of pallet)</label>
          <input type="number" name="copies" min="1" max="10" value="4">
        </div>
      </div>

      <div id="single-pallet-section">
        <div class="section-title">Pallet Details</div>
        <div class="form-grid cols-3">
          <div class="form-group">
            <label>Part Number</label>
            <input type="text" name="part_number" placeholder="e.g. B22519-42">
          </div>
          <div class="form-group">
            <label>Pallet # (this pallet)</label>
            <input type="number" name="pallet_num" min="1" placeholder="e.g. 2">
          </div>
          <div class="form-group">
            <label>Total Pallets</label>
            <input type="number" name="total_pallets" min="1" placeholder="e.g. 2">
          </div>
          <div class="form-group">
            <label>Total Qty on Pallet</label>
            <input type="number" name="pallet_qty" min="1" placeholder="e.g. 1480">
          </div>
          <div class="form-group">
            <label>Boxes on Pallet</label>
            <input type="number" name="pallet_boxes" min="1" placeholder="e.g. 37">
          </div>
          <div class="form-group">
            <label>Qty Per Box</label>
            <input type="number" name="pallet_box_qty" min="1" placeholder="e.g. 40">
          </div>
        </div>
      </div>

      <div style="margin-top:14px;">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;text-transform:none;letter-spacing:0;">
          <input type="checkbox" name="mixed" value="1" id="mixed-toggle" onchange="toggleMixed()"> This is a mixed pallet (two part numbers)
        </label>
      </div>

      <div id="mixed-pallet-section" class="mixed-pallet-section hidden">
        <div class="section-title">🔀 Mixed Pallet — Second Part Number</div>
        <div class="form-grid cols-3">
          <div class="form-group">
            <label>Part Number 2</label>
            <input type="text" name="part_number_2" placeholder="e.g. A23968">
          </div>
          <div class="form-group">
            <label>Pallet # (part 2)</label>
            <input type="number" name="pallet_num_2" min="1" placeholder="e.g. 1">
          </div>
          <div class="form-group">
            <label>Total Pallets (part 2)</label>
            <input type="number" name="total_pallets_2" min="1" placeholder="e.g. 1">
          </div>
          <div class="form-group">
            <label>Total Qty (part 2)</label>
            <input type="number" name="pallet_qty_2" min="1" placeholder="e.g. 840">
          </div>
          <div class="form-group">
            <label>Boxes (part 2)</label>
            <input type="number" name="pallet_boxes_2" min="1" placeholder="e.g. 21">
          </div>
          <div class="form-group">
            <label>Qty Per Box (part 2)</label>
            <input type="number" name="pallet_box_qty_2" min="1" placeholder="e.g. 40">
          </div>
        </div>
      </div>

      <div class="btn-row">
        <button type="submit" class="btn btn-primary">🖨️ Generate &amp; Preview Labels</button>
        <p class="note"><strong>Tip:</strong> Labels open in a new tab. Use Ctrl+P (or Cmd+P) to print.</p>
      </div>
    </form>
  </div>

</div><!-- /container -->

<script>
// ── Tab switching ──────────────────────────────────────────────
// Activate tab from URL hash (e.g. index.php#pallet)
(function() {
  const hash = location.hash.replace('#', '');
  if (hash === 'pallet') { switchTab('pallet', document.getElementById('tab-btn-pallet')); }
})();

function switchTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  const b = btn || event?.currentTarget;
  if (b) b.classList.add('active');
}

// ── Mixed pallet toggle ────────────────────────────────────────
function toggleMixed() {
  var sec = document.getElementById('mixed-pallet-section');
  if (document.getElementById('mixed-toggle').checked) {
    sec.classList.remove('hidden');
  } else {
    sec.classList.add('hidden');
  }
}

// ── Non-standard box rows ──────────────────────────────────────
var nonstdCount = 0;

function addNonstdRow() {
  var idx = nonstdCount++;
  var container = document.getElementById('nonstd-rows');
  var row = document.createElement('div');
  row.className = 'nonstd-row';
  row.id = 'nonstd-row-' + idx;
  row.innerHTML =
    '<button type="button" class="remove-btn" onclick="removeNonstdRow(' + idx + ')" title="Remove this row">✕</button>' +
    '<div class="form-grid cols-3" style="margin-top:4px;">' +
      '<div class="form-group">' +
        '<label>Box Number</label>' +
        '<input type="number" id="ns_box_' + idx + '" min="1" placeholder="e.g. 33" oninput="onNonstdInput(' + idx + ')">' +
      '</div>' +
      '<div class="form-group">' +
        '<label>Non-Standard Qty</label>' +
        '<input type="number" id="ns_qty_' + idx + '" min="1" placeholder="e.g. 4" oninput="onNonstdInput(' + idx + ')">' +
      '</div>' +
      '<div class="form-group">' +
        '<label>Copies</label>' +
        '<input type="number" id="ns_copies_' + idx + '" min="1" max="10" value="5">' +
      '</div>' +
    '</div>';
  container.appendChild(row);
}

function removeNonstdRow(idx) {
  var row = document.getElementById('nonstd-row-' + idx);
  if (row) row.remove();
  // Mark as removed so buildNonstdParams skips it
  if (!window.removedNonstd) window.removedNonstd = {};
  window.removedNonstd[idx] = true;
}

// Tracks which rows have already triggered adding a new row
var nonstdFilledRows = {};

function onNonstdInput(idx) {
  var boxVal = document.getElementById('ns_box_' + idx).value.trim();
  var qtyVal = document.getElementById('ns_qty_' + idx).value.trim();
  // Once both fields in this row have a value, add a new empty row (only once per row)
  if (boxVal && qtyVal && !nonstdFilledRows[idx]) {
    nonstdFilledRows[idx] = true;
    addNonstdRow();
  }
}

// Serialize non-standard rows into hidden inputs before form submit
function buildNonstdParams() {
  var hiddenDiv = document.getElementById('nonstd-hidden-inputs');
  hiddenDiv.innerHTML = '';
  var entries = [];
  var rows = document.querySelectorAll('#nonstd-rows .nonstd-row');
  rows.forEach(function(row) {
    var idx = row.id.replace('nonstd-row-', '');
    var boxEl = document.getElementById('ns_box_' + idx);
    var qtyEl = document.getElementById('ns_qty_' + idx);
    var copiesEl = document.getElementById('ns_copies_' + idx);
    if (!boxEl || !qtyEl) return;
    var b = boxEl.value.trim();
    var q = qtyEl.value.trim();
    var c = copiesEl ? copiesEl.value.trim() : '5';
    if (b && q) {
      entries.push({ box: b, qty: q, copies: c });
    }
  });
  // Encode as JSON in a single hidden field
  var h = document.createElement('input');
  h.type = 'hidden';
  h.name = 'nonstd_json';
  h.value = JSON.stringify(entries);
  hiddenDiv.appendChild(h);
  return true;
}

// Initialize with one empty non-standard row on page load
addNonstdRow();

  // ── v1.49 known-parts autocomplete ─────────────────────────────────────
  (function(){
    var KP = <?php echo $kp_json; ?>;
    function e(id){return document.getElementById(id);}
    function filled(el,yes){if(el){el.classList.toggle('kp-filled',yes);}}
    var custEl=e('kp-customer'), partEl=e('kp-part'), partList=e('kp-part-list');
    var seqEl=e('kp-seq-start'), dateRow=e('kp-date-row'), dateEl=e('kp-received-date');

    function rebuildParts(cust){
      if(!partList)return;
      partList.innerHTML='';
      Object.keys(KP).forEach(function(pn){
        if(!cust||KP[pn].customer===cust){
          var o=document.createElement('option');o.value=pn;partList.appendChild(o);
        }
      });
    }

    function applyQty(){
      var wrap=e('kp-qty-wrap'); if(!wrap)return;
      var old=e('kp-qty-sel'); if(old)old.remove();
      var inp=e('kp-std-qty'); if(!inp)return;
      inp.style.display=''; filled(inp,false);
      var pn=(partEl?partEl.value:'').trim();
      var entry=KP[pn]; if(!entry||!entry.std_qtys||!entry.std_qtys.length)return;
      var qtys=entry.std_qtys;
      if(qtys.length===1){inp.value=qtys[0];filled(inp,true);hideBanner();return;}
      inp.style.display='none';
      var sel=document.createElement('select');
      sel.id='kp-qty-sel';sel.name='std_qty';
      sel.style.cssText='width:100%;padding:7px 10px;border:1px solid #aaa;border-radius:3px;font-size:13px;font-family:Arial,sans-serif;';
      qtys.forEach(function(q){var o=document.createElement('option');o.value=q;o.textContent=q;sel.appendChild(o);});
      var oth=document.createElement('option');oth.value='__other__';oth.textContent='Other\u2026';sel.appendChild(oth);
      sel.addEventListener('change',function(){
        if(sel.value==='__other__'){sel.remove();inp.style.display='';inp.value='';inp.focus();}
      });
      wrap.insertBefore(sel,e('kp-nonstd-banner'));filled(sel,true);hideBanner();
    }

    function hideBanner(){var b=e('kp-nonstd-banner');if(b)b.style.display='none';}
    function checkNonstd(){
      var pn=(partEl?partEl.value:'').trim(), entry=KP[pn];
      if(!entry||!entry.std_qtys||!entry.std_qtys.length){hideBanner();return;}
      var inp=e('kp-std-qty'); if(!inp||inp.style.display==='none')return;
      var val=parseInt(inp.value,10);
      if(!val||entry.std_qtys.indexOf(val)!==-1){hideBanner();return;}
      var bt=e('kp-banner-txt');
      if(bt)bt.textContent=(pn||'This part')+' normally ships '+entry.std_qtys.join(' or ')+' pcs/box. This quantity ('+val+') differs \u2014 is this a non-standard box?';
      var b=e('kp-nonstd-banner');if(b)b.style.display='block';
    }

    function syncDate(){
      if(!seqEl||!dateRow)return;
      var v=parseInt(seqEl.value,10), show=(!isNaN(v)&&v>=1);
      dateRow.style.maxHeight=show?'70px':'0';
      dateRow.style.opacity=show?'1':'0';
      if(dateEl)dateEl.required=show;
    }

    if(custEl){
      custEl.addEventListener('input',function(){rebuildParts(custEl.value.trim());applyQty();});
    }
    if(partEl){
      partEl.addEventListener('input',function(){
        var pn=partEl.value.trim(), entry=KP[pn];
        if(entry&&custEl&&!custEl.value.trim()){custEl.value=entry.customer;filled(custEl,true);rebuildParts(entry.customer);}
        applyQty();
      });
    }
    var qi=e('kp-std-qty');
    if(qi){qi.addEventListener('change',checkNonstd);qi.addEventListener('blur',checkNonstd);}

    var btnY=e('kp-btn-yes'),btnN=e('kp-btn-no'),btnD=e('kp-btn-dim');
    if(btnY)btnY.addEventListener('click',function(){
      var pn=(partEl?partEl.value:'').trim(), entry=KP[pn];
      var inp=e('kp-std-qty'), val=parseInt((inp||{}).value,10);
      hideBanner();
      if(entry&&entry.std_qtys.length&&inp)inp.value=entry.std_qtys[0];
      // Trigger the existing addRow mechanism for non-std boxes
      if(typeof addNonstdRow==='function')addNonstdRow();
    });
    if(btnN)btnN.addEventListener('click',hideBanner);
    if(btnD)btnD.addEventListener('click',hideBanner);

    if(seqEl)seqEl.addEventListener('input',syncDate);
    syncDate();
  })();

</script>
  <div class="version-footer">LF Label Generator&nbsp;v1.49.3 &middot; index</div>
</body>
</html>
