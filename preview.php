<?php
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

$type          = $_GET['type'] ?? 'box';
$customer      = trim($_GET['customer'] ?? '');
$na_number     = trim($_GET['na_number'] ?? '');
$received_date = format_date($_GET['received_date'] ?? date('Y-m-d'));
$raw_date      = $_GET['received_date'] ?? date('Y-m-d');
$serial_prefix = date('y', strtotime($raw_date)) . sprintf('%03d', date('z', strtotime($raw_date)) + 1);
$copies        = max(1, intval($_GET['copies'] ?? 1));

$part_number   = trim($_GET['part_number'] ?? '');
$seq_start     = max(1, intval($_GET['seq_start'] ?? 1));
$total_boxes   = max(1, intval($_GET['total_boxes'] ?? 1));
$std_qty       = intval($_GET['std_qty'] ?? 0);
// Parse multiple non-standard boxes from JSON (v1.11+)
$nonstd_entries = [];
if (!empty($_GET['nonstd_json'])) {
  $decoded = json_decode($_GET['nonstd_json'], true);
  if (is_array($decoded)) {
    foreach ($decoded as $entry) {
      $bn = intval($entry['box'] ?? 0);
      $bq = intval($entry['qty'] ?? 0);
      $bcp = max(1, intval($entry['copies'] ?? 5));
      if ($bn > 0 && $bq > 0) $nonstd_entries[$bn] = ['qty' => $bq, 'copies' => $bcp];
    }
  }
}
// Legacy single-box fallback (backwards compat)
$nonstd_box    = intval($_GET['nonstd_box_num'] ?? 0);
$nonstd_qty    = intval($_GET['nonstd_qty'] ?? 0);
$nonstd_copies = max(1, intval($_GET['nonstd_copies'] ?? 5));
if ($nonstd_box > 0 && $nonstd_qty > 0 && !isset($nonstd_entries[$nonstd_box])) {
  $nonstd_entries[$nonstd_box] = ['qty' => $nonstd_qty, 'copies' => $nonstd_copies];
}

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
$unit_label = trim($_GET['unit_label'] ?? '');   // 'WOODEN CASE' or empty (= normal pallet)
$revision      = trim($_GET['revision']   ?? '');   // revision letter to display below barcode
$t013_prepped  = !empty($_GET['t013_prepped']);


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
@font-face {
  font-family: 'LFCenturyGothic';
  src: url('./assets/fonts/CenturyGothic.ttf') format('truetype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
@font-face {
  font-family: 'LFCenturyGothic';
  src: url('./assets/fonts/CenturyGothic-Bold.ttf') format('truetype');
  font-weight: 700;
  font-style: normal;
  font-display: swap;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #666; font-family: Arial, Helvetica, sans-serif; padding: 20px; }
.print-btn {
  position: fixed; top: 12px; right: 12px; z-index: 9999;
  background: #000; color: #fff; border: none; padding: 10px 20px;
  font-size: 14px; font-weight: bold; cursor: pointer; border-radius: 3px;
  font-family: Arial, Helvetica, sans-serif;
}
.print-btn:hover { background: #333; }
.label-page {
  background: #fff; width: <?php echo $page_w; ?>; height: <?php echo $page_h; ?>;
  margin: 0 auto 20px; display: block; position: relative; overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,0.5);
}
@page { size: <?php echo $page_w; ?> <?php echo $page_h; ?>; margin: 0; }
@media print {
  body { background: none; padding: 0; }
  .print-btn { display: none; }
  .label-page { box-shadow: none; margin: 0; page-break-after: always; break-after: page; }
  .label-page:last-child { page-break-after: avoid; break-after: avoid; }
}

.cg { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; }
.cg-bold { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-weight: 700; }
.impact { font-family: Impact, 'Arial Narrow', Arial, sans-serif; }
.arial { font-family: Arial, Helvetica, sans-serif; }

.box-label {
  width: 6in; height: 4in; padding: 0.15in 0.18in 0.12in;
  display: flex; flex-direction: column;
}
.bl-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.1in; }
.bl-customer-label { font-family: Impact, 'Arial Narrow', Arial, sans-serif; font-size: 14pt; margin-right: 4px; }
.bl-customer { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 21pt; font-weight: 700; vertical-align: baseline; }
.bl-body { display: flex; flex-direction: row; flex: 1; gap: 0.14in; min-height: 0; }
.bl-left { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.bl-pn-label { font-family: Impact, 'Arial Narrow', Arial, sans-serif; font-size: 14pt; margin-bottom: 4px; }
.bl-barcode { display: block; width: 100%; height: 1.25in; margin-bottom: 0.03in; }
.bl-part-number { font-family: Arial, Helvetica, sans-serif; font-size: 30pt; font-weight: 400; letter-spacing: 0.04em; line-height: 1; text-align: center; }
.bl-right { display: flex; flex-direction: column; justify-content: flex-start; gap: 0.04in; min-width: 1.55in; max-width: 1.7in; padding-top: 0.27in; padding-left: 0.1in; }
.bl-na-right { font-family: Impact, 'Arial Narrow', Arial, sans-serif; font-size: 14pt; font-weight: 400; line-height: 1.1; }
.bl-box-counter { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 12pt; font-weight: 400; line-height: 1.3; }
.bl-box-counter strong { font-size: 14pt; font-weight: 700; }
.bl-qty-label { font-family: Impact, 'Arial Narrow', Arial, sans-serif; font-size: 14pt; margin-top: 0.15in; }
.bl-qty-line { display: flex; align-items: baseline; line-height: 1; }
.bl-qty-arrow, .bl-qty-num { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 22pt; font-weight: 700; }
.bl-qty-pcs { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 22pt; font-weight: 400; margin-left: 3px; }
.bl-footer {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-top: 0.06in; min-height: 0.92in;
}
.bl-footer-left { display: flex; flex-direction: column; justify-content: flex-end; }
.bl-received { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 12pt; font-weight: 700; line-height: 1.15; margin-bottom: 0.04in; }
.bl-serial-barcode { display: block; width: 1.6in; height: 0.35in; }
.bl-serial-number { font-family: Arial, Helvetica, sans-serif; font-size: 15pt; letter-spacing: 0.05em; line-height: 1; margin-top: 2px; text-align: center; }
.bl-logo-wrap {
  height: 0.88in; min-height: 0.88in; display: flex; align-items: flex-end; justify-content: flex-end;
  flex: 0 0 auto;
}
.bl-logo {
  display: block; height: 0.88in; width: auto; max-width: none; max-height: none; object-fit: contain;
}

/* Non-standard (mirrored) label */
.bl-body.nonstd { flex-direction: row-reverse; }
.bl-body.nonstd .bl-right { padding-left: 0; padding-right: 0.1in; }
.bl-qty-num.nonstd-size { font-size: 29pt; }
.bl-qty-arrow.nonstd-size { font-size: 29pt; }
.bl-qty-pcs.nonstd-size { font-size: 29pt; }

.pallet-label {
  width: 12in; height: 4in; padding: 0.15in 0.2in 0.12in;
  display: flex; flex-direction: column;
}
.pl-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.08in; }
.pl-customer-label { font-family: Impact, 'Arial Narrow', Arial, sans-serif; font-size: 0.17in; margin-right: 0.04in; }
.pl-customer { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 0.22in; font-weight: 400; }
.pl-na-header { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 0.15in; font-weight: 700; white-space: nowrap; }
.pl-body { display: flex; flex-direction: row; flex: 1; min-height: 0; gap: 0.18in; }
.pl-left, .pl-part-col { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.pl-pallet-row, .pl-pn-label { font-family: Impact, 'Arial Narrow', Arial, sans-serif; font-size: 0.17in; margin-bottom: 0.04in; }
.pl-barcode { display: block; width: 100%; height: 1.25in; margin-bottom: 0.04in; }
.pl-part-number { font-family: Arial, Helvetica, sans-serif; font-size: 0.32in; font-weight: 400; letter-spacing: 0.03em; line-height: 1; }
.pl-right { display: flex; flex-direction: column; justify-content: center; gap: 0.04in; min-width: 2.2in; max-width: 2.8in; }
.pl-qty-label { font-family: Impact, 'Arial Narrow', Arial, sans-serif; font-size: 0.17in; }
.pl-qty-line, .pl-inline-qty { display: flex; align-items: baseline; line-height: 1; }
.pl-qty-arrow, .pl-qty-num { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 0.28in; font-weight: 700; }
.pl-qty-pcs { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 0.28in; font-weight: 400; margin-left: 0.04in; }
.pl-qty-boxes { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 0.11in; color: #333; margin-top: 0.02in; }
.pl-mixed-col { display: flex; flex-direction: column; justify-content: center; align-items: center; min-width: 1in; max-width: 1.2in; }
.pl-mixed-stamp { font-family: Impact, 'Arial Narrow', Arial, sans-serif; font-size: 0.24in; letter-spacing: 0.06em; color: #888; text-align: center; line-height: 1.2; }
.pl-footer {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-top: 0.06in; min-height: 0.92in;
}
.pl-received { font-family: 'LFCenturyGothic', 'Century Gothic', 'Gill Sans', 'Trebuchet MS', Arial, sans-serif; font-size: 0.13in; font-weight: 700; line-height: 1.5; }
.pl-logo-wrap {
  height: 0.88in; min-height: 0.88in; display: flex; align-items: flex-end; justify-content: flex-end;
  flex: 0 0 auto;
}
.pl-logo { display: block; height: 0.88in; width: auto; max-width: none; max-height: none; object-fit: contain; }
</style>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨️ Print Labels</button>
<?php if ($type === 'box'):
  $pages = [];
  // Sequence is tied to box number: box N gets seq (seq_start + N - 1)
  // Non-standard boxes first (each with its own copy count)
  foreach ($nonstd_entries as $nsbox => $nsdata) {
    $box_seq = $seq_start + $nsbox - 1;
    for ($c = 0; $c < $nsdata['copies']; $c++)
      $pages[] = ['box'=>$nsbox,'qty'=>$nsdata['qty'],'nonstd'=>true,'seq'=>$box_seq];
  }
  // Standard boxes (skip any in nonstd_entries)
  for ($b = 1; $b <= $total_boxes; $b++) {
    if (isset($nonstd_entries[$b])) continue;
    $box_seq = $seq_start + $b - 1;
    for ($c = 0; $c < $copies; $c++) $pages[] = ['box'=>$b,'qty'=>$std_qty,'nonstd'=>false,'seq'=>$box_seq];
  }
  $bid = 0;
  foreach ($pages as $pg):
    $box_num = $pg['box']; $qty = $pg['qty']; $is_nonstd = $pg['nonstd']; $box_seq = $pg['seq']; $bid++; $bc = 'bc_'.$bid;
    $serial = $serial_prefix . sprintf('%04d', $box_seq); $sbc = 'sbc_'.$bid;
?>
<div class="label-page"><div class="box-label">
  <div class="bl-header"><div><span class="bl-customer-label">Customer:</span><span class="bl-customer"> <?php echo h($customer); ?></span></div></div>
  <div class="bl-body<?php echo $is_nonstd ? ' nonstd' : ''; ?>">
    <div class="bl-left">
      <div class="bl-pn-label">Part Number:</div>
      <svg id="<?php echo $bc; ?>" class="bl-barcode"></svg>
      <div class="bl-part-number"><?php echo h($part_number); ?></div>
    <?php if ($revision || $t013_prepped): ?>
      <div style="font-family:Arial,sans-serif;font-size:14pt;color:#555;text-align:center;margin-top:2px;letter-spacing:0.04em;">
        <?php if ($revision): ?>Rev. <?php echo h($revision); ?><?php endif; ?>
        <?php if ($t013_prepped): ?><span style="margin-left:<?php echo $revision ? '12px' : '0'; ?>;font-size:14pt;color:#000;">(Prepped for T013)</span><?php endif; ?>
      </div>
    <?php endif; ?>
    </div>
    <div class="bl-right">
      <div class="bl-na-right"><?php echo h($na_number); ?></div>
      <div class="bl-box-counter">Box <strong><?php echo $box_num; ?></strong> of <strong><?php echo $total_boxes; ?></strong></div>
      <div class="bl-qty-label">Qty:</div>
      <?php if ($is_nonstd): ?>
        <div class="bl-qty-line" data-fittext data-fittext-max="29"><span class="bl-qty-arrow nonstd-size">&#9668;</span><span class="bl-qty-num nonstd-size"><?php echo number_format($qty); ?></span><span class="bl-qty-arrow nonstd-size">&#9658;</span><span class="bl-qty-pcs nonstd-size"> pcs</span></div>
      <?php else: ?>
        <div class="bl-qty-line" data-fittext data-fittext-max="22"><span class="bl-qty-num"><?php echo number_format($qty); ?></span><span class="bl-qty-pcs"> pcs</span></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="bl-footer">
    <div class="bl-footer-left">
      <div class="bl-received">Received:<br><?php echo h($received_date); ?></div>
      <svg id="<?php echo $sbc; ?>" class="bl-serial-barcode"></svg>
      <div class="bl-serial-number"><?php echo h($serial); ?></div>
    </div>
    <?php if ($logo_data): ?><div class="bl-logo-wrap"><img src="<?php echo $logo_data; ?>" class="bl-logo" alt="Lake Forest Industries"></div><?php endif; ?>
  </div>
</div></div>
<script>
(function(){try{JsBarcode('#<?php echo $bc; ?>', <?php echo json_encode($part_number); ?>, {format:'CODE128', width:2.4, height:120, displayValue:false, margin:0, background:'#ffffff', lineColor:'#000000'});}catch(e){document.getElementById('<?php echo $bc; ?>').style.display='none';}try{JsBarcode('#<?php echo $sbc; ?>', <?php echo json_encode($serial); ?>, {format:'CODE128', width:1.2, height:28, displayValue:false, margin:0, background:'#ffffff', lineColor:'#000000'});}catch(e){document.getElementById('<?php echo $sbc; ?>').style.display='none';}})();
(function(){var els=document.querySelectorAll('[data-fittext]');els.forEach(function(el){var max=parseFloat(el.getAttribute('data-fittext-max'))||22;var spans=el.querySelectorAll('span');var fs=max;while(fs>8&&el.scrollWidth>el.clientWidth+1){fs-=0.5;spans.forEach(function(sp){sp.style.fontSize=fs+'pt';});}});})();
</script>
<?php endforeach; elseif ($type === 'pallet'):
  $pid = 0;
  for ($c = 0; $c < $copies; $c++):
    $pid++; $bc1 = 'pbc1_'.$pid; $bc2 = 'pbc2_'.$pid;
?>
<div class="label-page"><div class="pallet-label">
  <div class="pl-header">
    <div><span class="pl-customer-label">Customer:</span><span class="pl-customer"> <?php echo h($customer); ?></span></div>
    <div class="pl-na-header"><?php echo h($na_number); ?></div>
  </div>
  <div class="pl-body">
  <?php if ($mixed): ?>
    <div class="pl-part-col">
      <div class="pl-pallet-row">Pallet <?php echo $pallet_num; ?> of <?php echo $total_pallets; ?> for Part Number:</div>
      <svg id="<?php echo $bc1; ?>" class="pl-barcode"></svg>
      <div class="pl-part-number"><?php echo h($part_number); ?></div>
      <div class="pl-inline-qty"><span class="pl-qty-arrow">&#9668;</span><span class="pl-qty-num"><?php echo number_format($pallet_qty); ?></span><span class="pl-qty-arrow">&#9658;</span><span class="pl-qty-pcs"> pcs<?php echo $unit_label ? ' / '.h($unit_label) : ''; ?></span></div>
      <?php if ($pallet_boxes && $pallet_box_qty): ?><div class="pl-qty-boxes">(<?php echo $pallet_boxes; ?> boxes of <?php echo $pallet_box_qty; ?> pcs)</div><?php endif; ?>
    </div>
    <div class="pl-mixed-col"><div class="pl-mixed-stamp">MIXED<br>PALLET</div></div>
    <div class="pl-part-col">
      <div class="pl-pallet-row">Pallet <?php echo $pallet_num_2; ?> of <?php echo $total_pallets_2; ?> for Part Number:</div>
      <svg id="<?php echo $bc2; ?>" class="pl-barcode"></svg>
      <div class="pl-part-number"><?php echo h($part_number_2); ?></div>
      <div class="pl-inline-qty"><span class="pl-qty-arrow">&#9668;</span><span class="pl-qty-num"><?php echo number_format($pallet_qty_2); ?></span><span class="pl-qty-arrow">&#9658;</span><span class="pl-qty-pcs"> pcs<?php echo $unit_label ? ' / '.h($unit_label) : ''; ?></span></div>
      <?php if ($pallet_boxes_2 && $pallet_box_qty_2): ?><div class="pl-qty-boxes">(<?php echo $pallet_boxes_2; ?> boxes of <?php echo $pallet_box_qty_2; ?> pcs)</div><?php endif; ?>
    </div>
  <?php else: ?>
    <div class="pl-left">
      <div class="pl-pallet-row">Pallet <?php echo $pallet_num; ?> of <?php echo $total_pallets; ?> for Part Number:</div>
      <svg id="<?php echo $bc1; ?>" class="pl-barcode"></svg>
      <div class="pl-part-number"><?php echo h($part_number); ?></div>
    </div>
    <div class="pl-right">
      <div class="pl-qty-label">Qty:</div>
      <div class="pl-qty-line"><span class="pl-qty-arrow">&#9668;</span><span class="pl-qty-num"><?php echo number_format($pallet_qty); ?></span><span class="pl-qty-arrow">&#9658;</span><span class="pl-qty-pcs"> pcs<?php echo $unit_label ? ' / '.h($unit_label) : ''; ?></span></div>
      <?php if ($pallet_boxes && $pallet_box_qty): ?><div class="pl-qty-boxes">(<?php echo $pallet_boxes; ?> boxes of <?php echo $pallet_box_qty; ?> pcs)</div><?php endif; ?>
    </div>
  <?php endif; ?>
  </div>
  <div class="pl-footer">
    <div class="pl-received">Received:<br><?php echo h($received_date); ?></div>
    <?php if ($logo_data): ?><div class="pl-logo-wrap"><img src="<?php echo $logo_data; ?>" class="pl-logo" alt="Lake Forest Industries"></div><?php endif; ?>
  </div>
</div></div>
<script>
(function(){
  <?php if ($part_number): ?>try{JsBarcode('#<?php echo $bc1; ?>', <?php echo json_encode($part_number); ?>, {format:'CODE128', width:2.4, height:120, displayValue:false, margin:0, background:'#ffffff', lineColor:'#000000'});}catch(e){document.getElementById('<?php echo $bc1; ?>').style.display='none';}<?php endif; ?>
  <?php if ($mixed && $part_number_2): ?>try{JsBarcode('#<?php echo $bc2; ?>', <?php echo json_encode($part_number_2); ?>, {format:'CODE128', width:2.4, height:120, displayValue:false, margin:0, background:'#ffffff', lineColor:'#000000'});}catch(e){document.getElementById('<?php echo $bc2; ?>').style.display='none';}<?php endif; ?>
})();
</script>
<?php endfor; endif; ?>
</body>
</html>
