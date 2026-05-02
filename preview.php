<?php
// Lake Forest Industries — Label Renderer
// preview.php v1.2 — Landscape-native layouts
// Box labels:   6" × 4" landscape
// Pallet labels: 12" × 4" landscape

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function format_date($d) {
  $ts = strtotime($d);
  if (!$ts) return $d;
  return date('F j, Y', $ts);
}

$logo_path = __DIR__ . '/assets/logo.svg';
$logo_data = '';
if (file_exists($logo_path)) {
  $logo_data = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logo_path));
}

// ── Params ───────────────────────────────────────────────────────────────────
$type          = $_GET['type'] ?? 'box';
$customer      = trim($_GET['customer'] ?? '');
$na_number     = trim($_GET['na_number'] ?? '');
$received_date = format_date($_GET['received_date'] ?? date('Y-m-d'));
$copies        = max(1, intval($_GET['copies'] ?? 1));

// Box
$part_number   = trim($_GET['part_number'] ?? '');
$total_boxes   = max(1, intval($_GET['total_boxes'] ?? 1));
$std_qty       = intval($_GET['std_qty'] ?? 0);
$nonstd_box    = intval($_GET['nonstd_box_num'] ?? 0);
$nonstd_qty    = intval($_GET['nonstd_qty'] ?? 0);
$nonstd_copies = max(1, intval($_GET['nonstd_copies'] ?? 5));

// Pallet
$mixed          = isset($_GET['mixed']) && $_GET['mixed'] == '1';
$pallet_num     = intval($_GET['pallet_num'] ?? 1);
$total_pallets  = intval($_GET['total_pallets'] ?? 1);
$pallet_qty     = intval($_GET['pallet_qty'] ?? 0);
$pallet_boxes   = intval($_GET['pallet_boxes'] ?? 0);
$pallet_box_qty = intval($_GET['pallet_box_qty'] ?? 0);

$part_number_2    = trim($_GET['part_number_2'] ?? '');
$pallet_num_2     = intval($_GET['pallet_num_2'] ?? 1);
$total_pallets_2  = intval($_GET['total_pallets_2'] ?? 1);
$pallet_qty_2     = intval($_GET['pallet_qty_2'] ?? 0);
$pallet_boxes_2   = intval($_GET['pallet_boxes_2'] ?? 0);
$pallet_box_qty_2 = intval($_GET['pallet_box_qty_2'] ?? 0);

if ($type === 'pallet') {
  $page_w = '12in'; $page_h = '4in';
} else {
  $page_w = '6in'; $page_h = '4in';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Labels — <?php echo h($customer); ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: #666;
  font-family: Arial, Helvetica, sans-serif;
  padding: 20px;
}

.print-btn {
  position: fixed; top: 12px; right: 12px; z-index: 9999;
  background: #000; color: #fff; border: none; padding: 10px 20px;
  font-size: 14px; font-weight: bold; cursor: pointer; border-radius: 3px;
  font-family: Arial, Helvetica, sans-serif;
}
.print-btn:hover { background: #333; }

/* ── Label page shell ── */
.label-page {
  background: #fff;
  width: <?php echo $page_w; ?>;
  height: <?php echo $page_h; ?>;
  margin: 0 auto 20px;
  display: block;
  position: relative;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,0.5);
}

@page {
  size: <?php echo $page_w; ?> <?php echo $page_h; ?>;
  margin: 0;
}
@media print {
  body { background: none; padding: 0; }
  .print-btn { display: none; }
  .label-page {
    box-shadow: none;
    margin: 0;
    page-break-after: always;
    break-after: page;
  }
  .label-page:last-child {
    page-break-after: avoid;
    break-after: avoid;
  }
}

/* ═══════════════════════════════════════════════════════════
   BOX LABEL  —  6" × 4" landscape
   Layout:
     ┌─────────────────────────────────────────────────────┐
     │ Customer: NAME                         NA-XXXXXX    │  ← header row
     ├────────────────────────────────┬────────────────────┤
     │ Part Number:                   │  Box X of Y        │
     │ ████████████████████  barcode  │  NA-XXXXXX         │
     │ PART-NUMBER (Impact 38pt)      │                    │
     │                                │  Qty:              │
     │                                │  ◄1,480► pcs       │
     ├────────────────────────────────┴────────────────────┤
     │ Received: date                    [LF logo]         │  ← footer row
     └─────────────────────────────────────────────────────┘
═══════════════════════════════════════════════════════════ */
.box-label {
  width: 6in;
  height: 4in;
  padding: 0.12in 0.15in 0.1in;
  display: flex;
  flex-direction: column;
}

