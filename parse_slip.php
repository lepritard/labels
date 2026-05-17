<?php
// parse_slip.php — server-side packing slip parser for v1.44
// Accepts uploaded .xlsx files, runs parse_packing_slip.py, returns JSON

header('Content-Type: application/json');
set_time_limit(120);
ini_set('max_execution_time', 120);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['slips'])) {
    echo json_encode(['error' => 'No files uploaded.']);
    exit;
}

$upload_dir = sys_get_temp_dir() . '/lf_slips_' . uniqid();
mkdir($upload_dir, 0700, true);

$files  = $_FILES['slips'];
$paths  = [];
$errors = [];

// Normalise $_FILES array for multiple uploads
$count = is_array($files['name']) ? count($files['name']) : 1;
for ($i = 0; $i < $count; $i++) {
    $name = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
    $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
    $err  = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
    if ($err !== UPLOAD_ERR_OK) { $errors[] = "Upload error on $name (code $err)"; continue; }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls'])) { $errors[] = "$name is not an Excel file"; continue; }
    $dest = $upload_dir . '/' . basename($name);
    move_uploaded_file($tmp, $dest);
    $paths[] = $dest;
}

if (empty($paths)) {
    echo json_encode(['error' => implode('; ', $errors) ?: 'No valid files.']);
    exit;
}

// Locate the Python parser relative to this PHP file
$parser = __DIR__ . '/parse_packing_slip.py';
if (!file_exists($parser)) {
    echo json_encode(['error' => 'Parser script not found: parse_packing_slip.py']);
    exit;
}

// Build command — escape each path
$cmd_parts = ['python3', escapeshellarg($parser)];
foreach ($paths as $p) $cmd_parts[] = escapeshellarg($p);
$cmd = implode(' ', $cmd_parts) . ' 2>&1';

$output = shell_exec($cmd);

// Clean up temp files
foreach ($paths as $p) @unlink($p);
@rmdir($upload_dir);

if ($output === null) {
    echo json_encode(['error' => 'Failed to execute parser. Is python3 available?']);
    exit;
}

// Try to decode JSON from parser output
$decoded = json_decode($output, true);
if ($decoded === null) {
    echo json_encode(['error' => 'Parser returned invalid JSON', 'raw' => substr($output, 0, 500)]);
    exit;
}

// Attach any upload-level errors as a warning
if (!empty($errors)) $decoded['_warnings'] = $errors;

echo json_encode($decoded);
