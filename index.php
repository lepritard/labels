<?php
// Lake Forest Industries — Warehouse Label Generator
// index.php — Main entry form
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

  .app-header {
    background: #1a1a1a; color: #fff; padding: 12px 24px;
    display: flex; align-items: center; gap: 16px;
  }
  .app-header h1 { font-size: 18px; font-weight: bold; letter-spacing: 0.02em; }
  .app-header span { font-size: 12px; color: #999; }

  .container { max-width: 880px; margin: 24px auto; padding: 0 16px; }

  .card {
    background: #fff; border: 1px solid #ccc; border-radius: 4px;
    padding: 20px 24px; margin-bottom: 20px;
  }
  .card h2 { font-size: 15px; font-weight: bold; margin-bottom: 16px; border-bottom: 2px solid #000; padding-bottom: 6px; }

  .tabs { display: flex; gap: 0; margin-bottom: 0; border-bottom: 2px solid #000; }
  .tab-btn {
    padding: 8px 20px; font-size: 13px; font-weight: bold; cursor: pointer;
    background: #e8e8e8; border: 1px solid #ccc; border-bottom: none;
    color: #555; transition: background 0.15s;
    font-family: Arial, Helvetica, sans-serif;
  }
  .tab-btn.active { background: #fff; color: #000; border-color: #000; border-bottom: 2px solid #fff; margin-bottom: -2px; }
  .tab-btn:hover:not(.active) { background: #d8d8d8; }

  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; }
  .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
  .form-group { display: flex; flex-direction: column; gap: 4px; }
  .form-group.full { grid-column: 1 / -1; }
  label { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.04em; color: #444; }
  input, select {
    padding: 7px 10px; border: 1px solid #aaa; border-radius: 3px;
    font-size: 13px; font-family: Arial, Helvetica, sans-serif;
    background: #fff; color: #000;
    transition: border-color 0.15s, box-shadow 0.15s;
  }
  input:focus, select:focus { outline: none; border-color: #000; box-shadow: 0 0 0 2px rgba(0,0,0,0.12); }

  .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; color: #666; margin: 16px 0 8px; border-top: 1px solid #eee; padding-top: 12px; }

  .btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 20px; font-size: 13px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;
    border: 2px solid #000; border-radius: 3px; cursor: pointer;
    transition: background 0.15s, color 0.15s;
    text-decoration: none;
  }
  .btn-primary { background: #000; color: #fff; }
  .btn-primary:hover { background: #333; }
  .btn-secondary { background: #fff; color: #000; }
  .btn-secondary:hover { background: #f0f0f0; }

  .btn-row { display: flex; gap: 10px; margin-top: 20px; align-items: center; flex-wrap: wrap; }

  .note { font-size: 11px; color: #666; font-style: italic; line-height: 1.5; }
  .note strong { color: #333; font-style: normal; }

  .non-std-section { background: #fffbe6; border: 1px solid #e0c000; border-radius: 3px; padding: 12px 14px; margin-top: 12px; }
  .non-std-section .section-title { border-top: none; padding-top: 0; margin-top: 0; color: #7a6000; }

  .mixed-pallet-section { background: #f0f6ff; border: 1px solid #99bbdd; border-radius: 3px; padding: 12px 14px; margin-top: 12px; }
  .mixed-pallet-section .section-title { border-top: none; padding-top: 0; margin-top: 0; color: #1a4a7a; }

  .preview-link {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; color: #0044aa; text-decoration: none; font-weight: bold;
  }
  .preview-link:hover { text-decoration: underline; }

  .hidden { display: none !important; }

  @media (max-width: 600px) {
    .form-grid { grid-template-columns: 1fr; }
    .form-grid.cols-3 { grid-template-columns: 1fr 1fr; }
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

  <!-- TAB STRIP -->
  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('boxes')">📦 Box Labels</button>
    <button class="tab-btn" onclick="switchTab('pallet')">🏗️ Pallet Labels</button>
  </div>

  <!-- ===================== BOX LABELS TAB ===================== -->
  <div id="tab-boxes" class="tab-panel active card" style="border-top:none;border-radius:0 0 4px 4px;">
    <form method="GET" action="preview.php" target="_blank" id="form-boxes">
      <input type="hidden" name="type" value="box">

      <div class="form-grid">
        <div class="form-group">
          <label>Customer Name</label>
          <input type="text" name="customer" placeholder="e.g. TurboChef" required>
        </div>
        <div class="form-group">
          <label>NA Number (Shipment ID)</label>
          <input type="text" name="na_number" placeholder="e.g. NA-222610" required>
        </div>
        <div class="form-group">
          <label>Part Number</label>
          <input type="text" name="part_number" placeholder="e.g. ENC-1724" required>
        </div>
        <div class="form-group">
          <label>Received Date</label>
          <input type="date" name="received_date" value="<?php echo date('Y-m-d'); ?>" required>
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
          <input type="number" name="std_qty" min="1" placeholder="e.g. 6" required>
        </div>
        <div class="form-group">
          <label>Label Copies Per Box</label>
          <input type="number" name="copies" min="1" max="10" value="1">
        </div>
      </div>

      <!-- Non-standard last box -->
      <div class="non-std-section">
        <div class="section-title">⚠️ Non-Standard Box (Optional)</div>
        <p class="note" style="margin-bottom:10px;">Use this when the last box in the shipment has a different quantity than the rest (e.g., a partial box). The ◄qty► arrow style will be applied to this box's label automatically.</p>
        <div class="form-grid cols-3">
          <div class="form-group">
            <label>Which box number?</label>
            <input type="number" name="nonstd_box_num" min="1" placeholder="e.g. 33 (last box)">
          </div>
          <div class="form-group">
            <label>Non-Standard Qty</label>
            <input type="number" name="nonstd_qty" min="1" placeholder="e.g. 4">
          </div>
          <div class="form-group">
            <label>Copies of This Label</label>
            <input type="number" name="nonstd_copies" min="1" max="10" value="5">
          </div>
        </div>
      </div>

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
          <label>NA Number (Shipment ID)</label>
          <input type="text" name="na_number" placeholder="e.g. NA-222610" required>
        </div>
        <div class="form-group">
          <label>Received Date</label>
          <input type="date" name="received_date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="form-group">
          <label>Label Copies (sides of pallet)</label>
          <input type="number" name="copies" min="1" max="10" value="5">
        </div>
      </div>

      <!-- Single-part pallet -->
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

      <!-- Mixed pallet toggle -->
      <div style="margin-top:14px;">
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:bold;cursor:pointer;text-transform:none;letter-spacing:0;">
          <input type="checkbox" id="mixed-toggle" name="mixed" value="1" onchange="toggleMixed(this)" style="width:16px;height:16px;cursor:pointer;">
          This is a <strong>MIXED PALLET</strong> (two part numbers on one pallet)
        </label>
      </div>

      <!-- Mixed pallet second part -->
      <div id="mixed-pallet-section" class="mixed-pallet-section hidden">
        <div class="section-title">🔀 Second Part on Mixed Pallet</div>
        <div class="form-grid cols-3">
          <div class="form-group">
            <label>2nd Part Number</label>
            <input type="text" name="part_number_2" placeholder="e.g. A23968">
          </div>
          <div class="form-group">
            <label>Pallet # (2nd part)</label>
            <input type="number" name="pallet_num_2" min="1" placeholder="e.g. 1">
          </div>
          <div class="form-group">
            <label>Total Pallets (2nd part)</label>
            <input type="number" name="total_pallets_2" min="1" placeholder="e.g. 2">
          </div>
          <div class="form-group">
            <label>Total Qty (2nd part)</label>
            <input type="number" name="pallet_qty_2" min="1" placeholder="e.g. 950">
          </div>
          <div class="form-group">
            <label>Boxes (2nd part)</label>
            <input type="number" name="pallet_boxes_2" min="1" placeholder="e.g. 19">
          </div>
          <div class="form-group">
            <label>Qty Per Box (2nd part)</label>
            <input type="number" name="pallet_box_qty_2" min="1" placeholder="e.g. 50">
          </div>
        </div>
      </div>

      <div class="btn-row">
        <button type="submit" class="btn btn-primary">🖨️ Generate &amp; Preview Labels</button>
        <p class="note"><strong>Tip:</strong> Pallet labels are 4″ × 12″. Set your printer to this size before printing.</p>
      </div>
    </form>
  </div>

</div><!-- /container -->

<script>
function switchTab(name) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelector('#tab-' + name).classList.add('active');
  event.target.classList.add('active');
}
function toggleMixed(cb) {
  document.getElementById('mixed-pallet-section').classList.toggle('hidden', !cb.checked);
}
</script>
</body>
</html>