/* Header */
.bl-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 0.08in;
}
.bl-customer-label {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 11pt;
  font-weight: normal;
}
.bl-customer {
  font-family: 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif;
  font-size: 18pt;
  font-weight: bold;
}
.bl-na-header {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12pt;
  font-weight: bold;
  white-space: nowrap;
}

/* Main body: two columns */
.bl-body {
  display: flex;
  flex-direction: row;
  flex: 1;
  gap: 0.12in;
  min-height: 0;
}

/* Left column: Part Number label + barcode + large part number */
.bl-left {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.bl-pn-label {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 9pt;
  font-weight: bold;
  margin-bottom: 2pt;
}
.bl-barcode {
  display: block;
  width: 100%;
  height: 44pt;
  margin-bottom: 3pt;
}
.bl-part-number {
  font-family: Impact, 'Arial Narrow', Arial, sans-serif;
  font-size: 34pt;
  letter-spacing: 0.02em;
  line-height: 1;
}

/* Right column: Box counter + NA + Qty */
.bl-right {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  gap: 4pt;
  min-width: 1.5in;
  max-width: 1.7in;
  padding-left: 0.08in;
  border-left: 1pt solid #ccc;
}
.bl-box-counter {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 10pt;
  line-height: 1.3;
}
.bl-box-counter strong { font-size: 11pt; }
.bl-na-right {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 10pt;
  font-weight: bold;
  color: #333;
}
.bl-qty-label {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 10pt;
  font-weight: bold;
  margin-top: 4pt;
}
.bl-qty-line {
  display: flex;
  align-items: baseline;
  line-height: 1;
}
.bl-qty-arrow {
  font-family: Impact, Arial, sans-serif;
  font-size: 26pt;
}
.bl-qty-num {
  font-family: Impact, Arial, sans-serif;
  font-size: 30pt;
}
.bl-qty-pcs {
  font-family: 'Century Gothic', 'Gill Sans', Arial, sans-serif;
  font-size: 20pt;
  font-weight: bold;
  margin-left: 3pt;
}
.bl-qty-std {
  font-family: 'Century Gothic', 'Gill Sans', Arial, sans-serif;
  font-size: 26pt;
  font-weight: bold;
  line-height: 1;
}

/* Footer */
.bl-footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-top: 0.06in;
  padding-top: 4pt;
}
.bl-received {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 8.5pt;
  line-height: 1.5;
}
.bl-received strong { display: block; font-size: 8.5pt; }
.bl-logo {
  height: 36pt;
  max-width: 1.8in;
  object-fit: contain;
  display: block;
}

/* ═══════════════════════════════════════════════════════════
   PALLET LABEL  —  12" × 4" landscape
   Layout (single part):
     ┌──────────────────────────────────────────────────────────────────────────┐
     │ Customer: NAME                                           NA-XXXXXX       │
     ├──────────────────────────────────────────────────────────────────────────┤
     │  Pallet X of Y for Part Number:                                          │
     │  ████████████████████████████████████████  barcode                       │
     │  PART-NUMBER (Impact large)                  ◄1,480► pcs                 │
     │                                              (37 boxes of 40 pcs)        │
     ├──────────────────────────────────────────────────────────────────────────┤
     │  Received: date                              [LF logo]                   │
     └──────────────────────────────────────────────────────────────────────────┘

   Layout (mixed pallet):
     ┌──────────────────────────────────────────────────────────────────────────┐
     │ Customer: NAME                                           NA-XXXXXX       │
     ├────────────────────────────────────┬──────────────────────────────────── │
     │ Pallet X of Y  Part Number:        │  MIXED   │ Pallet X of Y Part Num: │
     │ ████ barcode ████                  │  PALLET  │ ████ barcode ████        │
     │ PART-1 (Impact)    ◄qty► pcs       │          │ PART-2 (Impact) ◄qty►   │
     ├────────────────────────────────────┴──────────────────────────────────── │
     │ Received: date                              [LF logo]                    │
     └──────────────────────────────────────────────────────────────────────────┘
═══════════════════════════════════════════════════════════ */
.pallet-label {
  width: 12in;
  height: 4in;
  padding: 0.12in 0.18in 0.1in;
  display: flex;
  flex-direction: column;
}

/* Pallet header */
.pl-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 0.07in;
}
.pl-customer-label {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 11pt;
}
.pl-customer {
  font-family: 'Century Gothic', 'Gill Sans', Arial, sans-serif;
  font-size: 20pt;
  font-weight: bold;
}
.pl-na-header {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13pt;
  font-weight: bold;
  white-space: nowrap;
}

/* Pallet body */
.pl-body {
  display: flex;
  flex-direction: row;
  flex: 1;
  min-height: 0;
  gap: 0.15in;
}

/* Single pallet: left = barcode+part, right = qty block */
.pl-left {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.pl-pn-label {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 9pt;
  font-weight: bold;
  margin-bottom: 2pt;
}
.pl-pallet-row {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 9.5pt;
  margin-bottom: 2pt;
}
.pl-pallet-row strong { font-size: 10pt; }
.pl-barcode {
  display: block;
  width: 100%;
  height: 46pt;
  margin-bottom: 3pt;
}
.pl-part-number {
  font-family: Impact, 'Arial Narrow', Arial, sans-serif;
  font-size: 42pt;
  letter-spacing: 0.02em;
  line-height: 1;
}

/* Qty right block */
.pl-right {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4pt;
  min-width: 2.2in;
  max-width: 2.8in;
  padding-left: 0.1in;
  border-left: 1pt solid #ccc;
}
.pl-qty-label {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 10pt;
  font-weight: bold;
}
.pl-qty-line {
  display: flex;
  align-items: baseline;
  line-height: 1;
}
.pl-qty-arrow {
  font-family: Impact, Arial, sans-serif;
  font-size: 30pt;
}
.pl-qty-num {
  font-family: Impact, Arial, sans-serif;
  font-size: 36pt;
}
.pl-qty-pcs {
  font-family: 'Century Gothic', 'Gill Sans', Arial, sans-serif;
  font-size: 24pt;
  font-weight: bold;
  margin-left: 4pt;
}
.pl-qty-boxes {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 8.5pt;
  color: #444;
  margin-top: 2pt;
}

/* Mixed pallet: three columns with "MIXED PALLET" stamp in center */
.pl-mixed-col {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-width: 0.9in;
  max-width: 1.1in;
  padding: 0 0.08in;
}
.pl-mixed-stamp {
  font-family: Impact, 'Arial Narrow', Arial, sans-serif;
  font-size: 20pt;
  letter-spacing: 0.06em;
  color: #888;
  text-align: center;
  line-height: 1.1;
}

/* Each part column in mixed layout */
.pl-part-col {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.pl-part-col + .pl-part-col {
  border-left: 1pt dashed #bbb;
  padding-left: 0.12in;
}
/* Inline qty row for mixed parts */
.pl-inline-qty {
  display: flex;
  align-items: baseline;
  margin-top: 3pt;
  line-height: 1;
}

/* Pallet footer */
.pl-footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-top: 0.06in;
  padding-top: 4pt;
}
.pl-received {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 8.5pt;
  line-height: 1.5;
}
.pl-received strong { display: block; font-size: 8.5pt; }
.pl-logo {
  height: 38pt;
  max-width: 2.4in;
  object-fit: contain;
  display: block;
}

</style>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨️ Print Labels</button>

<?php

// ════════════════════════════════════════════════
// BOX LABELS
// ════════════════════════════════════════════════
if ($type === 'box'):

  $pages = [];
  if ($nonstd_box > 0 && $nonstd_qty > 0) {
    for ($c = 0; $c < $nonstd_copies; $c++) {
      $pages[] = ['box' => $nonstd_box, 'qty' => $nonstd_qty, 'nonstd' => true];
    }
  }
  for ($b = 1; $b <= $total_boxes; $b++) {
    if ($b === $nonstd_box) continue;
    for ($c = 0; $c < $copies; $c++) {
      $pages[] = ['box' => $b, 'qty' => $std_qty, 'nonstd' => false];
    }
  }

  $bid = 0;
  foreach ($pages as $pg):
    $box_num   = $pg['box'];
    $qty       = $pg['qty'];
    $is_nonstd = $pg['nonstd'];
    $bid++;
    $bc = 'bc_' . $bid;
?>
<div class="label-page">
<div class="box-label">

  <!-- Header -->
  <div class="bl-header">
    <div>
      <span class="bl-customer-label">Customer: </span><span class="bl-customer"><?php echo h($customer); ?></span>
    </div>
    <div class="bl-na-header"><?php echo h($na_number); ?></div>
  </div>

  <!-- Body: left (barcode + part number) / right (box counter + qty) -->
  <div class="bl-body">

    <div class="bl-left">
      <div class="bl-pn-label">Part Number:</div>
      <svg id="<?php echo $bc; ?>" class="bl-barcode"></svg>
      <div class="bl-part-number"><?php echo h($part_number); ?></div>
    </div>

    <div class="bl-right">
      <div class="bl-box-counter">
        Box <strong><?php echo $box_num; ?></strong> of <strong><?php echo $total_boxes; ?></strong>
      </div>
      <div class="bl-na-right"><?php echo h($na_number); ?></div>
      <div class="bl-qty-label">Qty:</div>
      <?php if ($is_nonstd): ?>
        <div class="bl-qty-line">
          <span class="bl-qty-arrow">&#9668;</span><span class="bl-qty-num"><?php echo number_format($qty); ?></span><span class="bl-qty-arrow">&#9658;</span><span class="bl-qty-pcs"> pcs</span>
        </div>
      <?php else: ?>
        <div class="bl-qty-line">
          <span class="bl-qty-std"><?php echo number_format($qty); ?> pcs</span>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Footer -->
  <div class="bl-footer">
    <div class="bl-received">
      <strong>Received:</strong><?php echo h($received_date); ?>
    </div>
    <?php if ($logo_data): ?>
      <img src="<?php echo $logo_data; ?>" class="bl-logo" alt="Lake Forest Industries">
    <?php endif; ?>
  </div>

</div>
</div>

<script>
(function(){
  try {
    JsBarcode("#<?php echo $bc; ?>", <?php echo json_encode($part_number); ?>, {
      format:"CODE128", width:2.2, height:46,
      displayValue:false, margin:0,
      background:"#ffffff", lineColor:"#000000"
    });
  } catch(e) { document.getElementById("<?php echo $bc; ?>").style.display="none"; }
})();
</script>

<?php endforeach; ?>

<?php
// ════════════════════════════════════════════════
// PALLET LABELS
// ════════════════════════════════════════════════
elseif ($type === 'pallet'):

  $pid = 0;
  for ($c = 0; $c < $copies; $c++):
    $pid++;
    $bc1 = 'pbc1_' . $pid;
    $bc2 = 'pbc2_' . $pid;
?>
<div class="label-page">
<div class="pallet-label">

  <!-- Header -->
  <div class="pl-header">
    <div>
      <span class="pl-customer-label">Customer: </span><span class="pl-customer"><?php echo h($customer); ?></span>
    </div>
    <div class="pl-na-header"><?php echo h($na_number); ?></div>
  </div>

  <!-- Body -->
  <div class="pl-body">

  <?php if ($mixed): ?>

    <!-- Mixed pallet: col1 | MIXED PALLET stamp | col2 -->
    <div class="pl-part-col">
      <div class="pl-pallet-row">Pallet <strong><?php echo $pallet_num; ?></strong> of <strong><?php echo $total_pallets; ?></strong> for <strong>Part Number:</strong></div>
      <svg id="<?php echo $bc1; ?>" class="pl-barcode"></svg>
      <div class="pl-part-number"><?php echo h($part_number); ?></div>
      <div class="pl-inline-qty">
        <span class="pl-qty-arrow">&#9668;</span>
        <span class="pl-qty-num"><?php echo number_format($pallet_qty); ?></span>
        <span class="pl-qty-arrow">&#9658;</span>
        <span class="pl-qty-pcs"> pcs</span>
      </div>
      <?php if ($pallet_boxes && $pallet_box_qty): ?>
        <div class="pl-qty-boxes">(<?php echo $pallet_boxes; ?> boxes of <?php echo $pallet_box_qty; ?> pcs)</div>
      <?php endif; ?>
    </div>

    <div class="pl-mixed-col">
      <div class="pl-mixed-stamp">MIXED<br>PALLET</div>
    </div>

    <div class="pl-part-col">
      <div class="pl-pallet-row">Pallet <strong><?php echo $pallet_num_2; ?></strong> of <strong><?php echo $total_pallets_2; ?></strong> for <strong>Part Number:</strong></div>
      <svg id="<?php echo $bc2; ?>" class="pl-barcode"></svg>
      <div class="pl-part-number"><?php echo h($part_number_2); ?></div>
      <div class="pl-inline-qty">
        <span class="pl-qty-arrow">&#9668;</span>
        <span class="pl-qty-num"><?php echo number_format($pallet_qty_2); ?></span>
        <span class="pl-qty-arrow">&#9658;</span>
        <span class="pl-qty-pcs"> pcs</span>
      </div>
      <?php if ($pallet_boxes_2 && $pallet_box_qty_2): ?>
        <div class="pl-qty-boxes">(<?php echo $pallet_boxes_2; ?> boxes of <?php echo $pallet_box_qty_2; ?> pcs)</div>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <!-- Single part pallet: wide left (barcode + part) / narrower right (qty) -->
    <div class="pl-left">
      <div class="pl-pallet-row">Pallet <strong><?php echo $pallet_num; ?></strong> of <strong><?php echo $total_pallets; ?></strong> for <strong>Part Number:</strong></div>
      <svg id="<?php echo $bc1; ?>" class="pl-barcode"></svg>
      <div class="pl-part-number"><?php echo h($part_number); ?></div>
    </div>

    <div class="pl-right">
      <div class="pl-qty-label">Qty:</div>
      <div class="pl-qty-line">
        <span class="pl-qty-arrow">&#9668;</span>
        <span class="pl-qty-num"><?php echo number_format($pallet_qty); ?></span>
        <span class="pl-qty-arrow">&#9658;</span>
        <span class="pl-qty-pcs"> pcs</span>
      </div>
      <?php if ($pallet_boxes && $pallet_box_qty): ?>
        <div class="pl-qty-boxes">(<?php echo $pallet_boxes; ?> boxes of <?php echo $pallet_box_qty; ?> pcs)</div>
      <?php endif; ?>
    </div>

  <?php endif; ?>

  </div><!-- /pl-body -->

  <!-- Footer -->
  <div class="pl-footer">
    <div class="pl-received">
      <strong>Received:</strong><?php echo h($received_date); ?>
    </div>
    <?php if ($logo_data): ?>
      <img src="<?php echo $logo_data; ?>" class="pl-logo" alt="Lake Forest Industries">
    <?php endif; ?>
  </div>

</div>
</div>

<script>
(function(){
  <?php if ($part_number): ?>
  try {
    JsBarcode("#<?php echo $bc1; ?>", <?php echo json_encode($part_number); ?>, {
      format:"CODE128", width:2.2, height:48,
      displayValue:false, margin:0,
      background:"#ffffff", lineColor:"#000000"
    });
  } catch(e) { document.getElementById("<?php echo $bc1; ?>").style.display="none"; }
  <?php endif; ?>
  <?php if ($mixed && $part_number_2): ?>
  try {
    JsBarcode("#<?php echo $bc2; ?>", <?php echo json_encode($part_number_2); ?>, {
      format:"CODE128", width:2.2, height:48,
      displayValue:false, margin:0,
      background:"#ffffff", lineColor:"#000000"
    });
  } catch(e) { document.getElementById("<?php echo $bc2; ?>").style.display="none"; }
  <?php endif; ?>
})();
</script>

<?php endfor; ?>

<?php endif; ?>

</body>
</html>
